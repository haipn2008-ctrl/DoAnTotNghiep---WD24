<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->timestamp('move_in_terms_confirmed_at')->nullable()->after('reservation_expires_at');
            $table->foreignId('move_in_terms_confirmed_by')->nullable()->after('move_in_terms_confirmed_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('move_in_terms_confirmed_by');
            $table->dropColumn('move_in_terms_confirmed_at');
        });
    }
};
