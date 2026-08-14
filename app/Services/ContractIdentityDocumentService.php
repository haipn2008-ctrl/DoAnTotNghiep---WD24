<?php

namespace App\Services;

use App\Models\ContractOccupant;
use App\Models\ContractOccupantHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class ContractIdentityDocumentService
{
    public function storePair(
        ContractOccupant $occupant,
        UploadedFile $front,
        UploadedFile $back,
        User $actor,
        array &$storedPaths,
    ): ContractOccupant {
        $directory = "contract-identities/{$occupant->contract_id}/occupants/{$occupant->id}";
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

        $oldPaths = array_values(array_filter([$occupant->identity_front_path, $occupant->identity_back_path]));
        $occupant->forceFill([
            'identity_front_path' => $frontPath,
            'identity_back_path' => $backPath,
        ])->save();
        ContractOccupantHistory::query()->create([
            'contract_occupant_id' => $occupant->id,
            'from_status' => $occupant->status,
            'to_status' => $occupant->status,
            'action' => $oldPaths ? 'identity_documents_replaced' : 'identity_documents_uploaded',
            'reason' => null,
            'performed_by' => $actor->id,
            'performed_at' => now(),
            'metadata' => ['previous_paths' => $oldPaths, 'current_paths' => [$frontPath, $backPath]],
        ]);

        return $occupant->fresh();
    }
}
