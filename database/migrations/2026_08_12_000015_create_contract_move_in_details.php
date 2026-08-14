<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->timestamp('move_in_inventory_snapshotted_at')->nullable()->after('move_in_terms_confirmed_by');
            $table->timestamp('move_in_details_confirmed_at')->nullable()->after('move_in_inventory_snapshotted_at');
            $table->foreignId('move_in_details_confirmed_by')->nullable()->after('move_in_details_confirmed_at')
                ->constrained('users')->nullOnDelete();
        });

        Schema::create('contract_handover_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_quantifiable')->default(true);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('condition', 30)->default('normal');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'amenity_id']);
            $table->index(['contract_id', 'condition']);
        });

        // Các hợp đồng cũ vẫn có phiếu tài sản để khách xem lịch sử. Không tự đánh dấu
        // khách đã xác nhận vì hệ thống cũ chưa từng thu thập sự đồng ý đó.
        DB::table('contracts')->orderBy('id')->eachById(function (object $contract): void {
            $items = DB::table('amenity_room')
                ->join('amenities', 'amenities.id', '=', 'amenity_room.amenity_id')
                ->where('amenity_room.room_id', $contract->room_id)
                ->where('amenities.is_active', true)
                ->orderBy('amenities.name')
                ->get([
                    'amenities.id as amenity_id', 'amenities.name', 'amenities.description',
                    'amenities.is_quantifiable', 'amenity_room.quantity', 'amenity_room.condition', 'amenity_room.note',
                ]);

            foreach ($items as $item) {
                DB::table('contract_handover_items')->insert([
                    'contract_id' => $contract->id,
                    'amenity_id' => $item->amenity_id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'is_quantifiable' => $item->is_quantifiable,
                    'quantity' => $item->quantity,
                    'condition' => $item->condition,
                    'note' => $item->note,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('contracts')->where('id', $contract->id)->update([
                'move_in_inventory_snapshotted_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_handover_items');

        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('move_in_details_confirmed_by');
            $table->dropColumn(['move_in_inventory_snapshotted_at', 'move_in_details_confirmed_at']);
        });
    }
};
