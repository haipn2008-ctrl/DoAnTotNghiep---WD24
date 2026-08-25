<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('vehicles')
            ->whereNotNull('vehicle_image')
            ->orderBy('id')
            ->get(['id', 'vehicle_image'])
            ->each(function (object $vehicle): void {
                $sourcePath = (string) $vehicle->vehicle_image;
                if (! str_starts_with($sourcePath, 'vehicles/') || ! Storage::disk('public')->exists($sourcePath)) {
                    return;
                }

                $contents = Storage::disk('public')->get($sourcePath);
                $targetPath = $sourcePath;
                if (Storage::disk('local')->exists($targetPath)
                    && ! hash_equals(Storage::disk('local')->get($targetPath), $contents)) {
                    $targetPath = 'vehicles/'.Str::uuid().'-'.basename($sourcePath);
                }

                if (! Storage::disk('local')->exists($targetPath)
                    && ! Storage::disk('local')->put($targetPath, $contents)) {
                    return;
                }

                DB::table('vehicles')->where('id', $vehicle->id)->update(['vehicle_image' => $targetPath]);
                Storage::disk('public')->delete($sourcePath);
            });

        foreach (Storage::disk('public')->files('vehicles') as $sourcePath) {
            $contents = Storage::disk('public')->get($sourcePath);
            $targetPath = 'vehicles/orphaned/'.Str::uuid().'-'.basename($sourcePath);
            if (Storage::disk('local')->put($targetPath, $contents)) {
                Storage::disk('public')->delete($sourcePath);
            }
        }
    }

    public function down(): void
    {
        // Security migration: never expose private vehicle images on rollback.
    }
};
