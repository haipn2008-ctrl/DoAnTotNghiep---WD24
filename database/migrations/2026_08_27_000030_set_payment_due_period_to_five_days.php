<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->update(['payment_due_days' => 5]);
    }

    public function down(): void
    {
        DB::table('settings')->where('payment_due_days', 5)->update(['payment_due_days' => 10]);
    }
};
