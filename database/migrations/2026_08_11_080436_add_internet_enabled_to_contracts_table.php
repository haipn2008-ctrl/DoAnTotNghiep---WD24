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
        if (!Schema::hasColumn('contracts', 'internet_enabled')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->boolean('internet_enabled')
                    ->default(false)
                    ->after('number_of_people');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contracts', 'internet_enabled')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('internet_enabled');
            });
        }
    }
};
