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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            $table->string('full_name');

            $table->date('date_of_birth')
                ->nullable();
            $table->enum('gender', [
                'male',
                'female',
                'other'
            ])->nullable();
            $table->string('cccd')
                ->unique();

            $table->date('cccd_issue_date')
                ->nullable();

            $table->string('cccd_issue_place')
                ->nullable();

            $table->string('phone');

            $table->string('email')
                ->nullable();

            $table->text('address')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
