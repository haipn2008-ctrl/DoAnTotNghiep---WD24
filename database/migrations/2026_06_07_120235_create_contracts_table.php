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

            $table->string('contract_code')
                ->unique();

            $table->foreignId('room_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('monthly_rent', 12, 2);

            $table->decimal('deposit_amount', 12, 2)
                ->default(0);

            $table->integer('number_of_people')
                ->default(1);

            $table->date('signed_at')
                ->nullable();

            $table->date('start_date');

            $table->date('end_date');

            $table->date('terminated_at')
                ->nullable();

            $table->string('contract_file')
                ->nullable();

            $table->enum('status', [
                'pending',
                'active',
                'expired',
                'terminated'
            ])->default('pending');

            $table->text('note')
                ->nullable();

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
