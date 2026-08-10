<?php

// Admin routes
use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UtilityController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\ContractExtensionRequestController as AdminContractExtensionRequestController;
use App\Http\Controllers\Admin\ContractTerminationRequestController as AdminContractTerminationRequestController;


// Client routes
use App\Http\Controllers\Client\ContractController as ClientContractController;
use App\Http\Controllers\Client\InvoiceController as ClientInvoiceController;
use App\Http\Controllers\Client\ContractExtensionRequestController as ClientContractExtensionRequestController;
use App\Http\Controllers\Client\RequestHistoryController;
use App\Http\Controllers\Client\ContractTerminationRequestController as ClientContractTerminationRequestController;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;


// Tự động chuyển hướng về trang dashboard để kiểm tra đăng nhập
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Các route dành cho người chưa đăng nhập (Khách)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Route đăng xuất
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Các route BẮT BUỘC phải đăng nhập mới được vào
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [LoginController::class, 'dashboard'])->name('dashboard');

    // Nhóm route dành riêng cho Admin
    Route::prefix('admin')->name('admin.')->group(function () {

        Route::resource('users', UserController::class)->except(['show']);

        // Chức năng thêm phòng
        Route::get('rooms/export', [RoomController::class, 'exportForm'])
            ->name('rooms.export');
        Route::get('rooms/export/download', [RoomController::class, 'export'])
            ->name('rooms.export.download');
        Route::resource('rooms', RoomController::class);

        // Chức năng thêm sửa xoá khách thuê
        Route::get('tenants/export', [TenantController::class, 'exportForm'])
            ->name('tenants.export');
        Route::get('tenants/export/download', [TenantController::class, 'export'])
            ->name('tenants.export.download');
        Route::resource('tenants', TenantController::class);

        // =====================================================
        // QUẢN LÝ HỢP ĐỒNG THUÊ PHÒNG
        // =====================================================

        // 1. Kết thúc hợp đồng
        Route::post(
            'contracts/{contract}/terminate',
            [ContractController::class, 'end']
        )->name('contracts.terminate');


        // 2. Gia hạn hợp đồng
        Route::post(
            'contracts/{contract}/extend',
            [ContractController::class, 'extend']
        )->name('contracts.extend');


        // 3. Xử lý tiền cọc sau khi kết thúc hợp đồng
        Route::post(
            'contracts/{contract}/return-deposit',
            [ContractController::class, 'returnDeposit']
        )->name('contracts.return-deposit');


        // 4. In hợp đồng
        Route::get(
            'contracts/{id}/print',
            [ContractController::class, 'print']
        )->name('contracts.print');


        // =====================================================
        // KÝ HỢP ĐỒNG & KÍCH HOẠT
        // =====================================================

        // 5. Gửi hợp đồng cho khách thuê ký
        Route::post(
            'contracts/{contract}/send-signature',
            [ContractController::class, 'sendSignature']
        )->name('contracts.send-signature');


        // 6. Thu hồi yêu cầu ký
        Route::post(
            'contracts/{contract}/recall-signature',
            [ContractController::class, 'recallSignature']
        )->name('contracts.recall-signature');


        // 7. Xác nhận khách thuê đã ký
        Route::post(
            'contracts/{contract}/confirm-signature',
            [ContractController::class, 'confirmSignature']
        )->name('contracts.confirm-signature');


        // 8. Xác nhận đã đóng tiền cọc
        Route::post(
            'contracts/{contract}/confirm-deposit',
            [ContractController::class, 'confirmDeposit']
        )->name('contracts.confirm-deposit');


        // 9. Kích hoạt hợp đồng
        Route::post(
            'contracts/{contract}/activate',
            [ContractController::class, 'activate']
        )->name('contracts.activate');


        // =====================================================
        // MODAL CHI TIẾT HỢP ĐỒNG
        // =====================================================

        Route::get(
            'contracts/{contract}/modal',
            [ContractController::class, 'modal']
        )->name('contracts.modal');


        // =====================================================
        // YÊU CẦU GIA HẠN HỢP ĐỒNG
        // =====================================================

        // Danh sách yêu cầu gia hạn
        Route::get(
            'extension-requests',
            [AdminContractExtensionRequestController::class, 'index']
        )->name('extension-requests.index');


        // Duyệt yêu cầu gia hạn
        Route::post(
            'extension-requests/{extensionRequest}/approve',
            [AdminContractExtensionRequestController::class, 'approve']
        )->name('extension-requests.approve');


        // Từ chối yêu cầu gia hạn
        Route::post(
            'extension-requests/{extensionRequest}/reject',
            [AdminContractExtensionRequestController::class, 'reject']
        )->name('extension-requests.reject');

        // =====================================================
        // YÊU CẦU TRẢ PHÒNG
        // =====================================================

        Route::get(
            'termination-requests',
            [AdminContractTerminationRequestController::class, 'index']
        )->name('termination-requests.index');

        Route::post(
            'termination-requests/{terminationRequest}/approve',
            [AdminContractTerminationRequestController::class, 'approve']
        )->name('termination-requests.approve');

        Route::post(
            'termination-requests/{terminationRequest}/reject',
            [AdminContractTerminationRequestController::class, 'reject']
        )->name('termination-requests.reject');

        // =====================================================
        // RESOURCE HỢP ĐỒNG
        // Luôn đặt sau các route contracts cụ thể
        // =====================================================

        Route::resource('contracts', ContractController::class);
        //
        // Chức năng điện nước
        Route::get('/utilities/create', [UtilityController::class, 'create'])
            ->name('utilities.create');

        Route::post('/utilities/store', [UtilityController::class, 'store'])
            ->name('utilities.store');

        Route::get('/utilities', [UtilityController::class, 'index'])
            ->name('utilities.index');

        // Quản lý hóa đơn và công nợ
        // Các route cụ thể phải đặt TRƯỚC resource để tránh bị {invoice} chiếm
        Route::get('/invoices/generate', [InvoiceController::class, 'generate'])
            ->name('invoices.generate');

        Route::post('/invoices/generate', [InvoiceController::class, 'generateStore'])
            ->name('invoices.generate.store');

        Route::get('/invoices/export', [InvoiceController::class, 'exportForm'])
            ->name('invoices.export');

        Route::get('/invoices/export/download', [InvoiceController::class, 'export'])
            ->name('invoices.export.download');

        Route::get('/invoices/payments', [InvoiceController::class, 'payments'])
            ->name('invoices.payments');

        Route::get('/invoices/payments/export', [InvoiceController::class, 'exportPaymentsForm'])
            ->name('invoices.payments.export');

        Route::get('/invoices/payments/export/download', [InvoiceController::class, 'exportPayments'])
            ->name('invoices.payments.export.download');

        Route::get('/invoices/contracts/{contract}/preview', [InvoiceController::class, 'preview'])
            ->name('invoices.preview');

        Route::post('/invoices/contracts/{contract}/issue', [InvoiceController::class, 'issue'])
            ->name('invoices.issue');

        Route::post('/contracts/{contract}/deposit-invoice', [InvoiceController::class, 'createDepositInvoice'])
            ->name('contracts.deposit-invoice');

        Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])
            ->name('invoices.payments.store');

        Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])
            ->name('invoices.print');

        Route::resource('invoices', InvoiceController::class)
            ->except(['create', 'store']);

        // Tổng Quan Dashboard
        Route::get('/overview', [OverviewController::class, 'index'])
            ->name('overview');

        Route::get('/overview/revenue-chart', [OverviewController::class, 'revenueChart'])
            ->name('overview.revenue-chart');

        Route::get('/overview/revenue-stats', [OverviewController::class, 'revenueStats'])
            ->name('overview.revenue-stats');

        Route::get('/overview/room-stats', [OverviewController::class, 'roomStats'])
            ->name('overview.room-stats');

        Route::get('/overview/fill-rate', [OverviewController::class, 'fillRate'])
            ->name('overview.fill-rate');

        Route::get('/settings/{type}', [SettingController::class, 'edit'])
            ->where('type', 'electricity|water|internet|service')
            ->name('settings.edit');

        Route::put('/settings/{type}', [SettingController::class, 'update'])
            ->where('type', 'electricity|water|internet|service')
            ->name('settings.update');

        Route::get('/roles', function () {
            $user = auth()->user();

            if ($user->role_id !== 1) {
                return redirect()->route('dashboard');
            }

            $roles = Role::all();

            return view('admin.roles.index', compact('roles'));
        })->name('roles');

        Route::get('/', function () {
            $user = auth()->user();

            if ($user->role_id !== 1) {
                return redirect()->route('dashboard');
            }

            $currentMonth = now()->month;
            $currentYear = now()->year;

            $stats = [
                'total_rooms' => Room::count(),
                'available_rooms' => Room::where('status', 'available')->count(),
                'occupied_rooms' => Room::where('status', 'occupied')->count(),
                'maintenance_rooms' => Room::where('status', 'maintenance')->count(),
                'total_tenants' => Tenant::count(),
                'active_contracts' => Contract::where('status', 'active')->count(),
                'unpaid_invoices' => Invoice::whereIn('status', ['unpaid', 'partial'])->count(),
                'monthly_revenue' => Invoice::where('status', 'paid')
                    ->where('month', $currentMonth)
                    ->where('year', $currentYear)
                    ->sum('total_amount'),
            ];

            $recentInvoices = Invoice::with(['room', 'contract.tenant'])
                ->latest()
                ->take(5)
                ->get();

            $recentContracts = Contract::with(['room', 'tenant'])
                ->latest()
                ->take(5)
                ->get();

            return view('layouts.admin.home', compact('stats', 'recentInvoices', 'recentContracts'));
        })->name('home');
    });

    // Nhóm route dành cho Client (Người dùng thường)
    Route::get('/client', function () {
        $user = auth()->user()->load([
            'tenant.contracts.room',
            'tenant.contracts.invoices.room',
        ]);

        if ($user->role_id !== 2) {
            return redirect()->route('dashboard');
        }

        $tenant = $user->tenant;
        $activeContract = $tenant?->contracts
            ->where('status', 'active')
            ->sortByDesc('start_date')
            ->first();

        $invoices = $tenant
            ? Invoice::with(['room', 'contract'])
                ->whereHas('contract', function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id);
                })
                ->latest()
                ->get()
            : collect();

        $recentInvoice = $invoices->first();
        $openInvoices = $invoices->whereIn('status', ['unpaid', 'partial']);
        $supportRequests = 0;

        return view('layouts.client.home', compact(
            'tenant',
            'activeContract',
            'recentInvoice',
            'openInvoices',
            'supportRequests'
        ));
    })->name('client.home');

    Route::prefix('client')
    ->name('client.')
    ->group(function () {

        // ================================
        // HÓA ĐƠN CỦA KHÁCH THUÊ
        // ================================

        Route::get('/invoices', [
            ClientInvoiceController::class,
            'index'
        ])->name('invoices.index');

        Route::get('/invoices/{invoice}', [
            ClientInvoiceController::class,
            'show'
        ])->name('invoices.show');

        Route::get('/invoices/{invoice}/print', [
            ClientInvoiceController::class,
            'print'
        ])->name('invoices.print');

        Route::post('/invoices/{invoice}/payments', [
            ClientInvoiceController::class,
            'storePayment'
        ])->name('invoices.payments.store');

        // Hợp đồng của tôi
        Route::get('/contracts', [
            ClientContractController::class,
            'index'
        ])->name('contracts.index');
        
        // Chi tiết hợp đồng
        Route::get('/contracts/{contract}', [
            ClientContractController::class,
            'show'
        ])->name('contracts.show');

        // In hợp đồng của khách thuê
        Route::get('/contracts/{contract}/print', [
            ClientContractController::class,
            'print'
        ])->name('contracts.print');

        // Tải hợp đồng PDF
        Route::get('/contracts/{contract}/download', [
            ClientContractController::class,
            'download'
        ])->name('contracts.download');

        // Khách thuê ký hợp đồng
        Route::post('/contracts/{contract}/sign', [
            ClientContractController::class,
            'sign'
        ])->name('contracts.sign');

        // Khách thuê đăng ký ngày dự kiến nhận phòng
        Route::post('/contracts/{contract}/schedule-move-in', [
            ClientContractController::class,
            'scheduleMoveIn'
        ])->name('contracts.schedule-move-in');

        // Khách thuê xác nhận đã vào ở
        Route::post('/contracts/{contract}/confirm-move-in', [
            ClientContractController::class,
            'confirmMoveIn'
        ])->name('contracts.confirm-move-in');

        Route::get('/extension-requests', [ClientContractExtensionRequestController::class, 'index'])
            ->name('extension-requests.index');

        Route::post('/extension-requests', [ClientContractExtensionRequestController::class, 'store'])
            ->name('extension-requests.store');

        // ================================
        // YÊU CẦU TRẢ PHÒNG
        // ================================

        Route::get('/termination-requests', [
            ClientContractTerminationRequestController::class,
            'index'
        ])->name('termination-requests.index');

        Route::post('/termination-requests', [
            ClientContractTerminationRequestController::class,
            'store'
        ])->name('termination-requests.store');

        });
        // Lịch sử yêu cầu gia hạn và trả phòng
        Route::get('/request-history', [RequestHistoryController::class, 'index'])
            ->name('requests.history');
});