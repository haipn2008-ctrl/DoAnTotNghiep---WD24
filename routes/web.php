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
// Client routes
use App\Http\Controllers\Client\ContractController as ClientContractController;
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

        // Quản lý hợp đồng thuê phòng

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

        // Resource phải đặt SAU CÙNG
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
        Route::get('/invoices/generate', [InvoiceController::class, 'generateForm'])
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

        Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])
            ->name('invoices.payments.store');

        Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])
            ->name('invoices.print');

        Route::get('/invoices/contracts/{contract}/preview', [InvoiceController::class, 'preview'])
            ->name('invoices.preview');

        Route::post('/invoices/contracts/{contract}/issue', [InvoiceController::class, 'issue'])
            ->name('invoices.issue');

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

    // Route::prefix('client')
    // ->name('client.')
    // ->middleware(['auth'])
    // ->group(function () {

    //     Route::get('/contract',
    //         [ClientContractController::class, 'show'])
    //         ->name('contract.show');

    // });
});
