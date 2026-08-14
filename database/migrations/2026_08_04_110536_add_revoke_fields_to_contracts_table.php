<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->text('revoke_reason')->nullable()->after('status');
            $table->timestamp('revoked_at')->nullable()->after('revoke_reason');
            $table->unsignedBigInteger('revoked_by')->nullable()->after('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'revoke_reason',
                'revoked_at',
                'revoked_by',
            ]);
        });
    }
};
