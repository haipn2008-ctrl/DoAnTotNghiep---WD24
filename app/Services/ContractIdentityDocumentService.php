<?php

namespace App\Services;

use App\Models\ContractTenant;
use App\Models\ContractTenantHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class ContractIdentityDocumentService
{
    public function storePair(
        ContractTenant $member,
        UploadedFile $front,
        UploadedFile $back,
        User $actor,
        array &$storedPaths,
    ): ContractTenant {
        $directory = "contract-identities/{$member->contract_id}/members/{$member->id}";
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

        $oldPaths = array_values(array_filter([$member->identity_front_path, $member->identity_back_path]));
        $member->forceFill([
            'identity_front_path' => $frontPath,
            'identity_back_path' => $backPath,
        ])->save();
        ContractTenantHistory::query()->create([
            'contract_tenant_id' => $member->id,
            'from_status' => $member->status,
            'to_status' => $member->status,
            'action' => $oldPaths ? 'identity_documents_replaced' : 'identity_documents_uploaded',
            'reason' => null,
            'performed_by' => $actor->id,
            'performed_at' => now(),
            'metadata' => ['previous_paths' => $oldPaths, 'current_paths' => [$frontPath, $backPath]],
        ]);

        return $member->fresh();
    }
}
