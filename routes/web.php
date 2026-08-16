<?php

// Admin routes
use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\ContractOccupantController;
use App\Http\Controllers\Admin\ContractExtensionRequestController as AdminContractExtensionRequestController;
use App\Http\Controllers\Admin\ContractTerminationRequestController as AdminContractTerminationRequestController;
use App\Http\Controllers\Admin\DepositRefundController as AdminDepositRefundController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\RoomEvidenceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UtilityController;
use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Client\AccountController as ClientAccountController;
use App\Http\Controllers\Client\ContractController as ClientContractController;
use App\Http\Controllers\Client\ContractOccupantController as ClientContractOccupantController;
use App\Http\Controllers\Client\ContractExtensionRequestController as ClientContractExtensionRequestController;
use App\Http\Controllers\Client\ContractTerminationRequestController as ClientContractTerminationRequestController;
use App\Http\Controllers\Client\DepositRefundController as ClientDepositRefundController;
use App\Http\Controllers\Client\RequestHistoryController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\InvoiceController as ClientInvoiceController;
use App\Http\Controllers\Client\RoomController as ClientRoomController;
use App\Http\Controllers\Client\SupportController as ClientSupportController;
use App\Http\Controllers\Client\UtilityController as ClientUtilityController;
use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Models\ContractTerminationRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Services\ContractLifecycleService;
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
            Route::get('rooms/export', [RoomController::class, 'export'])
                ->name('rooms.export');
            Route::post('rooms/{room}/evidence', [RoomEvidenceController::class, 'store'])
                ->name('rooms.evidence.store');
            Route::resource('rooms', RoomController::class);

            // Chức năng thêm sửa xoá khách thuê
            Route::get('tenants/export', [TenantController::class, 'export'])
                ->name('tenants.export');
            Route::resource('tenants', TenantController::class);

            // Quản lý hợp đồng thuê phòng

            Route::view('contracts/template', 'admin.contracts.template')
                ->name('contracts.template');
            Route::view('contracts/template/print', 'admin.contracts.template-print')
                ->name('contracts.template.print');

            // Danh sách kết thúc hợp đồng (ĐẶT TRƯỚC resource)
            Route::get('contracts/end', [ContractController::class, 'endList'])
                ->name('contracts.end.list');

            // Xử lý kết thúc hợp đồng
            Route::post('contracts/{id}/end', [ContractController::class, 'end'])
                ->name('contracts.end');

            // Form kết thúc hợp đồng
            Route::get('contracts/{id}/end-form', [ContractController::class, 'endForm'])->name('contracts.end.form');

            // Danh sách gia hạn hợp đồng
            Route::get('contracts/extend', [ContractController::class, 'extendList'])
                ->name('contracts.extend.list');

            // Form gia hạn
            Route::get('contracts/{id}/extend-form', [ContractController::class, 'extendForm'])
                ->name('contracts.extend.form');

            // Xử lý gia hạn
            Route::post('contracts/{id}/extend', [ContractController::class, 'extend'])
                ->name('contracts.extend');

            // In hợp đồng
            Route::get('contracts/{id}/print', [ContractController::class, 'print'])
                ->name('contracts.print');
            Route::get('contracts/{contract}/file', [ContractController::class, 'file'])
                ->name('contracts.file');

            Route::post('contracts/{contract}/deposit-invoice', [ContractController::class, 'issueDepositInvoice'])
                ->name('contracts.deposit-invoice.issue');

            Route::post('contracts/{contract}/submit-for-signature', [ContractController::class, 'submitForSignature'])
                ->name('contracts.submit-for-signature');
            Route::post('contracts/{contract}/return-to-draft', [ContractController::class, 'returnToDraft'])
                ->name('contracts.return-to-draft');
            Route::post('contracts/{contract}/mark-signed', [ContractController::class, 'markAsSigned'])
                ->name('contracts.mark-signed');
            Route::post('contracts/{contract}/check-in', [ContractController::class, 'checkIn'])
                ->name('contracts.check-in');
            Route::post('contracts/{contract}/extend-move-in-deadline', [ContractController::class, 'extendMoveInDeadline'])
                ->name('contracts.extend-move-in-deadline');
            Route::post('contracts/{contract}/cancel', [ContractController::class, 'cancel'])
                ->name('contracts.cancel');
            Route::post('contracts/{contract}/check-out', [ContractController::class, 'checkOut'])
                ->name('contracts.check-out');
            Route::post('contracts/{contract}/complete-settlement', [ContractController::class, 'completeSettlement'])
                ->name('contracts.complete-settlement');
            Route::post('contract-occupants/{occupant}/approve', [ContractOccupantController::class, 'approve'])
                ->name('contract-occupants.approve');
            Route::post('contract-occupants/{occupant}/reject', [ContractOccupantController::class, 'reject'])
                ->name('contract-occupants.reject');
            Route::post('contract-occupants/{occupant}/move-out', [ContractOccupantController::class, 'moveOut'])
                ->name('contract-occupants.move-out');
            Route::get('contract-occupants/{occupant}/identity/{side}', [ContractController::class, 'identityDocument'])
                ->whereIn('side', ['front', 'back'])
                ->name('contract-occupants.identity-document');

            // Resource phải đặt SAU CÙNG
            Route::resource('contracts', ContractController::class)->except(['destroy']);

            Route::get('deposit-refunds', [AdminDepositRefundController::class, 'index'])->name('deposit-refunds.index');
            Route::post('deposit-refunds/{contract}/approve', [AdminDepositRefundController::class, 'approve'])->name('deposit-refunds.approve');
            Route::post('deposit-refunds/{contract}/complete', [AdminDepositRefundController::class, 'complete'])->name('deposit-refunds.complete');
            Route::post('deposit-refunds/{contract}/reject', [AdminDepositRefundController::class, 'reject'])->name('deposit-refunds.reject');
            Route::get('deposit-refunds/{contract}/qr', [AdminDepositRefundController::class, 'qr'])->name('deposit-refunds.qr');
            Route::get('deposit-refunds/{contract}/proof', [AdminDepositRefundController::class, 'proof'])->name('deposit-refunds.proof');

            Route::get('extension-requests', [AdminContractExtensionRequestController::class, 'index'])->name('extension-requests.index');
            Route::post('extension-requests/{extensionRequest}/approve', [AdminContractExtensionRequestController::class, 'approve'])->name('extension-requests.approve');
            Route::post('extension-requests/{extensionRequest}/reject', [AdminContractExtensionRequestController::class, 'reject'])->name('extension-requests.reject');
            Route::get('termination-requests', [AdminContractTerminationRequestController::class, 'index'])->name('termination-requests.index');
            Route::post('termination-requests/{terminationRequest}/approve', [AdminContractTerminationRequestController::class, 'approve'])->name('termination-requests.approve');
            Route::post('termination-requests/{terminationRequest}/reject', [AdminContractTerminationRequestController::class, 'reject'])->name('termination-requests.reject');
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
                ->where('type', 'fees|property-payment|electricity|water|internet|service|parking|bank|property')
                ->name('settings.edit');

            Route::put('/settings/{type}', [SettingController::class, 'update'])
                ->where('type', 'fees|property-payment|electricity|water|internet|service|parking|bank|property')
                ->name('settings.update');

            Route::get('/support', [AdminSupportController::class, 'index'])->name('support.index');
            Route::get('/support/{supportRequest}/attachment', [AdminSupportController::class, 'attachment'])->name('support.attachment');
            Route::put('/support/{supportRequest}', [AdminSupportController::class, 'update'])->name('support.update');

            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

            Route::get('/roles', function () {
                $roles = Role::all();

                return view('admin.roles.index', compact('roles'));
            })->name('roles');

            Route::get('/', function () {
                // Scheduler vẫn là nguồn xử lý chính; lần mở dashboard này là fallback idempotent
                // để Admin luôn thấy cảnh báo mới trên chuông ngay cả khi local scheduler chưa chạy.
                app(ContractLifecycleService::class)->processDailyAlerts();
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

                $expiringContracts = Contract::whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED])
                    ->whereBetween('end_date', [today(), today()->addDays(30)])
                    ->with('room:id,room_code')
                    ->orderBy('end_date')
                    ->get(['id', 'contract_code', 'end_date', 'room_id']);

                $overdueInvoices = Invoice::whereIn('status', ['unpaid', 'partial'])
                    ->where('due_date', '<', today())
                    ->selectRaw('COUNT(*) as count, SUM(total_amount) as total_amount')
                    ->first();

                $pendingSupportCount = SupportRequest::where('status', 'new')->count();
                $pendingExtensionCount = ContractExtensionRequest::where('status', 'pending')->count();
                $pendingTerminationCount = ContractTerminationRequest::where('status', 'pending')->count();

                return view('layouts.admin.home', compact(
                    'stats', 'recentInvoices', 'recentContracts',
                    'expiringContracts', 'overdueInvoices',
                    'pendingSupportCount', 'pendingExtensionCount', 'pendingTerminationCount'
                ));
            })->name('home');
        });

        Route::prefix('client')->name('client.')->middleware('role:client')->group(function () {
            Route::get('/', [ClientDashboardController::class, 'index'])->name('home');
            Route::get('/invoices', [ClientInvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/invoices/{invoice}', [ClientInvoiceController::class, 'show'])->name('invoices.show');
            Route::get('/invoices/{invoice}/print', [ClientInvoiceController::class, 'print'])->name('invoices.print');
            Route::post('/invoices/{invoice}/payments', [ClientInvoiceController::class, 'storePayment'])->name('invoices.payments.store');
            Route::get('/utilities', [ClientUtilityController::class, 'index'])->middleware('rental.active')->name('utilities.index');
            Route::get('/utilities/{reading}/{type}-image', [ClientUtilityController::class, 'image'])->middleware('rental.active')->name('utilities.image');
            Route::get('/room', [ClientRoomController::class, 'show'])->middleware('rental.active')->name('room.show');
            Route::get('/contracts', [ClientContractController::class, 'index'])->name('contracts.index');
            Route::get('/contracts/{contract}', [ClientContractController::class, 'show'])->name('contracts.show');
            Route::get('/contracts/{contract}/file', [ClientContractController::class, 'file'])->name('contracts.file');
            Route::post('/contracts/{contract}/move-in-details/confirm', [ClientContractController::class, 'confirmMoveInDetails'])
                ->name('contracts.move-in-details.confirm');
            Route::post('/contracts/{contract}/occupants', [ClientContractOccupantController::class, 'store'])->name('contracts.occupants.store');
            Route::post('/contracts/{contract}/occupants/{occupant}/withdraw', [ClientContractOccupantController::class, 'withdraw'])->name('contracts.occupants.withdraw');
            Route::get('/deposit-refunds/{contract}', [ClientDepositRefundController::class, 'index'])->name('deposit-refunds.index');
            Route::post('/contracts/{contract}/deposit-refund', [ClientDepositRefundController::class, 'store'])->name('deposit-refunds.store');
            Route::get('/contracts/{contract}/deposit-refund/qr', [ClientDepositRefundController::class, 'qr'])->name('deposit-refunds.qr');
            Route::get('/contracts/{contract}/deposit-refund/proof', [ClientDepositRefundController::class, 'proof'])->name('deposit-refunds.proof');
            Route::get('/extension-requests', [ClientContractExtensionRequestController::class, 'index'])->name('extension-requests.index');
            Route::post('/extension-requests', [ClientContractExtensionRequestController::class, 'store'])->name('extension-requests.store');
            Route::get('/termination-requests', [ClientContractTerminationRequestController::class, 'index'])->name('termination-requests.index');
            Route::post('/termination-requests', [ClientContractTerminationRequestController::class, 'store'])->name('termination-requests.store');
            Route::get('/support', [ClientSupportController::class, 'index'])->middleware('rental.active')->name('support.index');
            Route::post('/support', [ClientSupportController::class, 'store'])->middleware('rental.active')->name('support.store');
            Route::get('/support/{supportRequest}/attachment', [ClientSupportController::class, 'attachment'])->middleware('rental.active')->name('support.attachment');
            Route::get('/account', [ClientAccountController::class, 'edit'])->name('account.edit');
            Route::put('/account', [ClientAccountController::class, 'update'])->name('account.update');
            Route::put('/account/password', [ClientAccountController::class, 'updatePassword'])->name('account.password.update');
            Route::get('/request-history', [RequestHistoryController::class, 'index'])->name('requests.history');
        });
    });
});
