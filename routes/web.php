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

        // Xử lý kết thúc hợp đồng
        Route::post('contracts/{contract}/terminate',[ContractController::class,'end'])
        ->name('contracts.terminate');

        // Xử lý gia hạn
        Route::post('contracts/{contract}/extend',[ContractController::class,'extend'])
        ->name('contracts.extend');
        // Xử lý hoàn cọc
        Route::post(
            'contracts/{contract}/return-deposit',
            [ContractController::class, 'returnDeposit']
        )->name('contracts.returnDeposit');

        // In hợp đồng
        Route::get('contracts/{id}/print', [ContractController::class, 'print'])
            ->name('contracts.print');
        // Gửi hợp đồng để ký điện tử
        Route::post('contracts/{contract}/send-signature',[ContractController::class, 'sendSignature'])
            ->name('contracts.send-signature');
        // Thu hồi hợp đồng để ký điện tử
        Route::post(
            'contracts/{contract}/recall-signature',
            [ContractController::class, 'recallSignature']
        )->name('contracts.recall-signature');
        // Xác nhận ký hợp đồng điện tử
        Route::post('contracts/{contract}/confirm-signature',[ContractController::class, 'confirmSignature'])
            ->name('contracts.confirm-signature');
        // Xác nhận đặt cọc
        Route::post('contracts/{contract}/confirm-deposit',[ContractController::class, 'confirmDeposit'])
            ->name('contracts.confirm-deposit');
        // Kích hoạt hợp đồng
        Route::post('contracts/{contract}/activate',[ContractController::class, 'activate'])
            ->name('contracts.activate');
        // Xem chi tiết hợp đồng trong modal
        Route::get(
            'contracts/{contract}/modal',
            [ContractController::class, 'modal']
        )->name('contracts.modal');
        // Xử lý hoàn cọc
        Route::post(
            'contracts/{contract}/return-deposit',
            [ContractController::class, 'returnDeposit']
        )->name('contracts.returnDeposit');
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

        Route::post('/invoices/generate', [InvoiceController::class, 'generate'])
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

        Route::resource('invoices', InvoiceController::class)
            ->except(['create', 'store']);

        Route::get('/invoices', [InvoiceController::class, 'index'])
            ->name('invoices.index');

        Route::get('/invoices/generate', [InvoiceController::class, 'generate'])
            ->name('invoices.generate');

        Route::get('/invoices/contracts/{contract}/preview', [InvoiceController::class, 'preview'])
            ->name('invoices.preview');

        Route::post('/invoices/contracts/{contract}/issue', [InvoiceController::class, 'issue'])
            ->name('invoices.issue');

        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])
            ->name('invoices.show');

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
