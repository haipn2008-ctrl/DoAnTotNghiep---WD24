<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';
$kernel = app()->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Invoice;

$rows = Invoice::orderBy('id')->get(['id','month','year','total_amount']);
foreach($rows as $r){
    echo "#{$r->id}\t month=" . ($r->month === null ? 'NULL' : $r->month) . '\t year=' . ($r->year === null ? 'NULL' : $r->year) . '\t total=' . $r->total_amount . PHP_EOL;
}

echo "\nDistinct months:\n";
print_r(Invoice::select('month','year')->distinct()->get()->toArray());
