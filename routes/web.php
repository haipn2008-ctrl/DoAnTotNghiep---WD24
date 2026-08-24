<?php

// =====================================================
// ADMIN CONTROLLERS
// =====================================================

use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\ContractTenantController;
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
use App\Http\Controllers\Admin\TemporaryResidenceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UtilityController;

// =====================================================
// AUTH CONTROLLERS
// =====================================================

use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\Auth\LoginController;

// =====================================================
// CLIENT CONTROLLERS
// =====================================================

use App\Http\Controllers\Client\AccountController as ClientAccountController;
use App\Http\Controllers\Client\ContractController as ClientContractController;
use App\Http\Controllers\Client\ContractTenantController as ClientContractTenantController;
use App\Http\Controllers\Client\ContractExtensionRequestController as ClientContractExtensionRequestController;
use App\Http\Controllers\Client\ContractTerminationRequestController as ClientContractTerminationRequestController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\DepositRefundController as ClientDepositRefundController;
use App\Http\Controllers\Client\InvoiceController as ClientInvoiceController;
use App\Http\Controllers\Client\RequestHistoryController;
use App\Http\Controllers\Client\RoomController as ClientRoomController;
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
        'showLoginForm'
    ])->name('login');

    Route::post('/login', [
        LoginController::class,
        'login'
    ]);
});


// =====================================================
// ĐĂNG XUẤT
// =====================================================

Route::post('/logout', [
    LoginController::class,
    'logout'
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
        'show'
    ])->name('account.activation.show');

    Route::get('/activate-account/{step}', [
        AccountActivationController::class,
        'show'
    ])->whereIn('step', ['personal', 'identity', 'contact', 'password'])
        ->name('account.activation.step.show');

    Route::post('/activate-account/{step}', [
        AccountActivationController::class,
        'store'
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
            'dashboard'
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

                Route::put('vehicles/{vehicle}/review', [TenantController::class, 'reviewVehicle'])
                    ->name('vehicles.review');


                // =================================================
                // HỢP ĐỒNG - TEMPLATE
                // =================================================

                Route::view(
                    'contracts/template',
                    'admin.contracts.template'
                )->name('contracts.template');

                Route::view(
                    'contracts/template/print',
                    'admin.contracts.template-print'
                )->name('contracts.template.print');


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

                Route::get(
                    'contract-tenants/{member}/identity/{side}',
                    [ContractController::class, 'identityDocument']
                )
                    ->whereIn('side', ['front', 'back'])
                    ->name('contract-tenants.identity-document');


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
                    'invoices/payments/{payment}/approve',
                    [InvoiceController::class, 'approvePayment']
                )->name('invoices.payments.approve');

                Route::post(
                    'invoices/payments/{payment}/reject',
                    [InvoiceController::class, 'rejectPayment']
                )->name('invoices.payments.reject');

                Route::get(
                    'invoices/{invoice}/print',
                    [InvoiceController::class, 'print']
                )->name('invoices.print');

                Route::resource(
                    'invoices',
                    InvoiceController::class
                )->except(['create', 'store']);


                // =================================================
                // QUẢN LÝ TẠM TRÚ
                // =================================================

                Route::resource(
                    'temporary_residences',
                    TemporaryResidenceController::class
                );

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

                        'total_rooms' =>
                            Room::count(),

                        'available_rooms' =>
                            Room::where(
                                'status',
                                'available'
                            )->count(),

                        'occupied_rooms' =>
                            Room::where(
                                'status',
                                'occupied'
                            )->count(),

                        'maintenance_rooms' =>
                            Room::where(
                                'status',
                                'maintenance'
                            )->count(),

                        'total_tenants' =>
                            Tenant::count(),

                        'active_contracts' =>
                            Contract::where(
                                'status',
                                'active'
                            )->count(),

                        'unpaid_invoices' =>
                            Invoice::whereIn(
                                'status',
                                [
                                    'unpaid',
                                    'partial'
                                ]
                            )->count(),

                        'monthly_revenue' =>
                            Payment::success()
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
                            'contract.tenant'
                        ])
                        ->latest()
                        ->take(5)
                        ->get();


                    $recentContracts =
                        Contract::with([
                            'room',
                            'tenant'
                        ])
                        ->latest()
                        ->take(5)
                        ->get();


                    $expiringContracts =
                        Contract::whereIn(
                            'status',
                            [
                                Contract::STATUS_ACTIVE,
                                Contract::STATUS_EXPIRED
                            ]
                        )
                        ->whereBetween(
                            'end_date',
                            [
                                today(),
                                today()->addMonthNoOverflow()
                            ]
                        )
                        ->with('room:id,room_code')
                        ->orderBy('end_date')
                        ->get([
                            'id',
                            'contract_code',
                            'end_date',
                            'room_id'
                        ]);


                    $overdueInvoices =
                        Invoice::whereIn(
                            'status',
                            [
                                'unpaid',
                                'partial'
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
            ->middleware('role:client')
            ->group(function () {

                // =============================================
                // DASHBOARD
                // =============================================

                Route::get(
                    '/',
                    [ClientDashboardController::class, 'index']
                )->name('home');


                // =============================================
                // HÓA ĐƠN
                // =============================================

                Route::get(
                    '/invoices',
                    [ClientInvoiceController::class, 'index']
                )->name('invoices.index');

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


                // =============================================
                // HỢP ĐỒNG
                // =============================================

                Route::get(
                    '/contracts',
                    [ClientContractController::class, 'index']
                )->name('contracts.index');

                Route::get(
                    '/contracts/{contract}',
                    [ClientContractController::class, 'show']
                )->name('contracts.show');

                Route::get(
                    '/contracts/{contract}/file',
                    [ClientContractController::class, 'file']
                )->name('contracts.file');

                Route::post(
                    '/contracts/{contract}/move-in-details/confirm',
                    [ClientContractController::class, 'confirmMoveInDetails']
                )->name('contracts.move-in-details.confirm');


                // =============================================
                // NGƯỜI THAM GIA HỢP ĐỒNG
                // =============================================

                Route::post(
                    '/contracts/{contract}/members',
                    [ClientContractTenantController::class, 'store']
                )->name('contracts.members.store');

                Route::post(
                    '/contracts/{contract}/members/{member}/withdraw',
                    [ClientContractTenantController::class, 'withdraw']
                )->name('contracts.members.withdraw');


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
                )->name('extension-requests.store');


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
                )->name('termination-requests.store');


                // =============================================
                // HỖ TRỢ
                // =============================================

                Route::get(
                    '/support',
                    [ClientSupportController::class, 'index']
                )
                    ->middleware('rental.active')
                    ->name('support.index');

                Route::post(
                    '/support',
                    [ClientSupportController::class, 'store']
                )
                    ->middleware('rental.active')
                    ->name('support.store');

                Route::get(
                    '/support/{supportRequest}/attachment',
                    [ClientSupportController::class, 'attachment']
                )
                    ->middleware('rental.active')
                    ->name('support.attachment');


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

                Route::put(
                    '/account/password',
                    [ClientAccountController::class, 'updatePassword']
                )->name('account.password.update');

                Route::get('/vehicles', [ClientVehicleController::class, 'index'])->name('vehicles.index');
                Route::post('/vehicles', [ClientVehicleController::class, 'store'])->name('vehicles.store');
                Route::put('/vehicles/{vehicle}', [ClientVehicleController::class, 'update'])->name('vehicles.update');
                Route::delete('/vehicles/{vehicle}', [ClientVehicleController::class, 'destroy'])->name('vehicles.destroy');


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
