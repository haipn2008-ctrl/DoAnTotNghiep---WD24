<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->boolean('is_quantifiable')->default(true)->after('description');
        });

        Schema::table('amenity_room', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('amenity_id');
            $table->string('condition', 30)->default('good')->after('quantity');
            $table->text('note')->nullable()->after('condition');
        });

        Schema::create('room_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evidence_type', 30)->default('baseline');
            $table->string('disk', 30)->default('public');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->text('caption')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'evidence_type']);
            $table->index(['contract_id', 'evidence_type']);
        });

        DB::table('rooms')
            ->whereNotNull('thumbnail')
            ->where('thumbnail', '<>', '')
            ->orderBy('id')
            ->each(function (object $room): void {
                DB::table('room_images')->insert([
                    'room_id' => $room->id,
                    'evidence_type' => 'legacy',
                    'disk' => 'public',
                    'path' => $room->thumbnail,
                    'caption' => 'Ảnh phòng được chuyển đổi từ dữ liệu cũ.',
                    'taken_at' => null,
                    'metadata' => json_encode(['migrated' => true], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_images');

        Schema::table('amenity_room', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'condition', 'note']);
        });

        Schema::table('amenities', function (Blueprint $table) {
            $table->dropColumn('is_quantifiable');
        });
    }
};
