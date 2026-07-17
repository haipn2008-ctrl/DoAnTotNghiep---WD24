<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'invoice_code')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('invoice_code')->nullable()->after('id');
            });
        }

        DB::table('invoices')
            ->whereNull('invoice_code')
            ->orderBy('id')
            ->get(['id', 'month', 'year'])
            ->each(function ($invoice) {
                DB::table('invoices')
                    ->where('id', $invoice->id)
                    ->update([
                        'invoice_code' => sprintf(
                            'INV-%04d%02d-%04d',
                            $invoice->year,
                            $invoice->month,
                            $invoice->id
                        ),
                    ]);
            });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('invoice_code', 'invoices_invoice_code_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'invoice_code')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique('invoices_invoice_code_unique');
                $table->dropColumn('invoice_code');
            });
        }
    }
};
