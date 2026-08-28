<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tenants')->orderBy('id')->each(function ($tenant): void {
            if (blank($tenant->cccd)) {
                return;
            }

            $membership = DB::table('contract_tenants')
                ->where('tenant_id', $tenant->id)
                ->whereNotNull('identity_front_path')
                ->whereNotNull('identity_back_path')
                ->latest('id')
                ->first(['identity_front_path', 'identity_back_path']);
            if (! $membership) {
                return;
            }

            $document = DB::table('tenant_documents')->where('tenant_id', $tenant->id)->first();
            if ($document) {
                DB::table('tenant_documents')->where('id', $document->id)->update([
                    'cccd' => $tenant->cccd,
                    'cccd_issue_date' => $tenant->cccd_issue_date,
                    'cccd_issue_place' => $tenant->cccd_issue_place,
                    'cccd_front_image' => $document->cccd_front_image ?: $membership->identity_front_path,
                    'cccd_back_image' => $document->cccd_back_image ?: $membership->identity_back_path,
                    'updated_at' => now(),
                ]);

                return;
            }

            DB::table('tenant_documents')->insert([
                'tenant_id' => $tenant->id,
                'cccd' => $tenant->cccd,
                'cccd_issue_date' => $tenant->cccd_issue_date,
                'cccd_issue_place' => $tenant->cccd_issue_place,
                'cccd_front_image' => $membership->identity_front_path,
                'cccd_back_image' => $membership->identity_back_path,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // Không xóa ảnh hồ sơ đã đồng bộ vì đây là dữ liệu người dùng.
    }
};
