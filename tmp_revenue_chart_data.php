<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';
$kernel = app()->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new App\Http\Controllers\Admin\OverviewController();
$view = $controller->revenueChart();
$data = $view->getData();

echo "currentYear=" . $data['currentYear'] . PHP_EOL;
echo "monthlyRevenue=" . json_encode($data['monthlyRevenue'], JSON_PRETTY_PRINT) . PHP_EOL;
echo "yearLabels=" . json_encode($data['yearLabels'], JSON_PRETTY_PRINT) . PHP_EOL;
echo "yearlyRevenue=" . json_encode($data['yearlyRevenue'], JSON_PRETTY_PRINT) . PHP_EOL;
