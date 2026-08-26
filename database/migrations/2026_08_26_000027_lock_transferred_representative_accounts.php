<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_representative_transfers')) {
            return;
        }

        DB::table('users')
            ->whereIn('id', DB::table('contract_representative_transfers')->whereNotNull('old_user_id')->select('old_user_id'))
            ->update([
                'status' => User::STATUS_LOCKED,
                'must_change_password' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('contract_representative_transfers')) {
            return;
        }

        DB::table('users')
            ->whereIn('id', DB::table('contract_representative_transfers')->whereNotNull('old_user_id')->select('old_user_id'))
            ->where('status', User::STATUS_LOCKED)
            ->update([
                'status' => User::STATUS_INACTIVE,
                'updated_at' => now(),
            ]);
    }
};
