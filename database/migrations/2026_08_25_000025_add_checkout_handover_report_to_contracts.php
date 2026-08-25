<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->unsignedInteger('checkout_key_count')->nullable()->after('checkout_reason');
            $table->json('checkout_asset_report')->nullable()->after('checkout_key_count');
            $table->text('checkout_damage_note')->nullable()->after('checkout_asset_report');
            $table->json('checkout_photo_paths')->nullable()->after('checkout_damage_note');
            $table->timestamp('checkout_handover_confirmed_at')->nullable()->after('checkout_photo_paths');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropColumn([
                'checkout_key_count', 'checkout_asset_report', 'checkout_damage_note',
                'checkout_photo_paths', 'checkout_handover_confirmed_at',
            ]);
        });
    }
};
