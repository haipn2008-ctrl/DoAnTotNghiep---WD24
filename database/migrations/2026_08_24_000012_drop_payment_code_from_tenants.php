<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'payment_code')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropUnique('tenants_payment_code_unique');
            $table->dropColumn('payment_code');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'payment_code')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('payment_code', 20)->nullable()->unique()->after('user_id');
        });
    }
};
