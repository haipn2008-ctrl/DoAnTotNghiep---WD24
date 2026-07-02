<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';
$kernel = app()->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

$targetYear = 2026;
$invoices = Invoice::all();
$updated = 0;
foreach ($invoices as $inv) {
    $changed = false;
    if ((int)$inv->year !== $targetYear) {
        $inv->year = $targetYear;
        $changed = true;
    }
    // adjust invoice_date and due_date to year 2026 while keeping month/day
    try {
        $invDate = \Carbon\Carbon::parse($inv->invoice_date);
    } catch (Exception $e) {
        $invDate = null;
    }
    if ($invDate) {
        if ($invDate->year !== $targetYear) {
            $invDate->year = $targetYear;
            $inv->invoice_date = $invDate->format('Y-m-d');
            $changed = true;
        }
    }
    try {
        $dueDate = \Carbon\Carbon::parse($inv->due_date);
    } catch (Exception $e) {
        $dueDate = null;
    }
    if ($dueDate) {
        if ($dueDate->year !== $targetYear) {
            $dueDate->year = $targetYear;
            $inv->due_date = $dueDate->format('Y-m-d');
            $changed = true;
        }
    }

    if ($changed) {
        $inv->save();
        $updated++;
        echo "Updated invoice #{$inv->id}: month={$inv->month} year={$inv->year} invoice_date={$inv->invoice_date}\n";
    }
}

echo "Total invoices updated: {$updated}\n";

// show distinct months/years now
$distinct = DB::select('select month, year, invoice_date from invoices limit 10');
var_export($distinct);
