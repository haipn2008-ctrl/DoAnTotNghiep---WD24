<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->timestamp('issued_at')->nullable()->after('invoice_date');
            $table->foreignId('issued_by')->nullable()->after('issued_at')
                ->constrained('users')->nullOnDelete();
        });

        DB::table('invoices')->whereNull('issued_at')->update([
            'issued_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('issued_by');
            $table->dropColumn('issued_at');
        });
    }
};
