<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('payment_code', 20)->nullable()->unique()->after('user_id');
        });

        DB::table('tenants')->orderBy('id')->eachById(function ($tenant) {
            DB::table('tenants')->where('id', $tenant->id)->update([
                'payment_code' => 'KH'.str_pad((string) $tenant->id, 8, '0', STR_PAD_LEFT),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('payment_code');
        });
    }
};
