<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantDocument;
use Illuminate\Http\UploadedFile;

class TenantIdentityDocumentService
{
    public function storePair(Tenant $tenant, UploadedFile $front, UploadedFile $back, array &$storedPaths): TenantDocument
    {
        $directory = "tenant-identities/{$tenant->id}";
        $frontPath = $front->store($directory, 'local');
        if ($frontPath) {
            $storedPaths[] = $frontPath;
        }
        $backPath = $back->store($directory, 'local');
        if ($backPath) {
            $storedPaths[] = $backPath;
        }
        if (! $frontPath || ! $backPath) {
            throw new \RuntimeException('Không thể lưu đủ hai mặt CCCD.');
        }

        return $this->syncPaths($tenant, $frontPath, $backPath);
    }

    public function syncPaths(Tenant $tenant, string $frontPath, string $backPath): TenantDocument
    {
        return TenantDocument::query()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'cccd' => $tenant->cccd,
                'cccd_issue_date' => $tenant->cccd_issue_date,
                'cccd_issue_place' => $tenant->cccd_issue_place,
                'cccd_front_image' => $frontPath,
                'cccd_back_image' => $backPath,
            ]
        );
    }

    public function syncMetadata(Tenant $tenant): void
    {
        $tenant->document()->update([
            'cccd' => $tenant->cccd,
            'cccd_issue_date' => $tenant->cccd_issue_date,
            'cccd_issue_place' => $tenant->cccd_issue_place,
        ]);
    }
}
