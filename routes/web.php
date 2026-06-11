<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [LoginController::class, 'dashboard'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);

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

    Route::get('/client', function () {
        $user = auth()->user();

        if ($user->role_id !== 2) {
            return redirect()->route('dashboard');
        }

        return view('layouts.client.home');
    })->name('client.home');
});
