<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_requests')) {
            return;
        }

        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();

            $table->string('submission_token')->unique();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->nullOnDelete();

            $table->foreignId('contract_id')
                ->nullable()
                ->constrained('contracts')
                ->nullOnDelete();

            $table->string('category');
            $table->string('subject');
            $table->text('description');

            $table->string('attachment')->nullable();

            $table->string('status')->default('new');

            $table->text('admin_response')->nullable();

            $table->foreignId('handled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('tenant_id');
            $table->index('contract_id');
            $table->index('handled_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};
