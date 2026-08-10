<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('users')
    ->join('roles', 'users.role_id', '=', 'roles.id')
    ->select('users.id', 'users.name', 'users.email', 'users.status', 'roles.role_name')
    ->get();

foreach ($rows as $row) {
    echo $row->id . '|' . $row->name . '|' . $row->email . '|' . $row->status . '|' . $row->role_name . PHP_EOL;
}
