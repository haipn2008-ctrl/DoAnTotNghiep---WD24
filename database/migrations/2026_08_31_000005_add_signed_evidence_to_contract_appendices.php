<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_appendices', function (Blueprint $table): void {
            $table->string('appendix_type', 32)->default('general')->after('code')->index();
            $table->foreignId('extension_request_id')->nullable()->after('contract_id')
                ->constrained('contract_extension_requests')->nullOnDelete();
            $table->json('signed_evidence_paths')->nullable()->after('content_sha256');
            $table->timestamp('signed_evidence_uploaded_at')->nullable()->after('signed_evidence_paths');
            $table->foreignId('signed_evidence_uploaded_by')->nullable()->after('signed_evidence_uploaded_at')
                ->constrained('users')->nullOnDelete();
            $table->unique('extension_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('contract_appendices', function (Blueprint $table): void {
            $table->dropUnique(['extension_request_id']);
            $table->dropConstrainedForeignId('signed_evidence_uploaded_by');
            $table->dropConstrainedForeignId('extension_request_id');
            $table->dropIndex(['appendix_type']);
            $table->dropColumn([
                'appendix_type',
                'signed_evidence_paths',
                'signed_evidence_uploaded_at',
            ]);
        });
    }
};
