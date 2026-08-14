<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RoomEvidenceService
{
    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function store(Room $room, array $files, array $attributes): Collection
    {
        $storedPaths = [];
        $images = collect();

        try {
            foreach ($files as $file) {
                $path = $file->store("room-evidence/{$room->id}", 'public');
                $storedPaths[] = $path;

                $images->push($room->images()->create([
                    ...$attributes,
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                ]));
            }
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }

        return $images;
    }

    public function deleteFiles(iterable $images): void
    {
        foreach ($images as $image) {
            Storage::disk($image->disk)->delete($image->path);
        }
    }
}
