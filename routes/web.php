<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin', function () {
    return view('layouts.admin.home');
});

Route::get('/client', function () {
    return view('layouts.client.home');
});
