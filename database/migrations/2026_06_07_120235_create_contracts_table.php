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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained('rooms');

            $table->foreignId('tenant_id')
                ->constrained('tenants');

            $table->date('start_date');

            $table->date('end_date');

            $table->decimal('deposit_amount', 12, 2)
                ->default(0);

            $table->enum('status', [
                'active',
                'expired',
                'terminated'
            ])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
