<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitLossOverviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Contract $contract;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-15 09:00:00');
        $this->withoutVite();

        $this->admin = $this->createUser('Admin', 'profit-loss-admin@example.test');
        [$this->contract, $this->room] = $this->createContractContext();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_profit_loss_page_contains_expand_buttons_and_detail_rows(): void
    {
        $invoice = $this->createInvoice('INV-PL-01', 3200000, Invoice::STATUS_PARTIAL, 8, 2026);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 1500000,
            'payment_date' => '2026-08-10',
            'payment_method' => Payment::METHOD_CASH,
            'transaction_code' => 'GD-PL-001',
            'status' => Payment::STATUS_SUCCESS,
        ]);

        Expense::create([
            'expense_code' => 'EXP-202608-0001',
            'category' => Expense::CATEGORY_ELECTRICITY,
            'title' => 'Thanh toán điện EVN',
            'amount' => 900000,
            'expense_date' => '2026-08-11',
            'payment_method' => Expense::METHOD_BANK_TRANSFER,
            'payer_name' => 'Admin',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/profit-loss?month=8&year=2026');

        $response->assertSuccessful()
            ->assertSee('kpiRevenueDetail', false)
            ->assertSee('kpiExpenseDetail', false)
            ->assertSee('toggleKpiDetail', false)
            ->assertSee('GD-PL-001')
            ->assertSee('INV-PL-01')
            ->assertSee('EXP-202608-0001')
            ->assertSee('Thanh toán điện EVN');
    }

    private function createUser(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return User::create([
            'name' => $roleName.' profit loss',
            'email' => $email,
            'phone' => '0977'.str_pad((string) User::count(), 6, '0', STR_PAD_LEFT),
            'role_id' => $role->id,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function createContractContext(): array
    {
        $client = $this->createUser('User', 'profit-loss-tenant@example.test');

        $tenant = Tenant::create([
            'user_id' => $client->id,
            'full_name' => 'Khach Thu Chi',
            'date_of_birth' => '1993-05-01',
            'gender' => 'female',
            'cccd' => 'PL-CCCD-001',
            'phone' => $client->phone,
            'email' => $client->email,
            'address' => 'Da Nang',
        ]);

        $room = Room::create([
            'room_code' => 'PL-101',
            'floor' => 1,
            'price' => 3200000,
            'area' => 24,
            'status' => Room::STATUS_OCCUPIED,
        ]);

        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-PL-001',
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3200000,
            'deposit_amount' => 3200000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => Contract::STATUS_ACTIVE,
        ]);

        return [$contract, $room];
    }

    private function createInvoice(
        string $code,
        float $total,
        string $status,
        int $month,
        int $year
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
}
