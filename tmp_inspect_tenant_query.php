<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$count = App\Models\Tenant::count();
echo 'tenant_count=' . $count . PHP_EOL;
$tenantUserIds = App\Models\Tenant::pluck('user_id')->all();
echo implode(',', $tenantUserIds) . PHP_EOL;

$users = App\Models\User::whereHas('role', function ($query) {
    $query->whereIn('role_name', ['User', 'Client']);
})->whereIn('status', [App\Models\User::STATUS_PENDING, App\Models\User::STATUS_ACTIVE])->doesntHave('tenant')->get();

echo 'eligible_count=' . count($users) . PHP_EOL;
foreach ($users as $u) {
    echo $u->id . '|' . $u->name . '|' . $u->email . PHP_EOL;
}
