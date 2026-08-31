<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        Carbon::setTestNow('2026-08-20 10:00:00');

        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $userRole = Role::firstOrCreate(['role_name' => 'User']);

        $this->admin = User::create([
            'name' => 'Admin Chi Phí',
            'email' => 'admin-expense@example.test',
            'phone' => '0988111222',
            'role_id' => $adminRole->id,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->client = User::create([
            'name' => 'Khách Thuê',
            'email' => 'client-expense@example.test',
            'phone' => '0977111222',
            'role_id' => $userRole->id,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->room = Room::create([
            'room_code' => 'P101',
            'floor' => 1,
            'price' => 3500000,
            'area' => 25,
            'status' => Room::STATUS_OCCUPIED,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_expense_and_profit_loss_routes_require_authentication_and_admin_role(): void
    {
        $urls = [
            '/admin/expenses',
            '/admin/expenses/create',
            '/admin/profit-loss',
            '/admin/overview/profit-loss',
        ];

        // 1. Khách chưa đăng nhập bị redirect về login
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect('/login');
        }

        // 2. Tài khoản User không có quyền admin bị 403 Forbidden
        foreach ($urls as $url) {
            $this->actingAs($this->client)->get($url)->assertForbidden();
        }
    }

    public function test_admin_can_view_expenses_index_and_create_page(): void
    {
        Expense::create([
            'expense_code' => 'EXP-202608-0001',
            'category' => Expense::CATEGORY_ELECTRICITY,
            'title' => 'Nộp tiền điện EVN T8',
            'amount' => 2500000,
            'expense_date' => '2026-08-15',
            'payment_method' => Expense::METHOD_BANK_TRANSFER,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/expenses');
        $response->assertSuccessful();
        $response->assertSee('Nộp tiền điện EVN T8');
        $response->assertSee('EXP-202608-0001');

        $this->actingAs($this->admin)->get('/admin/expenses/create')
            ->assertSuccessful()
            ->assertSee('Lập phiếu chi mới');
    }

    public function test_admin_can_create_expense_with_or_without_receipt_image(): void
    {
        // 1. Tạo phiếu chi không có ảnh
        $response = $this->actingAs($this->admin)->post('/admin/expenses', [
            'category' => Expense::CATEGORY_MAINTENANCE,
            'title' => 'Thay vòi sen tắm phòng 101',
            'amount' => 350000,
            'expense_date' => '2026-08-18',
            'room_id' => $this->room->id,
            'payer_name' => 'Thợ nước Hoàng',
            'payment_method' => Expense::METHOD_CASH,
            'notes' => 'Vòi sen bị rò nước',
        ]);

        $response->assertRedirect('/admin/expenses');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('expenses', [
            'title' => 'Thay vòi sen tắm phòng 101',
            'category' => Expense::CATEGORY_MAINTENANCE,
            'amount' => '350000.00',
            'room_id' => $this->room->id,
            'payment_method' => Expense::METHOD_CASH,
        ]);

        // 2. Tạo phiếu chi kèm ảnh hóa đơn
        $file = UploadedFile::fake()->image('evn_receipt.jpg');
        $this->actingAs($this->admin)->post('/admin/expenses', [
            'category' => Expense::CATEGORY_ELECTRICITY,
            'title' => 'Hóa đơn tiền điện EVN T8',
            'amount' => 4500000,
            'expense_date' => '2026-08-20',
            'payer_name' => 'EVN Hà Nội',
            'payment_method' => Expense::METHOD_BANK_TRANSFER,
            'receipt_image' => $file,
        ])->assertRedirect('/admin/expenses');

        $expenseWithReceipt = Expense::where('title', 'Hóa đơn tiền điện EVN T8')->first();
        $this->assertNotNull($expenseWithReceipt);
        $this->assertNotNull($expenseWithReceipt->receipt_image);
        Storage::disk('local')->assertExists($expenseWithReceipt->receipt_image);

        // Kiểm tra xem/tải ảnh hóa đơn
        $this->actingAs($this->admin)->get('/admin/expenses/' . $expenseWithReceipt->id . '/receipt')
            ->assertSuccessful();
    }

    public function test_expense_creation_validates_required_fields_and_min_amount(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/expenses', [
            'category' => '',
            'title' => '',
            'amount' => 500, // < 1000
            'expense_date' => '',
        ]);

        $response->assertSessionHasErrors(['category', 'title', 'amount', 'expense_date']);
    }

    public function test_admin_can_update_expense_and_manage_receipt_image(): void
    {
        $initialFile = UploadedFile::fake()->image('old_receipt.jpg');
        $filePath = $initialFile->store('expenses', 'local');

        $expense = Expense::create([
            'expense_code' => 'EXP-202608-0002',
            'category' => Expense::CATEGORY_WATER,
            'title' => 'Tiền nước T8',
            'amount' => 1200000,
            'expense_date' => '2026-08-10',
            'payment_method' => Expense::METHOD_BANK_TRANSFER,
            'receipt_image' => $filePath,
            'created_by' => $this->admin->id,
        ]);

        // Cập nhật thông tin và số tiền
        $this->actingAs($this->admin)->get('/admin/expenses/' . $expense->id . '/edit')
            ->assertSuccessful()
            ->assertSee('EXP-202608-0002');

        $this->actingAs($this->admin)->put('/admin/expenses/' . $expense->id, [
            'category' => Expense::CATEGORY_WATER,
            'title' => 'Tiền nước T8 (Đã điều chỉnh)',
            'amount' => 1350000,
            'expense_date' => '2026-08-12',
            'payment_method' => Expense::METHOD_BANK_TRANSFER,
            'remove_receipt' => 1,
        ])->assertRedirect('/admin/expenses');

        $expense->refresh();
        $this->assertSame('Tiền nước T8 (Đã điều chỉnh)', $expense->title);
        $this->assertSame('1350000.00', $expense->amount);
        $this->assertNull($expense->receipt_image);
        Storage::disk('local')->assertMissing($filePath);
    }

    public function test_admin_can_delete_expense_and_its_receipt_file(): void
    {
        $file = UploadedFile::fake()->image('delete_me.jpg');
        $path = $file->store('expenses', 'local');

        $expense = Expense::create([
            'expense_code' => 'EXP-202608-0003',
            'category' => Expense::CATEGORY_CLEANING,
            'title' => 'Dọn dẹp hành lang',
            'amount' => 500000,
            'expense_date' => '2026-08-19',
            'payment_method' => Expense::METHOD_CASH,
            'receipt_image' => $path,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->delete('/admin/expenses/' . $expense->id)
            ->assertRedirect('/admin/expenses');

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_admin_can_view_profit_loss_report_and_calculates_correct_metrics(): void
    {
        $tenant = Tenant::create([
            'user_id' => $this->client->id,
            'full_name' => 'Khách Doanh Thu',
            'date_of_birth' => '1992-02-02',
            'gender' => 'female',
            'cccd' => '098765432111',
            'phone' => '0977111222',
            'email' => 'client-expense@example.test',
            'address' => 'Đà Nẵng',
        ]);

        $contract = \App\Models\Contract::query()->forceCreate([
            'contract_code' => 'HD-TEST-PL-01',
            'room_id' => $this->room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3500000,
            'deposit_amount' => 3500000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => \App\Models\Contract::STATUS_ACTIVE,
        ]);

        // Tạo hóa đơn & thanh toán thực tế (Thu = 5,000,000)
        $invoice = Invoice::create([
            'invoice_code' => 'INV-TEST-01',
            'room_id' => $this->room->id,
            'contract_id' => $contract->id,
            'month' => 8,
            'year' => 2026,
            'room_fee' => 3500000,
            'electricity_fee' => 800000,
            'water_fee' => 400000,
            'internet_fee' => 100000,
            'service_fee' => 200000,
            'total_amount' => 5000000,
            'status' => Invoice::STATUS_PAID,
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-05',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_code' => 'PAY-001',
            'amount_paid' => 5000000,
            'payment_date' => '2026-08-03',
            'status' => Payment::STATUS_SUCCESS,
            'payment_method' => 'bank_transfer',
        ]);

        // Tạo chi phí (Chi = 2,000,000 điện EVN + 500,000 sửa chữa = 2,500,000)
        Expense::create([
            'expense_code' => 'EXP-202608-0010',
            'category' => Expense::CATEGORY_ELECTRICITY,
            'title' => 'Nộp tiền điện EVN',
            'amount' => 2000000,
            'expense_date' => '2026-08-10',
            'payment_method' => Expense::METHOD_BANK_TRANSFER,
            'created_by' => $this->admin->id,
        ]);

        Expense::create([
            'expense_code' => 'EXP-202608-0011',
            'category' => Expense::CATEGORY_MAINTENANCE,
            'title' => 'Sửa điều hòa',
            'amount' => 500000,
            'expense_date' => '2026-08-12',
            'room_id' => $this->room->id,
            'payment_method' => Expense::METHOD_CASH,
            'created_by' => $this->admin->id,
        ]);

        // Truy cập báo cáo Thu - Chi & Lợi Nhuận
        $response = $this->actingAs($this->admin)->get('/admin/profit-loss?year=2026&month=8');
        $response->assertSuccessful();
        $response->assertViewHas('totalRevenue', 5000000.0);
        $response->assertViewHas('totalExpenses', 2500000.0);
        $response->assertViewHas('netProfit', 2500000.0);
        $response->assertViewHas('profitMargin', 50.0);

        // Kiểm tra đối soát điện nước
        $response->assertViewHas('elecInvoiced', 800000.0);
        $response->assertViewHas('elecPaidGov', 2000000.0);
        $response->assertViewHas('elecDiff', -1200000.0); // Bị hụt 1.2tr
    }

    public function test_admin_can_create_expense_linked_to_support_request_and_room(): void
    {
        $tenant = Tenant::create([
            'user_id' => $this->client->id,
            'full_name' => 'Nguyễn Văn A',
            'date_of_birth' => '1995-05-15',
            'gender' => 'male',
            'cccd' => '012345678999',
            'phone' => '0977111222',
            'email' => 'client-expense@example.test',
            'address' => 'Hà Nội',
        ]);

        $support = SupportRequest::create([
            'user_id' => $this->client->id,
            'tenant_id' => $tenant->id,
            'category' => 'repair',
            'subject' => 'Hỏng khóa cửa',
            'description' => 'Khóa cửa phòng 101 bị kẹt không mở được',
            'status' => SupportRequest::STATUS_IN_PROGRESS,
        ]);

        // Mở trang tạo chi phí từ sự cố
        $this->actingAs($this->admin)->get('/admin/expenses/create?support_request_id=' . $support->id . '&room_id=' . $this->room->id)
            ->assertSuccessful()
            ->assertSee('Liên kết với Yêu cầu sửa chữa #' . $support->id);

        // Lưu phiếu chi liên kết sự cố
        $this->actingAs($this->admin)->post('/admin/expenses', [
            'category' => Expense::CATEGORY_MAINTENANCE,
            'title' => 'Thay ổ khóa cửa P101',
            'amount' => 200000,
            'expense_date' => '2026-08-20',
            'room_id' => $this->room->id,
            'support_request_id' => $support->id,
            'payment_method' => Expense::METHOD_CASH,
        ])->assertRedirect('/admin/expenses');

        $this->assertDatabaseHas('expenses', [
            'support_request_id' => $support->id,
            'room_id' => $this->room->id,
            'amount' => '200000.00',
        ]);
    }
}
