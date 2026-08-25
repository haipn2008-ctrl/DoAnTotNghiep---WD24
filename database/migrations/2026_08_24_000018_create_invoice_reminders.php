<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('channel', 30);
            $table->text('note')->nullable();
            $table->foreignId('reminded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reminded_by_name');
            $table->date('reminder_date');
            $table->dateTime('reminded_at');
            $table->timestamps();

            $table->unique(['invoice_id', 'reminder_date'], 'invoice_reminders_invoice_date_unique');
            $table->index(['reminder_date', 'channel']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['status', 'due_date'], 'invoices_status_due_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_status_due_date_index');
        });

        Schema::dropIfExists('invoice_reminders');
    }
};
