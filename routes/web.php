<?php

use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UtilityController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\ContractController;
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
        Route::resource('tenants', TenantController::class);

        // Quản lý hợp đồng thuê phòng
        Route::resource('contracts', ContractController::class);

        // In hợp đồng
        Route::get('contracts/{id}/print', [ContractController::class, 'print'])
            ->name('contracts.print');

        // Kết thúc hợp đồng
        Route::post('contracts/{id}/end', [ContractController::class, 'end'])
            ->name('contracts.end');

        // Chức năng điện nước
        Route::get('/utilities/create', [UtilityController::class, 'create'])
            ->name('utilities.create');

        Route::post('/utilities/store', [UtilityController::class, 'store'])
            ->name('utilities.store');

        Route::get('/utilities', [UtilityController::class, 'index'])
            ->name('utilities.index');

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
});
