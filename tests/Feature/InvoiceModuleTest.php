<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_can_be_generated_once_and_payment_updates_status(): void
    {
        $role = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'phone' => '0123456789',
            'role_id' => $role->id,
            'password' => bcrypt('password'),
        ]);

        $room = Room::create([
            'room_code' => 'P101',
            'floor' => 1,
            'price' => 3000000,
            'area' => 25,
            'max_people' => 2,
            'current_people' => 1,
            'status' => 'occupied',
        ]);

        $tenant = Tenant::create([
            'user_id' => $admin->id,
            'full_name' => 'Nguyễn Văn A',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'cccd' => '123456789',
            'cccd_issue_date' => '2020-01-01',
            'cccd_issue_place' => 'Hà Nội',
            'phone' => '0900000000',
            'email' => 'tenant@example.com',
            'address' => 'Hà Nội',
        ]);

        $contract = Contract::create([
            'contract_code' => 'HD001',
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000,
            'deposit_amount' => 6000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        Setting::create([
            'electric_price' => 3500,
            'water_price' => 38000,
            'internet_fee' => 100000,
            'service_fee' => 50000,
        ]);

        UtilityReading::create([
            'room_id' => $room->id,
            'month' => 7,
            'year' => 2026,
            'record_date' => '2026-07-01',
            'electricity_old' => 100,
            'electricity_new' => 120,
            'water_old' => 50,
            'water_new' => 60,
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin);

        $response = $this->post('/admin/invoices/generate', [
            'month' => 7,
            'year' => 2026,
            'contract_id' => $contract->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('invoices', 1);

        $invoice = Invoice::first();
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertGreaterThan(0, $invoice->total_amount);

        $paymentResponse = $this->post('/admin/invoices/' . $invoice->id . '/payments', [
            'amount_paid' => 2000000,
            'payment_date' => '2026-07-10',
            'payment_method' => 'cash',
            'note' => 'Thanh toán một phần',
        ]);

        $paymentResponse->assertRedirect();
        $this->assertDatabaseCount('payments', 1);

        $invoice->refresh();
        $this->assertEquals('partial', $invoice->status);
    }

    public function test_invoice_export_downloads_csv_file(): void
    {
        $role = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'name' => 'Admin Export',
            'email' => 'admin-export@example.com',
            'phone' => '0123456780',
            'role_id' => $role->id,
            'password' => bcrypt('password'),
        ]);

        $room = Room::create([
            'room_code' => 'P102',
            'floor' => 1,
            'price' => 3500000,
            'area' => 30,
            'max_people' => 2,
            'current_people' => 1,
            'status' => 'occupied',
        ]);

        $tenant = Tenant::create([
            'user_id' => $admin->id,
            'full_name' => 'Nguyễn Văn B',
            'date_of_birth' => '1991-01-01',
            'gender' => 'male',
            'cccd' => '987654321',
            'cccd_issue_date' => '2020-01-01',
            'cccd_issue_place' => 'Hà Nội',
            'phone' => '0900000001',
            'email' => 'tenant-b@example.com',
            'address' => 'Hà Nội',
        ]);

        $contract = Contract::create([
            'contract_code' => 'HD002',
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3500000,
            'deposit_amount' => 7000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        Invoice::create([
            'invoice_code' => 'INV-20260701',
            'contract_id' => $contract->id,
            'room_id' => $room->id,
            'month' => 7,
            'year' => 2026,
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-15',
            'room_fee' => 3500000,
            'electricity_fee' => 0,
            'water_fee' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
            'total_amount' => 3500000,
            'status' => 'unpaid',
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/invoices/export/download');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertSeeText('Mã hóa đơn');
    }

    public function test_payment_export_downloads_csv_file(): void
    {
        $role = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'name' => 'Admin Payment',
            'email' => 'admin-payment@example.com',
            'phone' => '0123456781',
            'role_id' => $role->id,
            'password' => bcrypt('password'),
        ]);

        $room = Room::create([
            'room_code' => 'P103',
            'floor' => 2,
            'price' => 4000000,
            'area' => 35,
            'max_people' => 2,
            'current_people' => 1,
            'status' => 'occupied',
        ]);

        $tenant = Tenant::create([
            'user_id' => $admin->id,
            'full_name' => 'Nguyễn Văn C',
            'date_of_birth' => '1992-01-01',
            'gender' => 'male',
            'cccd' => '111222333',
            'cccd_issue_date' => '2020-01-01',
            'cccd_issue_place' => 'Hà Nội',
            'phone' => '0900000002',
            'email' => 'tenant-c@example.com',
            'address' => 'Hà Nội',
        ]);

        $contract = Contract::create([
            'contract_code' => 'HD003',
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 4000000,
            'deposit_amount' => 8000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'invoice_code' => 'INV-20260702',
            'contract_id' => $contract->id,
            'room_id' => $room->id,
            'month' => 7,
            'year' => 2026,
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-15',
            'room_fee' => 4000000,
            'electricity_fee' => 0,
            'water_fee' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
            'total_amount' => 4000000,
            'status' => 'unpaid',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 2000000,
            'payment_date' => '2026-07-10',
            'payment_method' => Payment::METHOD_CASH,
            'transaction_code' => 'TXN-001',
            'status' => Payment::STATUS_SUCCESS,
            'confirmed_by' => $admin->id,
            'note' => 'Thanh toán thử',
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/invoices/payments/export/download');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertSeeText('Mã giao dịch');
    }
}
