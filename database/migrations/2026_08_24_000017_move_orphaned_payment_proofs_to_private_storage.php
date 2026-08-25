<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Storage::disk('public')->files('payment-proofs') as $sourcePath) {
            $contents = Storage::disk('public')->get($sourcePath);
            $targetPath = $sourcePath;

            if (Storage::disk('local')->exists($targetPath)) {
                if (hash_equals(Storage::disk('local')->get($targetPath), $contents)) {
                    Storage::disk('public')->delete($sourcePath);

                    continue;
                }

                $targetPath = 'payment-proofs/orphaned/'.Str::uuid().'-'.basename($sourcePath);
            }

            if (Storage::disk('local')->put($targetPath, $contents)) {
                Storage::disk('public')->delete($sourcePath);
            }
        }
    }

    public function down(): void
    {
        // Security migration: do not expose private payment proofs on rollback.
    }
};
