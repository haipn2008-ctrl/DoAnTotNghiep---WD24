<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('contract_id', 'invoices_contract_id_index');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['contract_id', 'month', 'year']);
            $table->string('invoice_type', 20)->default('rental')->after('invoice_code')->index();
            $table->unique(['contract_id', 'invoice_type', 'month', 'year'], 'invoices_contract_type_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_contract_type_period_unique');
            $table->dropIndex(['invoice_type']);
            $table->dropColumn('invoice_type');
            $table->unique(['contract_id', 'month', 'year']);
            $table->dropIndex('invoices_contract_id_index');
        });
    }
};
