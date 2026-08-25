<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->moveProofs('public', 'local');
    }

    public function down(): void
    {
        $this->moveProofs('local', 'public');
    }

    private function moveProofs(string $sourceDisk, string $targetDisk): void
    {
        DB::table('payments')
            ->whereNotNull('proof_image')
            ->pluck('proof_image')
            ->unique()
            ->each(function (string $path) use ($sourceDisk, $targetDisk): void {

                if (! str_starts_with($path, 'payment-proofs/')) {
                    return;
                }

                if (Storage::disk($targetDisk)->exists($path)) {
                    Storage::disk($sourceDisk)->delete($path);

                    return;
                }

                if (! Storage::disk($sourceDisk)->exists($path)) {
                    return;
                }

                $contents = Storage::disk($sourceDisk)->get($path);

                if (Storage::disk($targetDisk)->put($path, $contents)) {
                    Storage::disk($sourceDisk)->delete($path);
                }
            });
    }
};
