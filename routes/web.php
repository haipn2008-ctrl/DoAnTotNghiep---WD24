<?php

// =====================================================
// ADMIN CONTROLLERS
// =====================================================

use App\Http\Controllers\Admin\ContractAppendixController as AdminContractAppendixController;
use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\ContractExtensionRequestController as AdminContractExtensionRequestController;
use App\Http\Controllers\Admin\ContractTenantController;
use App\Http\Controllers\Admin\ContractTerminationRequestController as AdminContractTerminationRequestController;
use App\Http\Controllers\Admin\DebtController;
use App\Http\Controllers\Admin\DepositRefundController as AdminDepositRefundController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\ProfitLossController;
use App\Http\Controllers\Admin\ReconciliationController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\RoomEvidenceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\TemporaryResidenceController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UtilityController;
// =====================================================
// AUTH CONTROLLERS
// =====================================================

use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
// =====================================================
// CLIENT CONTROLLERS
// =====================================================

use App\Http\Controllers\Client\AccountController as ClientAccountController;
use App\Http\Controllers\Client\ContractAppendixController as ClientContractAppendixController;
use App\Http\Controllers\Client\ContractController as ClientContractController;
use App\Http\Controllers\Client\ContractExtensionRequestController as ClientContractExtensionRequestController;
use App\Http\Controllers\Client\ContractTenantController as ClientContractTenantController;
use App\Http\Controllers\Client\ContractTerminationRequestController as ClientContractTerminationRequestController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\DepositRefundController as ClientDepositRefundController;
use App\Http\Controllers\Client\InvoiceController as ClientInvoiceController;
use App\Http\Controllers\Client\LandlordInformationController as ClientLandlordInformationController;
use App\Http\Controllers\Client\NotificationController as ClientNotificationController;
use App\Http\Controllers\Client\RequestHistoryController;
use App\Http\Controllers\Client\RoomController as ClientRoomController;
use App\Http\Controllers\Client\SettlementController as ClientSettlementController;
use App\Http\Controllers\Client\SupportController as ClientSupportController;
use App\Http\Controllers\Client\UtilityController as ClientUtilityController;
use App\Http\Controllers\Client\VehicleController as ClientVehicleController;
// =====================================================
// MODELS
// =====================================================

use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Models\ContractTerminationRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\SupportRequest;
use App\Models\Tenant;
// =====================================================
// SERVICES
// =====================================================

use App\Services\ContractLifecycleService;
// =====================================================
// ROUTE
// =====================================================

use Illuminate\Support\Facades\Route;

// =====================================================
// TRANG CHỦ
// =====================================================

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// =====================================================
// AUTH - KHÁCH CHƯA ĐĂNG NHẬP
// =====================================================

Route::middleware('guest')->group(function () {

    Route::get('/login', [
        LoginController::class,
        'showLoginForm',
    ])->name('login');

    Route::post('/login', [
        LoginController::class,
        'login',
    ]);

    Route::get('/forgot-password', [
        ForgotPasswordController::class,
        'showLinkRequestForm',
    ])->name('password.request');

    Route::post('/forgot-password', [
        ForgotPasswordController::class,
        'sendResetLink',
    ])->middleware('throttle:5,1')->name('password.email');

    Route::get('/reset-password', [
        ResetPasswordController::class,
        'showResetForm',
    ])->name('password.reset');

    Route::post('/reset-password', [
        ResetPasswordController::class,
        'reset',
    ])->middleware('throttle:5,1')->name('password.update');
});

// =====================================================
// ĐĂNG XUẤT
// =====================================================

Route::post('/logout', [
    LoginController::class,
    'logout',
])->name('logout');

// =====================================================
// ROUTE BẮT BUỘC ĐĂNG NHẬP
// =====================================================

Route::middleware('auth')->group(function () {

    // =================================================
    // KÍCH HOẠT TÀI KHOẢN
    // =================================================

    Route::get('/activate-account', [
        AccountActivationController::class,
        'show',
    ])->name('account.activation.show');

    Route::get('/activate-account/{step}', [
        AccountActivationController::class,
        'show',
    ])->whereIn('step', ['personal', 'identity', 'contact', 'password'])
        ->name('account.activation.step.show');

    Route::post('/activate-account/{step}', [
        AccountActivationController::class,
        'store',
    ])->whereIn('step', ['personal', 'identity', 'contact', 'password'])
        ->name('account.activation.step.store');

    // =================================================
    // TÀI KHOẢN ĐÃ ACTIVE
    // =================================================

    Route::middleware('account.active')->group(function () {

        // =================================================
        // DASHBOARD CHUNG
        // =================================================

        Route::get('/dashboard', [
            LoginController::class,
            'dashboard',
        ])->name('dashboard');

        // =================================================
        // ADMIN
        // =================================================

        Route::prefix('admin')
            ->name('admin.')
            ->middleware('role:admin')
            ->group(function () {

                // =================================================
                // USER
                // =================================================

                Route::resource('users', UserController::class)
                    ->except(['show']);

                Route::patch('users/{user}/restore', [UserController::class, 'restore'])
                    ->name('users.restore');

                // =================================================
                // PHÒNG
                // =================================================

                Route::get(
                    'rooms/export',
                    [RoomController::class, 'export']
                )->name('rooms.export');

                Route::post(
                    'rooms/{room}/evidence',
                    [RoomEvidenceController::class, 'store']
                )->name('rooms.evidence.store');

                Route::patch('rooms/{room}/retire', [RoomController::class, 'retire'])
                    ->name('rooms.retire');

                Route::patch('rooms/{room}/restore', [RoomController::class, 'restore'])
                    ->name('rooms.restore');

                Route::resource(
                    'rooms',
                    RoomController::class
                );

                // =================================================
                // KHÁCH THUÊ
                // =================================================

                Route::get(
                    'tenants/export',
                    [TenantController::class, 'export']
                )->name('tenants.export');

                Route::resource(
                    'tenants',
                    TenantController::class
                )->only(['index', 'show', 'edit', 'update', 'destroy']);

                Route::patch('tenants/{tenant}/restore', [TenantController::class, 'restore'])
                    ->name('tenants.restore');

                Route::put('vehicles/{vehicle}/review', [TenantController::class, 'reviewVehicle'])
                    ->name('vehicles.review');

                Route::get('vehicles/{vehicle}/image', [TenantController::class, 'vehicleImage'])
                    ->name('vehicles.image');

                // =================================================
                // HỢP ĐỒNG - TEMPLATE
                // =================================================

                Route::get('contracts/template', [ContractController::class, 'template'])
                    ->name('contracts.template');

                Route::post('contracts/template', [ContractController::class, 'storeTemplate'])
                    ->name('contracts.template.store');

                Route::get('contracts/template/print', [ContractController::class, 'templatePrint'])
                    ->name('contracts.template.print');

                Route::get('contracts/template/{contractTemplate}', [ContractController::class, 'showTemplate'])
                    ->name('contracts.template.show');

                // =================================================
                // HỢP ĐỒNG - KẾT THÚC
                // =================================================

                Route::get(
                    'contracts/end',
                    [ContractController::class, 'endList']
                )->name('contracts.end.list');

                Route::post(
                    'contracts/{id}/end',
                    [ContractController::class, 'end']
                )->name('contracts.end');

                Route::get(
                    'contracts/{id}/end-form',
                    [ContractController::class, 'endForm']
                )->name('contracts.end.form');

                Route::post(
                    'contracts/{contract}/terminate',
                    [ContractController::class, 'end']
                )->name('contracts.terminate');

                // =================================================
                // HỢP ĐỒNG - GIA HẠN
                // =================================================

                Route::get(
                    'contracts/extend',
                    [ContractController::class, 'extendList']
                )->name('contracts.extend.list');

                Route::get(
                    'contracts/{id}/extend-form',
                    [ContractController::class, 'extendForm']
                )->name('contracts.extend.form');

                Route::post(
                    'contracts/{id}/extend',
                    [ContractController::class, 'extend']
                )->name('contracts.extend');

                // =================================================
                // HỢP ĐỒNG - IN / FILE
                // =================================================

                Route::get(
                    'contracts/{id}/print',
                    [ContractController::class, 'print']
                )->name('contracts.print');

                Route::get(
                    'contracts/{contract}/file',
                    [ContractController::class, 'file']
                )->name('contracts.file');

                Route::get('contracts/{contract}/appendices/create', [AdminContractAppendixController::class, 'create'])
                    ->name('contracts.appendices.create');
                Route::post('contracts/{contract}/appendices', [AdminContractAppendixController::class, 'store'])
                    ->name('contracts.appendices.store');
                Route::get('contract-appendices/{appendix}', [AdminContractAppendixController::class, 'show'])
                    ->name('contract-appendices.show');
                Route::get('contract-appendices/{appendix}/edit', [AdminContractAppendixController::class, 'edit'])
                    ->name('contract-appendices.edit');
                Route::put('contract-appendices/{appendix}', [AdminContractAppendixController::class, 'update'])
                    ->name('contract-appendices.update');
                Route::post('contract-appendices/{appendix}/send', [AdminContractAppendixController::class, 'send'])
                    ->name('contract-appendices.send');
                Route::post('contract-appendices/{appendix}/revise', [AdminContractAppendixController::class, 'revise'])
                    ->name('contract-appendices.revise');

                Route::get('contracts/{contract}/checkout-photos/{index}', [ContractController::class, 'checkoutPhoto'])
                    ->whereNumber('index')->name('contracts.checkout-photos.show');

                // =================================================
                // HỢP ĐỒNG - TIỀN CỌC
                // =================================================

                Route::post(
                    'contracts/{contract}/deposit-invoice',
                    [ContractController::class, 'issueDepositInvoice']
                )->name('contracts.deposit-invoice.issue');

                // =================================================
                // HỢP ĐỒNG - KÝ
                // =================================================

                Route::post(
                    'contracts/{contract}/submit-for-signature',
                    [ContractController::class, 'submitForSignature']
                )->name('contracts.submit-for-signature');

                Route::post(
                    'contracts/{contract}/return-to-draft',
                    [ContractController::class, 'returnToDraft']
                )->name('contracts.return-to-draft');

                Route::post(
                    'contracts/{contract}/mark-signed',
                    [ContractController::class, 'markAsSigned']
                )->name('contracts.mark-signed');

                // =================================================
                // HỢP ĐỒNG - CHECK IN / CHECK OUT
                // =================================================

                Route::post(
                    'contracts/{contract}/handover-reading',
                    [ContractController::class, 'saveHandoverReading']
                )->name('contracts.handover-reading.store');

                Route::post(
                    'contracts/{contract}/move-in-details/reopen',
                    [ContractController::class, 'reopenMoveInDetails']
                )->name('contracts.move-in-details.reopen');

                Route::post(
                    'contracts/{contract}/check-in',
                    [ContractController::class, 'checkIn']
                )->name('contracts.check-in');

                Route::post(
                    'contracts/{contract}/extend-move-in-deadline',
                    [ContractController::class, 'extendMoveInDeadline']
                )->name('contracts.extend-move-in-deadline');

                Route::post(
                    'contracts/{contract}/cancel',
                    [ContractController::class, 'cancel']
                )->name('contracts.cancel');

                Route::get(
                    'contracts/{contract}/check-out',
                    [ContractController::class, 'checkOutForm']
                )->name('contracts.check-out.form');

                Route::post(
                    'contracts/{contract}/check-out',
                    [ContractController::class, 'checkOut']
                )->name('contracts.check-out');

                Route::post(
                    'contracts/{contract}/complete-settlement',
                    [ContractController::class, 'completeSettlement']
                )->name('contracts.complete-settlement');

                // =================================================
                // NGƯỜI THAM GIA HỢP ĐỒNG
                // =================================================

                Route::post(
                    'contract-tenants/{member}/approve',
                    [ContractTenantController::class, 'approve']
                )->name('contract-tenants.approve');

                Route::post(
                    'contract-tenants/{member}/reject',
                    [ContractTenantController::class, 'reject']
                )->name('contract-tenants.reject');

                Route::post(
                    'contract-tenants/{member}/move-out',
                    [ContractTenantController::class, 'moveOut']
                )->name('contract-tenants.move-out');

                Route::post(
                    'contract-tenants/{member}/transfer-representative',
                    [ContractTenantController::class, 'transferRepresentative']
                )->name('contract-tenants.transfer-representative');

                Route::get(
                    'representative-transfers/{transfer}/appendix',
                    [ContractTenantController::class, 'transferAppendix']
                )->name('representative-transfers.appendix');

                Route::get(
                    'contract-tenants/{member}/identity/{side}',
                    [ContractController::class, 'identityDocument']
                )
                    ->whereIn('side', ['front', 'back'])
                    ->name('contract-tenants.identity-document');

                Route::get(
                    'tenants/{tenant}/identity/{side}',
                    [ContractController::class, 'tenantIdentityDocument']
                )
                    ->whereIn('side', ['front', 'back'])
                    ->name('tenants.identity-document');

                // =================================================
                // RESOURCE HỢP ĐỒNG
                // Phải đặt SAU các route custom
                // =================================================

                Route::resource(
                    'contracts',
                    ContractController::class
                )->except(['destroy']);

                // =================================================
                // HOÀN CỌC
                // =================================================

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

                // =================================================
                // YÊU CẦU GIA HẠN HỢP ĐỒNG
                // =================================================

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

                // =================================================
                // YÊU CẦU TRẢ PHÒNG
                // =================================================

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

                // =================================================
                // ĐIỆN NƯỚC
                // =================================================

                Route::get(
                    'utilities/create',
                    [UtilityController::class, 'create']
                )->name('utilities.create');

                Route::post(
                    'utilities/store',
                    [UtilityController::class, 'store']
                )->name('utilities.store');

                Route::post(
                    'utilities/{reading}/confirm',
                    [UtilityController::class, 'confirm']
                )->name('utilities.confirm');

                Route::post(
                    'utilities/{reading}/reopen',
                    [UtilityController::class, 'reopen']
                )->name('utilities.reopen');

                Route::get(
                    'utilities',
                    [UtilityController::class, 'index']
                )->name('utilities.index');

                Route::get(
                    'utilities/{reading}/{type}-image',
                    [UtilityController::class, 'image']
                )->name('utilities.image');

                // =================================================
                // HÓA ĐƠN
                // =================================================

                Route::get('debts', [DebtController::class, 'index'])
                    ->name('debts.index');

                Route::get('debts/{invoice}', [DebtController::class, 'show'])
                    ->name('debts.show');

                Route::post('debts/{invoice}/reminders', [DebtController::class, 'storeReminder'])
                    ->name('debts.reminders.store');

                Route::post('payment-delay-requests/{delayRequest}/approve', [DebtController::class, 'approveDelayRequest'])
                    ->name('payment-delay-requests.approve');

                Route::post('payment-delay-requests/{delayRequest}/reject', [DebtController::class, 'rejectDelayRequest'])
                    ->name('payment-delay-requests.reject');

                Route::get(
                    'invoices/generate',
                    [InvoiceController::class, 'generate']
                )->name('invoices.generate');

                Route::post(
                    'invoices/generate',
                    [InvoiceController::class, 'generateStore']
                )->name('invoices.generate.store');

                Route::get(
                    'invoices/export',
                    [InvoiceController::class, 'exportForm']
                )->name('invoices.export');

                Route::get(
                    'invoices/export/download',
                    [InvoiceController::class, 'export']
                )->name('invoices.export.download');

                Route::get(
                    'invoices/payments',
                    [InvoiceController::class, 'payments']
                )->name('invoices.payments');

                Route::get(
                    'invoices/payments/export',
                    [InvoiceController::class, 'exportPaymentsForm']
                )->name('invoices.payments.export');

                Route::get(
                    'invoices/payments/export/download',
                    [InvoiceController::class, 'exportPayments']
                )->name('invoices.payments.export.download');

                Route::get(
                    'invoices/contracts/{contract}/preview',
                    [InvoiceController::class, 'preview']
                )->name('invoices.preview');

                Route::post(
                    'invoices/contracts/{contract}/issue',
                    [InvoiceController::class, 'issue']
                )->name('invoices.issue');

                Route::post(
                    'invoices/{invoice}/payments',
                    [InvoiceController::class, 'storePayment']
                )->name('invoices.payments.store');

                Route::post(
                    'invoices/{invoice}/cancel',
                    [InvoiceController::class, 'cancel']
                )->name('invoices.cancel');

                Route::post(
                    'invoices/{invoice}/adjustments',
                    [InvoiceController::class, 'storeAdjustment']
                )->name('invoices.adjustments.store');

                Route::post(
                    'invoices/payments/{payment}/approve',
                    [InvoiceController::class, 'approvePayment']
                )->name('invoices.payments.approve');

                Route::post(
                    'invoices/payments/{payment}/reject',
                    [InvoiceController::class, 'rejectPayment']
                )->name('invoices.payments.reject');

                Route::get(
                    'invoices/payments/{payment}/proof',
                    [InvoiceController::class, 'paymentProof']
                )->name('invoices.payments.proof');

                Route::get(
                    'invoices/{invoice}/print',
                    [InvoiceController::class, 'print']
                )->name('invoices.print');

                Route::resource(
                    'invoices',
                    InvoiceController::class
                )->except(['create', 'store', 'destroy']);

                // =================================================
                // QUẢN LÝ TẠM TRÚ
                // =================================================

                Route::patch('temporary_residences/{temporary_residence}/cancel', [TemporaryResidenceController::class, 'cancel'])
                    ->name('temporary_residences.cancel');

                Route::resource(
                    'temporary_residences',
                    TemporaryResidenceController::class
                )->except(['destroy']);

                Route::post(
                    'temporary-residences/{temporaryResidence}/sign',
                    [TemporaryResidenceController::class, 'sign']
                )->name('temporary_residences.sign');

                Route::get(
                    'temporary-residences/{temporaryResidence}/pdf',
                    [TemporaryResidenceController::class, 'pdf']
                )->name('temporary_residences.pdf');

                // =================================================
                // TỔNG QUAN
                // =================================================

                Route::get(
                    'overview',
                    [OverviewController::class, 'index']
                )->name('overview');

                Route::get(
                    'reconciliation',
                    [ReconciliationController::class, 'index']
                )->name('reconciliation.index');

                Route::get(
                    'profit-loss',
                    [ProfitLossController::class, 'index']
                )->name('profit-loss.index');

                Route::get(
                    'overview/profit-loss',
                    [ProfitLossController::class, 'index']
                )->name('overview.profit-loss');

                Route::get(
                    'overview/revenue-chart',
                    [OverviewController::class, 'revenueChart']
                )->name('overview.revenue-chart');

                Route::get(
                    'overview/revenue-stats',
                    [OverviewController::class, 'revenueStats']
                )->name('overview.revenue-stats');

                Route::get(
                    'overview/room-stats',
                    [OverviewController::class, 'roomStats']
                )->name('overview.room-stats');

                Route::get(
                    'overview/fill-rate',
                    [OverviewController::class, 'fillRate']
                )->name('overview.fill-rate');

                // =================================================
                // QUẢN LÝ CHI PHÍ (THU CHI)
                // =================================================

                Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])
                    ->name('expenses.receipt');

                Route::resource('expenses', ExpenseController::class);

                // =================================================
                // CÀI ĐẶT
                // =================================================

                Route::get(
                    'settings/{type}',
                    [SettingController::class, 'edit']
                )
                    ->where(
                        'type',
                        'fees|property-payment|electricity|water|internet|service|parking|bank|property'
                    )
                    ->name('settings.edit');

                Route::put(
                    'settings/{type}',
                    [SettingController::class, 'update']
                )
                    ->where(
                        'type',
                        'fees|property-payment|electricity|water|internet|service|parking|bank|property'
                    )
                    ->name('settings.update');

                // =================================================
                // THÔNG BÁO
                // =================================================

                Route::get(
                    'notifications',
                    [NotificationController::class, 'index']
                )->name('notifications.index');
                Route::get('notifications/{notification}', [NotificationController::class, 'open'])
                    ->name('notifications.open');

                // =================================================
                // HỖ TRỢ
                // =================================================

                Route::get(
                    'support',
                    [AdminSupportController::class, 'index']
                )->name('support.index');

                Route::get(
                    'support/{supportRequest}/attachment',
                    [AdminSupportController::class, 'attachment']
                )->name('support.attachment');

                Route::put(
                    'support/{supportRequest}',
                    [AdminSupportController::class, 'update']
                )->name('support.update');

                // =================================================
                // VAI TRÒ
                // =================================================

                Route::get(
                    'roles',
                    function () {

                        $roles = Role::all();

                        return view(
                            'admin.roles.index',
                            compact('roles')
                        );
                    }
                )->name('roles');

                // =================================================
                // ADMIN HOME
                // =================================================

                Route::get('/', function () {

                    // Scheduler vẫn là nguồn xử lý chính.
                    // Lần mở dashboard là fallback idempotent
                    // để Admin luôn thấy cảnh báo mới.

                    app(ContractLifecycleService::class)
                        ->processDailyAlerts();

                    $currentMonth = now()->month;
                    $currentYear = now()->year;

                    $stats = [

                        'total_rooms' => Room::where('status', '!=', Room::STATUS_RETIRED)->count(),

                        'available_rooms' => Room::where(
                            'status',
                            'available'
                        )->count(),

                        'occupied_rooms' => Room::where(
                            'status',
                            'occupied'
                        )->count(),

                        'maintenance_rooms' => Room::where(
                            'status',
                            'maintenance'
                        )->count(),

                        'total_tenants' => Tenant::active()->count(),

                        'active_contracts' => Contract::where(
                            'status',
                            'active'
                        )->count(),

                        'unpaid_invoices' => Invoice::whereIn(
                            'status',
                            [
                                'unpaid',
                                'partial',
                            ]
                        )->count(),

                        'monthly_revenue' => Payment::success()
                            ->whereMonth(
                                'payment_date',
                                $currentMonth
                            )
                            ->whereYear(
                                'payment_date',
                                $currentYear
                            )
                            ->sum('amount_paid'),
                    ];

                    $recentInvoices =
                        Invoice::with([
                            'room',
                            'contract.tenant',
                        ])
                            ->latest()
                            ->take(5)
                            ->get();

                    $recentContracts =
                        Contract::with([
                            'room',
                            'tenant',
                        ])
                            ->latest()
                            ->take(5)
                            ->get();

                    $expiringContracts =
                        Contract::whereIn(
                            'status',
                            [
                                Contract::STATUS_ACTIVE,
                                Contract::STATUS_EXPIRED,
                            ]
                        )
                            ->whereBetween(
                                'end_date',
                                [
                                    today(),
                                    today()->addMonthNoOverflow(),
                                ]
                            )
                            ->with('room:id,room_code')
                            ->orderBy('end_date')
                            ->get([
                                'id',
                                'contract_code',
                                'end_date',
                                'room_id',
                            ]);

                    $overdueInvoices =
                        Invoice::whereIn(
                            'status',
                            [
                                'unpaid',
                                'partial',
                            ]
                        )
                            ->where(
                                'due_date',
                                '<',
                                today()
                            )
                            ->selectRaw(
                                'COUNT(*) as count, SUM(total_amount) as total_amount'
                            )
                            ->first();

                    $pendingSupportCount =
                        SupportRequest::where(
                            'status',
                            'new'
                        )->count();

                    $pendingExtensionCount =
                        ContractExtensionRequest::where(
                            'status',
                            'pending'
                        )->count();

                    $pendingTerminationCount =
                        ContractTerminationRequest::where(
                            'status',
                            'pending'
                        )->count();

                    return view(
                        'layouts.admin.home',
                        compact(
                            'stats',
                            'recentInvoices',
                            'recentContracts',
                            'expiringContracts',
                            'overdueInvoices',
                            'pendingSupportCount',
                            'pendingExtensionCount',
                            'pendingTerminationCount'
                        )
                    );

                })->name('home');

            });

        // =================================================
        // CLIENT PORTAL
        // =================================================

        Route::prefix('client')
            ->name('client.')
            ->middleware('role:user')
            ->group(function () {

                // =============================================
                // DASHBOARD
                // =============================================

                Route::get(
                    '/',
                    [ClientDashboardController::class, 'index']
                )->name('home');

                Route::get(
                    '/settlement',
                    [ClientSettlementController::class, 'index']
                )->name('settlement.index');

                // =============================================
                // HÓA ĐƠN
                // =============================================

                Route::get(
                    '/invoices',
                    [ClientInvoiceController::class, 'index']
                )->name('invoices.index');

                Route::get('/notifications', [ClientNotificationController::class, 'index'])
                    ->name('notifications.index');
                Route::get('/notifications/{notification}', [ClientNotificationController::class, 'open'])
                    ->name('notifications.open');
                Route::post('/notifications/read-all', [ClientNotificationController::class, 'markAllAsRead'])
                    ->name('notifications.read-all');

                Route::get(
                    '/invoices/{invoice}',
                    [ClientInvoiceController::class, 'show']
                )->name('invoices.show');

                Route::get(
                    '/invoices/{invoice}/print',
                    [ClientInvoiceController::class, 'print']
                )->name('invoices.print');

                Route::post(
                    '/invoices/{invoice}/payments',
                    [ClientInvoiceController::class, 'storePayment']
                )->name('invoices.payments.store');

                Route::post(
                    '/invoices/{invoice}/payment-delay-request',
                    [ClientInvoiceController::class, 'storePaymentDelayRequest']
                )->name('invoices.payment-delay-request.store');

                Route::get(
                    '/payments/{payment}/proof',
                    [ClientInvoiceController::class, 'paymentProof']
                )->name('invoices.payments.proof');

                // =============================================
                // ĐIỆN NƯỚC
                // =============================================

                Route::get(
                    '/utilities',
                    [ClientUtilityController::class, 'index']
                )
                    ->middleware('rental.active')
                    ->name('utilities.index');

                Route::get(
                    '/utilities/{reading}/{type}-image',
                    [ClientUtilityController::class, 'image']
                )
                    ->middleware('rental.active')
                    ->name('utilities.image');

                // =============================================
                // PHÒNG
                // =============================================

                Route::get(
                    '/room',
                    [ClientRoomController::class, 'show']
                )
                    ->middleware('rental.active')
                    ->name('room.show');

                Route::get(
                    '/room/members',
                    [ClientRoomController::class, 'members']
                )
                    ->middleware('rental.active')
                    ->name('room.members.index');

                Route::get(
                    '/room/members/{member}',
                    [ClientRoomController::class, 'member']
                )
                    ->whereNumber('member')
                    ->middleware('rental.active')
                    ->name('room.members.show');

                Route::put(
                    '/room/members/{member}',
                    [ClientRoomController::class, 'updateMember']
                )
                    ->whereNumber('member')
                    ->middleware('rental.active')
                    ->name('room.members.update');

                Route::get(
                    '/room/members/{member}/identity/{side}',
                    [ClientRoomController::class, 'memberIdentity']
                )
                    ->whereNumber('member')
                    ->whereIn('side', ['front', 'back'])
                    ->middleware('rental.active')
                    ->name('room.members.identity');

                // =============================================
                // HỢP ĐỒNG
                // =============================================

                Route::get(
                    '/contracts',
                    [ClientContractController::class, 'index']
                )->name('contracts.index');

                Route::get('/contract-appendices/{appendix}', [ClientContractAppendixController::class, 'show'])
                    ->name('contract-appendices.show');
                Route::post('/contract-appendices/{appendix}/accept', [ClientContractAppendixController::class, 'accept'])
                    ->name('contract-appendices.accept');
                Route::post('/contract-appendices/{appendix}/reject', [ClientContractAppendixController::class, 'reject'])
                    ->name('contract-appendices.reject');

                Route::get(
                    '/contracts/{contract}',
                    [ClientContractController::class, 'show']
                )->name('contracts.show');

                Route::get(
                    '/contracts/{contract}/file',
                    [ClientContractController::class, 'file']
                )->name('contracts.file');

                Route::get(
                    '/contracts/{contract}/handover-meter/{type}',
                    [ClientContractController::class, 'handoverMeterImage']
                )->whereIn('type', ['electricity', 'water'])
                    ->name('contracts.handover-meter-image');

                Route::get('/contracts/{contract}/checkout-photos/{index}', [ClientContractController::class, 'checkoutPhoto'])
                    ->whereNumber('index')->name('contracts.checkout-photos.show');

                Route::post(
                    '/contracts/{contract}/move-in-details/confirm',
                    [ClientContractController::class, 'confirmMoveInDetails']
                )->middleware('rental.active')->name('contracts.move-in-details.confirm');

                // =============================================
                // NGƯỜI THAM GIA HỢP ĐỒNG
                // =============================================

                Route::get(
                    '/contracts/{contract}/tenants/create',
                    [ClientContractTenantController::class, 'create']
                )->middleware('rental.active')->name('contracts.tenants.create');

                Route::post(
                    '/contracts/{contract}/members',
                    [ClientContractTenantController::class, 'store']
                )->middleware('rental.active')->name('contracts.members.store');

                Route::post(
                    '/contracts/{contract}/members/{member}/withdraw',
                    [ClientContractTenantController::class, 'withdraw']
                )->middleware('rental.active')->name('contracts.members.withdraw');

                // =============================================
                // HOÀN CỌC
                // =============================================

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

                Route::get(
                    '/contracts/{contract}/deposit-refund/proof',
                    [ClientDepositRefundController::class, 'proof']
                )->name('deposit-refunds.proof');

                // =============================================
                // YÊU CẦU GIA HẠN
                // =============================================

                Route::get(
                    '/extension-requests',
                    [ClientContractExtensionRequestController::class, 'index']
                )->name('extension-requests.index');

                Route::post(
                    '/extension-requests',
                    [ClientContractExtensionRequestController::class, 'store']
                )->middleware('rental.active')->name('extension-requests.store');

                Route::post(
                    '/extension-requests/{extensionRequest}/accept',
                    [ClientContractExtensionRequestController::class, 'accept']
                )->middleware('rental.active')->name('extension-requests.accept');

                Route::post(
                    '/extension-requests/{extensionRequest}/decline',
                    [ClientContractExtensionRequestController::class, 'decline']
                )->middleware('rental.active')->name('extension-requests.decline');

                // =============================================
                // YÊU CẦU TRẢ PHÒNG
                // =============================================

                Route::get(
                    '/termination-requests',
                    [ClientContractTerminationRequestController::class, 'index']
                )->name('termination-requests.index');

                Route::post(
                    '/termination-requests',
                    [ClientContractTerminationRequestController::class, 'store']
                )->middleware('rental.active')->name('termination-requests.store');

                // =============================================
                // HỖ TRỢ
                // =============================================

                Route::get(
                    '/support',
                    [ClientSupportController::class, 'index']
                )
                    ->name('support.index');

                Route::post(
                    '/support',
                    [ClientSupportController::class, 'store']
                )
                    ->name('support.store');

                Route::get(
                    '/support/{supportRequest}/attachment',
                    [ClientSupportController::class, 'attachment']
                )
                    ->name('support.attachment');

                Route::get(
                    '/landlord-information',
                    ClientLandlordInformationController::class
                )->name('landlord-information');

                // =============================================
                // TÀI KHOẢN
                // =============================================

                Route::get(
                    '/account',
                    [ClientAccountController::class, 'edit']
                )->name('account.edit');

                Route::put(
                    '/account',
                    [ClientAccountController::class, 'update']
                )->name('account.update');

                Route::get(
                    '/account/identity/{side}',
                    [ClientAccountController::class, 'identityDocument']
                )->whereIn('side', ['front', 'back'])->name('account.identity-document');

                Route::put(
                    '/account/password',
                    [ClientAccountController::class, 'updatePassword']
                )->name('account.password.update');

                Route::get('/vehicles', [ClientVehicleController::class, 'index'])->middleware('rental.active')->name('vehicles.index');
                Route::post('/vehicles', [ClientVehicleController::class, 'store'])->middleware('rental.active')->name('vehicles.store');
                Route::get('/vehicles/{vehicle}/image', [ClientVehicleController::class, 'image'])->middleware('rental.active')->name('vehicles.image');
                Route::put('/vehicles/{vehicle}', [ClientVehicleController::class, 'update'])->middleware('rental.active')->name('vehicles.update');
                Route::delete('/vehicles/{vehicle}', [ClientVehicleController::class, 'destroy'])->middleware('rental.active')->name('vehicles.destroy');
                Route::patch('/vehicles/{vehicle}/restore', [ClientVehicleController::class, 'restore'])->middleware('rental.active')->name('vehicles.restore');

                // =============================================
                // LỊCH SỬ YÊU CẦU
                // =============================================

                Route::get(
                    '/request-history',
                    [RequestHistoryController::class, 'index']
                )->name('requests.history');

            });

    });

});
