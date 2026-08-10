<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('submitted_by')
                ->nullable()
                ->after('transaction_code');

            $table->string('proof_image')
                ->nullable()
                ->after('submitted_by');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'submitted_by',
                'proof_image',
            ]);
        });
    }
};