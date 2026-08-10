<?php

// Admin routes
use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UtilityController;
use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\ContractExtensionRequestController as AdminContractExtensionRequestController;
use App\Http\Controllers\Admin\ContractTerminationRequestController as AdminContractTerminationRequestController;
use App\Http\Controllers\Admin\DepositRefundController as AdminDepositRefundController;


// Client routes
use App\Http\Controllers\Client\AccountController as ClientAccountController;
use App\Http\Controllers\Client\DepositRefundController as ClientDepositRefundController;
use App\Http\Controllers\Client\ContractController as ClientContractController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\InvoiceController as ClientInvoiceController;
use App\Http\Controllers\Client\RoomController as ClientRoomController;
use App\Http\Controllers\Client\SupportController as ClientSupportController;
use App\Http\Controllers\Client\UtilityController as ClientUtilityController;
use App\Http\Controllers\Client\ContractExtensionRequestController as ClientContractExtensionRequestController;
use App\Http\Controllers\Client\RequestHistoryController;
use App\Http\Controllers\Client\ContractTerminationRequestController as ClientContractTerminationRequestController;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
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
    Route::get('/activate-account', [AccountActivationController::class, 'show'])->name('account.activation.show');
    Route::post('/activate-account', [AccountActivationController::class, 'activate'])->name('account.activation.activate');

    Route::middleware('account.active')->group(function () {
        Route::get('/dashboard', [LoginController::class, 'dashboard'])->name('dashboard');

        // Nhóm route dành riêng cho Admin
        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {

            Route::resource('users', UserController::class)->except(['show']);

            // Chức năng thêm phòng
            Route::get('rooms/export', [RoomController::class, 'exportForm'])
                ->name('rooms.export');
            Route::get('rooms/export/download', [RoomController::class, 'export'])
                ->name('rooms.export.download');
            Route::resource('rooms', RoomController::class);

        // =====================================================
        // QUẢN LÝ HỢP ĐỒNG THUÊ PHÒNG
        // =====================================================

        Route::post(
            'contracts/{contract}/terminate',
            [ContractController::class, 'end']
        )->name('contracts.terminate');

        Route::post(
            'contracts/{contract}/extend',
            [ContractController::class, 'extend']
        )->name('contracts.extend');

        Route::get(
            'contracts/{id}/print',
            [ContractController::class, 'print']
        )->name('contracts.print');
        // Hoàn cọc
        Route::get(
            'deposit-refunds',
            [AdminDepositRefundController::class, 'index']
        )->name('deposit-refunds.index');

        Route::post(
            'deposit-refunds/{contract}/approve',
            [AdminDepositRefundController::class, 'approve']
        )->name('deposit-refunds.approve');

        Route::post(
            'deposit-refunds/{contract}/complete',
            [AdminDepositRefundController::class, 'complete']
        )->name('deposit-refunds.complete');

        Route::post(
            'deposit-refunds/{contract}/reject',
            [AdminDepositRefundController::class, 'reject']
        )->name('deposit-refunds.reject');

        Route::get(
            'deposit-refunds/{contract}/qr',
            [AdminDepositRefundController::class, 'qr']
        )->name('deposit-refunds.qr');

        Route::get(
            'deposit-refunds/{contract}/proof',
            [AdminDepositRefundController::class, 'proof']
        )->name('deposit-refunds.proof');

        Route::post(
            'contracts/{contract}/send-signature',
            [ContractController::class, 'sendSignature']
        )->name('contracts.send-signature');

        Route::post(
            'contracts/{contract}/recall-signature',
            [ContractController::class, 'recallSignature']
        )->name('contracts.recall-signature');

        Route::post(
            'contracts/{contract}/confirm-signature',
            [ContractController::class, 'confirmSignature']
        )->name('contracts.confirm-signature');

        Route::post(
            'contracts/{contract}/confirm-deposit',
            [ContractController::class, 'confirmDeposit']
        )->name('contracts.confirm-deposit');

        Route::post(
            'contracts/{contract}/activate',
            [ContractController::class, 'activate']
        )->name('contracts.activate');

        Route::get(
            'contracts/{contract}/modal',
            [ContractController::class, 'modal']
        )->name('contracts.modal');

        // Yêu cầu gia hạn
        Route::get(
            'extension-requests',
            [AdminContractExtensionRequestController::class, 'index']
        )->name('extension-requests.index');

        Route::post(
            'extension-requests/{extensionRequest}/approve',
            [AdminContractExtensionRequestController::class, 'approve']
        )->name('extension-requests.approve');

        Route::post(
            'extension-requests/{extensionRequest}/reject',
            [AdminContractExtensionRequestController::class, 'reject']
        )->name('extension-requests.reject');

        // Yêu cầu trả phòng
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

        // Khách thuê
        Route::get('tenants/export', [TenantController::class, 'exportForm'])
            ->name('tenants.export');
        Route::get('tenants/export/download', [TenantController::class, 'export'])
            ->name('tenants.export.download');
        Route::resource('tenants', TenantController::class);

        // Tải file hợp đồng
        Route::get('contracts/{contract}/file', [ContractController::class, 'file'])
            ->name('contracts.file');

        // Resource hợp đồng đặt sau các route cụ thể
        Route::resource('contracts', ContractController::class);
        //
            //
            // Chức năng điện nước
            Route::get('/utilities/create', [UtilityController::class, 'create'])
                ->name('utilities.create');

            Route::post('/utilities/store', [UtilityController::class, 'store'])
                ->name('utilities.store');

            Route::get('/utilities', [UtilityController::class, 'index'])
                ->name('utilities.index');
            Route::get('/utilities/{reading}/{type}-image', [UtilityController::class, 'image'])
                ->name('utilities.image');

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

            Route::post('/contracts/{contract}/deposit-invoice', [InvoiceController::class, 'createDepositInvoice'])
                ->name('contracts.deposit-invoice');

            Route::post('/invoices/contracts/{contract}/issue', [InvoiceController::class, 'issue'])
                ->name('invoices.issue');

            Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])
                ->name('invoices.payments.store');

            Route::post('/invoices/payments/{payment}/approve', [InvoiceController::class, 'approvePayment'])
                ->name('invoices.payments.approve');

            Route::post('/invoices/payments/{payment}/reject', [InvoiceController::class, 'rejectPayment'])
                ->name('invoices.payments.reject');

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

            Route::get('/support', [AdminSupportController::class, 'index'])->name('support.index');
            Route::get('/support/{supportRequest}/attachment', [AdminSupportController::class, 'attachment'])->name('support.attachment');
            Route::put('/support/{supportRequest}', [AdminSupportController::class, 'update'])->name('support.update');

            Route::get('/roles', function () {
                $roles = Role::all();

                return view('admin.roles.index', compact('roles'));
            })->name('roles');

            Route::get('/', function () {
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
                    'monthly_revenue' => Payment::success()
                        ->whereMonth('payment_date', $currentMonth)
                        ->whereYear('payment_date', $currentYear)
                        ->sum('amount_paid'),
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
        // Client portal
        Route::prefix('client')->name('client.')->middleware('role:client')->group(function () {
            Route::get('/', [ClientDashboardController::class, 'index'])->name('home');

            // Hóa đơn
            Route::get('/invoices', [ClientInvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/invoices/{invoice}', [ClientInvoiceController::class, 'show'])->name('invoices.show');
            Route::get('/invoices/{invoice}/print', [ClientInvoiceController::class, 'print'])->name('invoices.print');
            Route::post('/invoices/{invoice}/payments', [ClientInvoiceController::class, 'storePayment'])
                ->name('invoices.payments.store');

            // Điện nước
            Route::get('/utilities', [ClientUtilityController::class, 'index'])
                ->middleware('rental.active')->name('utilities.index');
            Route::get('/utilities/{reading}/{type}-image', [ClientUtilityController::class, 'image'])
                ->middleware('rental.active')->name('utilities.image');

            // Phòng
            Route::get('/room', [ClientRoomController::class, 'show'])
                ->middleware('rental.active')->name('room.show');

            // Hợp đồng
            Route::get('/contracts', [ClientContractController::class, 'index'])->name('contracts.index');
            Route::get('/contracts/{contract}', [ClientContractController::class, 'show'])->name('contracts.show');
            Route::get('/contracts/{contract}/file', [ClientContractController::class, 'file'])->name('contracts.file');
            Route::get('/contracts/{contract}/print', [ClientContractController::class, 'print'])->name('contracts.print');
            Route::get('/contracts/{contract}/download', [ClientContractController::class, 'download'])->name('contracts.download');
            Route::post('/contracts/{contract}/sign', [ClientContractController::class, 'sign'])->name('contracts.sign');
            Route::post('/contracts/{contract}/schedule-move-in', [ClientContractController::class, 'scheduleMoveIn'])
                ->name('contracts.schedule-move-in');
            Route::post('/contracts/{contract}/confirm-move-in', [ClientContractController::class, 'confirmMoveIn'])
                ->name('contracts.confirm-move-in');
            // Yêu cầu hoàn cọc
            Route::get(
                '/deposit-refunds/{contract}',
                [ClientDepositRefundController::class, 'index']
            )->name('deposit-refunds.index');

            Route::post(
                '/contracts/{contract}/deposit-refund',
                [ClientDepositRefundController::class, 'store']
            )->name('deposit-refunds.store');

            Route::get(
                '/contracts/{contract}/deposit-refund/qr',
                [ClientDepositRefundController::class, 'qr']
            )->name('deposit-refunds.qr');

            // Bằng chứng Admin đã chuyển tiền hoàn cọc
            Route::get(
                '/contracts/{contract}/deposit-refund/proof',
                [ClientDepositRefundController::class, 'proof']
            )->name('deposit-refunds.proof');

            // Yêu cầu gia hạn
            Route::get('/extension-requests', [ClientContractExtensionRequestController::class, 'index'])
                ->name('extension-requests.index');
            Route::post('/extension-requests', [ClientContractExtensionRequestController::class, 'store'])
                ->name('extension-requests.store');

            // Yêu cầu trả phòng
            Route::get('/termination-requests', [ClientContractTerminationRequestController::class, 'index'])
                ->name('termination-requests.index');
            Route::post('/termination-requests', [ClientContractTerminationRequestController::class, 'store'])
                ->name('termination-requests.store');

            // Hỗ trợ
            Route::get('/support', [ClientSupportController::class, 'index'])
                ->middleware('rental.active')->name('support.index');
            Route::post('/support', [ClientSupportController::class, 'store'])
                ->middleware('rental.active')->name('support.store');
            Route::get('/support/{supportRequest}/attachment', [ClientSupportController::class, 'attachment'])
                ->middleware('rental.active')->name('support.attachment');

            // Tài khoản
            Route::get('/account', [ClientAccountController::class, 'edit'])->name('account.edit');
            Route::put('/account', [ClientAccountController::class, 'update'])->name('account.update');
            Route::put('/account/password', [ClientAccountController::class, 'updatePassword'])
                ->name('account.password.update');

            // Lịch sử yêu cầu
            Route::get('/request-history', [RequestHistoryController::class, 'index'])
                ->name('requests.history');
        });
    });
});