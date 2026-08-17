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
        Schema::create('tenant_documents', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Khách thuê
            |--------------------------------------------------------------------------
            */
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Thông tin CCCD
            |--------------------------------------------------------------------------
            */
            $table->string('cccd')
                ->unique();

            $table->date('cccd_issue_date')
                ->nullable();

            $table->string('cccd_issue_place')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Ảnh CCCD
            |--------------------------------------------------------------------------
            */
            $table->string('cccd_front_image')
                ->nullable();

            $table->string('cccd_back_image')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Tenant chỉ có 1 bộ giấy tờ
            |--------------------------------------------------------------------------
            */
            $table->unique('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_documents');
    }
};
