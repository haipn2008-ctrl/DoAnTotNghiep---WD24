<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Contract $contract;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-10 10:00:00');
        $this->withoutVite();
        $this->admin = $this->createUser('Admin', 'dashboard-admin@example.test');
        [$this->contract, $this->room] = $this->createContractContext();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_all_dashboard_routes_require_authentication_and_admin_role(): void
    {
        $client = $this->createUser('User', 'dashboard-client@example.test');
        $urls = [
            '/admin',
            '/admin/overview',
            '/admin/overview/revenue-chart',
            '/admin/overview/revenue-stats',
            '/admin/overview/room-stats',
            '/admin/overview/fill-rate',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertRedirect('/login');
        }

        foreach ($urls as $url) {
            $this->actingAs($client)->get($url)->assertForbidden();
        }
    }

    public function test_empty_dashboard_data_returns_zero_without_division_errors(): void
    {
        Invoice::query()->delete();
        Contract::query()->delete();
        Room::query()->delete();

        $this->actingAs($this->admin)->get('/admin/overview')
            ->assertSuccessful()
            ->assertViewHas('totalRevenue', 0)
            ->assertViewHas('totalReceivable', 0)
            ->assertViewHas('totalRooms', 0)
            ->assertViewHas('occupiedPercent', 0)
            ->assertViewHas('monthlyRevenueCurrentYear', array_fill(0, 12, 0.0));

        $this->actingAs($this->admin)->get('/admin/overview/fill-rate')
            ->assertSuccessful()
            ->assertViewHas('occupiedPercent', 0)
            ->assertViewHas('availablePercent', 0)
            ->assertViewHas('maintenancePercent', 0);
    }

    public function test_revenue_uses_only_successful_payments_and_groups_by_payment_date(): void
    {
        $invoice = $this->createInvoice('INV-REVENUE', 1000, Invoice::STATUS_PARTIAL, 1, 2026);
        $this->createPayment($invoice, 100, '2025-12-15', Payment::STATUS_SUCCESS);
        $this->createPayment($invoice, 200, '2026-01-20', Payment::STATUS_SUCCESS);
        $this->createPayment($invoice, 300, '2026-08-10', Payment::STATUS_SUCCESS);
        $this->createPayment($invoice, 900, '2026-08-10', Payment::STATUS_PENDING);
        $this->createPayment($invoice, 800, '2026-08-10', Payment::STATUS_FAILED);

        $this->actingAs($this->admin)->get('/admin/overview')
            ->assertSuccessful()
            ->assertViewHas('totalReceivable', 400.0)
            ->assertViewHas('todayRevenue', '300')
            ->assertViewHas('monthRevenue', '300')
            ->assertViewHas('monthlyRevenueCurrentYear', function (array $values) {
                return count($values) === 12 && $values[0] === 200.0 && $values[7] === 300.0
                    && array_sum($values) === 500.0;
            })
            ->assertViewHas('monthlyRevenuePreviousYear', function (array $values) {
                return $values[11] === 100.0 && array_sum($values) === 100.0;
            });

        $this->actingAs($this->admin)->get('/admin/overview/revenue-chart')
            ->assertSuccessful()
            ->assertViewHas('totalReceivable', 400.0);
    }

    public function test_invoice_status_counts_include_partial_invoice_as_outstanding(): void
    {
        $this->createInvoice('INV-UNPAID', 1000, Invoice::STATUS_UNPAID, 2);
        $this->createInvoice('INV-PARTIAL', 2000, Invoice::STATUS_PARTIAL, 3);
        $this->createInvoice('INV-PAID', 3000, Invoice::STATUS_PAID, 4);

        $this->actingAs($this->admin)->get('/admin/overview')
            ->assertSuccessful()
            // totalReceivable bao gồm cả unpaid + partial + paid (không có payment nào ở test này)
            ->assertViewHas('totalReceivable', 6000.0)
            ->assertSee('Tổng tiền công nợ');
    }

    public function test_room_status_and_fill_rate_are_consistent_across_pages(): void
    {
        $this->room->update(['status' => Room::STATUS_OCCUPIED]);
        $this->createRoom('ROOM-AVAILABLE', Room::STATUS_AVAILABLE);
        $this->createRoom('ROOM-MAINTENANCE', Room::STATUS_MAINTENANCE);
        $this->createRoom('ROOM-OCCUPIED-2', Room::STATUS_OCCUPIED);

        foreach (['/admin/overview', '/admin/overview/fill-rate'] as $url) {
            $this->actingAs($this->admin)->get($url)
                ->assertSuccessful()
                ->assertViewHas('totalRooms', 4)
                ->assertViewHas('occupiedRooms', 2)
                ->assertViewHas('availableRooms', 1)
                ->assertViewHas('maintenanceRooms', 1)
                ->assertViewHas('occupiedPercent', 50.0)
                ->assertViewHas('availablePercent', 25.0)
                ->assertViewHas('maintenancePercent', 25.0);
        }

        $this->actingAs($this->admin)->get('/admin/overview/room-stats')
            ->assertSuccessful()
            ->assertViewHas('totalRooms', 4)
            ->assertViewHas('occupiedRooms', 2)
            ->assertViewHas('availableRooms', 1)
            ->assertViewHas('maintenanceRooms', 1);
    }

    public function test_admin_home_monthly_revenue_uses_actual_successful_payment_date(): void
    {
        $julyInvoice = $this->createInvoice('INV-JULY', 1000, Invoice::STATUS_PARTIAL, 7);
        $augustInvoice = $this->createInvoice('INV-AUGUST', 5000, Invoice::STATUS_PAID, 8);
        $this->createPayment($julyInvoice, 400, '2026-08-10', Payment::STATUS_SUCCESS);
        $this->createPayment($augustInvoice, 5000, '2026-07-31', Payment::STATUS_SUCCESS);
        $this->createPayment($julyInvoice, 600, '2026-08-10', Payment::STATUS_PENDING);

        $this->actingAs($this->admin)->get('/admin')
            ->assertSuccessful()
            ->assertViewHas('stats', function (array $stats) {
                return (float) $stats['monthly_revenue'] === 400.0
                    && $stats['unpaid_invoices'] === 1;
            });
    }

    public function test_revenue_stats_never_show_negative_receivable_or_collection_above_one_hundred_percent(): void
    {
        $invoice = $this->createInvoice('INV-LEGACY-OVERPAY', 100, Invoice::STATUS_PAID, 5);
        $this->createPayment($invoice, 150, '2026-08-10', Payment::STATUS_SUCCESS);

        $this->actingAs($this->admin)->get('/admin/overview/revenue-stats')
            ->assertSuccessful()
            ->assertViewHas('totalReceivable', 0)
            ->assertViewHas('collectionRate', 100);
    }

    private function createUser(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return User::create([
            'name' => $roleName.' dashboard',
            'email' => $email,
            'phone' => '0963'.str_pad((string) User::count(), 6, '0', STR_PAD_LEFT),
            'role_id' => $role->id,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function createContractContext(): array
    {
        $client = $this->createUser('User', 'dashboard-tenant@example.test');
        $tenant = Tenant::create([
            'user_id' => $client->id,
            'full_name' => 'Khách dashboard',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'cccd' => 'DASHBOARD-CCCD',
            'phone' => $client->phone,
            'email' => $client->email,
            'address' => 'Hà Nội',
        ]);
        $room = $this->createRoom('ROOM-DASHBOARD', Room::STATUS_OCCUPIED);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-DASHBOARD',
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000,
            'deposit_amount' => 3000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => Contract::STATUS_ACTIVE,
        ]);

        return [$contract, $room];
    }

    private function createRoom(string $code, string $status): Room
    {
        return Room::create([
            'room_code' => $code,
            'floor' => 1,
            'price' => 3000000,
            'area' => 25,
            'status' => $status,
        ]);
    }

    private function createInvoice(
        string $code,
        float $total,
        string $status,
        int $month,
        int $year = 2026
    ): Invoice {
        return Invoice::create([
            'contract_id' => $this->contract->id,
            'room_id' => $this->room->id,
            'invoice_code' => $code,
            'month' => $month,
            'year' => $year,
            'invoice_date' => sprintf('%04d-%02d-01', $year, $month),
            'due_date' => sprintf('%04d-%02d-10', $year, $month),
            'room_fee' => $total,
            'electricity_fee' => 0,
            'water_fee' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
            'total_amount' => $total,
            'status' => $status,
        ]);
    }

    private function createPayment(Invoice $invoice, float $amount, string $date, string $status): Payment
    {
        return Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => $amount,
            'payment_date' => $date,
            'payment_method' => Payment::METHOD_CASH,
            'status' => $status,
        ]);
    }
}
