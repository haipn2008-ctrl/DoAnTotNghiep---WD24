<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';
$kernel = app()->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payment;

$currentYear = date('Y');
$monthlyRevenue = [];
for ($month = 1; $month <= 12; $month++) {
    $monthlyRevenue[] = (float) Payment::where('status', 'success')
        ->whereYear('payment_date', $currentYear)
        ->whereMonth('payment_date', $month)
        ->sum('amount_paid');
}

$yearLabels = [];
$yearlyRevenue = [];
for ($year = $currentYear - 4; $year <= $currentYear; $year++) {
    $yearLabels[] = $year;
    $yearlyRevenue[] = (float) Payment::where('status', 'success')
        ->whereYear('payment_date', $year)
        ->sum('amount_paid');
}

$html = view('admin.overview.revenue-chart', compact('monthlyRevenue', 'yearLabels', 'yearlyRevenue', 'currentYear'))->render();
echo $html;
