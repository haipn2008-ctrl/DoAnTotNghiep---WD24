<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_appendices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_appendix_id')->nullable()->constrained('contract_appendices')->nullOnDelete();
            $table->unsignedInteger('appendix_number');
            $table->unsignedInteger('revision')->default(1);
            $table->string('code')->unique();
            $table->string('title');
            $table->text('legal_basis')->nullable();
            $table->longText('content');
            $table->date('effective_from');
            $table->string('status', 32)->default('draft')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->char('content_sha256', 64)->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'appendix_number', 'revision'], 'contract_appendix_revision_unique');
            $table->index(['contract_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_appendices');
    }
};
