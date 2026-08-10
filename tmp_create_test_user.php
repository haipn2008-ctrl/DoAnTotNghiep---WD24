<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = 'testtenant@example.com';
$user = App\Models\User::firstOrCreate(
    ['email' => $email],
    [
        'name' => 'Test Tenant Account',
        'phone' => '0900000000',
        'role_id' => 2,
        'password' => Illuminate\Support\Facades\Hash::make('password123'),
        'status' => App\Models\User::STATUS_ACTIVE,
    ]
);

echo $user->id . '|' . $user->name . '|' . $user->email . PHP_EOL;
