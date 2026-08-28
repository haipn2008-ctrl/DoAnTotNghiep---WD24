<?php

use App\Models\ContractTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('version')->unique();
            $table->json('clauses');
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('effective_from')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('contract_templates')->insert([
            'name' => 'Mẫu hợp đồng thuê phòng trọ',
            'version' => 1,
            'clauses' => json_encode(ContractTemplate::DEFAULT_CLAUSES, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_active' => true,
            'effective_from' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('contracts', function (Blueprint $table): void {
            $table->foreignId('contract_template_id')->nullable()->after('contract_content')
                ->constrained('contract_templates')->nullOnDelete();
            $table->timestamp('contract_content_snapshotted_at')->nullable()->after('contract_template_id');
            $table->char('contract_content_sha256', 64)->nullable()->after('contract_content_snapshotted_at');
        });

        $initialTemplateId = DB::table('contract_templates')->where('version', 1)->value('id');
        DB::table('contracts')->whereNull('contract_template_id')->update([
            'contract_template_id' => $initialTemplateId,
        ]);
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('contract_template_id');
            $table->dropColumn(['contract_content_snapshotted_at', 'contract_content_sha256']);
        });
        Schema::dropIfExists('contract_templates');
    }
};
