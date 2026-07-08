<?php
//Admin routes
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UtilityController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\ContractController;
//Client routes
use App\Http\Controllers\Client\ContractController as ClientContractController;

use App\Models\Role;
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
        // Các route cụ thể phải đặt TRƯỚC resource để tránh bị {invoice} chiếm
        Route::get('/invoices/generate', [InvoiceController::class, 'generate'])
            ->name('invoices.generate');

        Route::post('/invoices/generate', [InvoiceController::class, 'generate'])
            ->name('invoices.generate.store');

        Route::get('/invoices/payments', [InvoiceController::class, 'payments'])
            ->name('invoices.payments');

        Route::get('/invoices/contracts/{contract}/preview', [InvoiceController::class, 'preview'])
            ->name('invoices.preview');

        Route::post('/invoices/contracts/{contract}/issue', [InvoiceController::class, 'issue'])
            ->name('invoices.issue');

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

            return view('layouts.admin.home');
        })->name('home');
    });

    // Nhóm route dành cho Client (Người dùng thường)
    Route::get('/client', function () {
        $user = auth()->user();

        if ($user->role_id !== 2) {
            return redirect()->route('dashboard');
        }

        return view('layouts.client.home');
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
