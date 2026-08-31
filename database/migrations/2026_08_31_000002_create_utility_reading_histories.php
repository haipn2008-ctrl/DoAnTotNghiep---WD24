<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_reading_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('utility_reading_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->json('snapshot');
            $table->json('previous_snapshot')->nullable();
            $table->timestamp('performed_at')->index();

            $table->index(['utility_reading_id', 'performed_at'], 'utility_reading_history_timeline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_reading_histories');
    }
};
