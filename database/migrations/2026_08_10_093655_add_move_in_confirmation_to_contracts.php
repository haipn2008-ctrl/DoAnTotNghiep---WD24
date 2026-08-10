<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->date('move_in_date')
                ->nullable()
                ->after('tenant_signature');

            $table->dateTime('move_in_confirmed_at')
                ->nullable()
                ->after('move_in_date');

            $table->unsignedBigInteger('move_in_confirmed_by')
                ->nullable()
                ->after('move_in_confirmed_at');

            $table->index('move_in_confirmed_by');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['move_in_confirmed_by']);

            $table->dropColumn([
                'move_in_date',
                'move_in_confirmed_at',
                'move_in_confirmed_by',
            ]);
        });
    }
};