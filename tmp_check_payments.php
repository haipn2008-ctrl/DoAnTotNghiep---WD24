<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
Illuminate\Support\Facades\DB::setFacadeApplication($app);
$rows = Illuminate\Support\Facades\DB::select('SELECT status, YEAR(payment_date) AS y, MONTH(payment_date) AS m, SUM(amount_paid) AS total FROM payments GROUP BY status, y, m ORDER BY y, m');
print_r($rows);
$yearly = Illuminate\Support\Facades\DB::select('SELECT YEAR(payment_date) AS y, SUM(amount_paid) AS total FROM payments WHERE status = ? GROUP BY y ORDER BY y', ['success']);
print_r($yearly);
