<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payments', 'submitted_by') || !Schema::hasColumn('payments', 'proof_image')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'submitted_by')) {
                    $table->unsignedBigInteger('submitted_by')
                        ->nullable()
                        ->after('transaction_code');
                }

                if (!Schema::hasColumn('payments', 'proof_image')) {
                    $table->string('proof_image')
                        ->nullable()
                        ->after('submitted_by');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'submitted_by')) {
                $table->dropColumn('submitted_by');
            }

            if (Schema::hasColumn('payments', 'proof_image')) {
                $table->dropColumn('proof_image');
            }
        });
    }
};
