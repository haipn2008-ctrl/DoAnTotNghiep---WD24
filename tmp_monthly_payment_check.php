<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';
$kernel = app()->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$currentYear = date('Y');
$monthlyRevenue = [];
for ($month = 1; $month <= 12; $month++) {
    $monthlyRevenue[] = App\Models\Payment::where('status', 'success')
        ->whereYear('payment_date', $currentYear)
        ->whereMonth('payment_date', $month)
        ->sum('amount_paid');
}
echo json_encode(['year' => $currentYear, 'monthly' => $monthlyRevenue], JSON_PRETTY_PRINT);
