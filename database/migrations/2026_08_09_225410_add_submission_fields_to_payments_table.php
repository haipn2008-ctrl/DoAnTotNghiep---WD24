<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payments', 'submitted_by')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('submitted_by')
                    ->nullable()
                    ->after('transaction_code');
            });
        }

        if (! Schema::hasColumn('payments', 'proof_image')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('proof_image')
                    ->nullable()
                    ->after('submitted_by');
            });
        }
    }

    public function down(): void
    {
        foreach (['proof_image', 'submitted_by'] as $column) {
            if (Schema::hasColumn('payments', $column)) {
                Schema::table('payments', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
