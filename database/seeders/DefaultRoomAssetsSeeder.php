<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DefaultRoomAssetsSeeder extends Seeder
{
    /**
     * Bộ tài sản tiêu chuẩn được bàn giao mặc định cho mỗi phòng demo.
     */
    public const DEFAULT_ASSETS = [
        'Bình nóng lạnh',
        'Giường',
        'Máy giặt',
        'Máy lạnh',
        'Tủ lạnh',
        'Tủ quần áo',
    ];

    private const DEFAULT_IMAGES = [
        'Bình nóng lạnh' => 'water-heater.jpg',
        'Giường' => 'bed.jpg',
        'Máy giặt' => 'washing-machine.jpg',
        'Máy lạnh' => 'air-conditioner.jpg',
        'Tủ lạnh' => 'refrigerator.jpg',
        'Tủ quần áo' => 'wardrobe.jpg',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $assets = Amenity::query()
                ->active()
                ->assets()
                ->whereIn('name', self::DEFAULT_ASSETS)
                ->get();

            if ($assets->count() !== count(self::DEFAULT_ASSETS)) {
                throw new \RuntimeException('Chưa có đủ 6 tài sản mặc định. Hãy chạy AmenitySeeder trước.');
            }

            $defaultImagePaths = $this->publishDefaultImages();

            Room::query()->each(function (Room $room) use ($assets, $defaultImagePaths): void {
                $existingImages = $room->amenities()
                    ->get()
                    ->mapWithKeys(fn (Amenity $asset): array => [
                        $asset->id => $asset->pivot->image_path,
                    ]);

                $payload = $assets->mapWithKeys(function (Amenity $asset) use ($existingImages, $defaultImagePaths): array {
                    $existingPath = $existingImages->get($asset->id);
                    $imagePath = $existingPath && Storage::disk('public')->exists($existingPath)
                        ? $existingPath
                        : $defaultImagePaths[$asset->name];

                    return [$asset->id => [
                        'quantity' => 1,
                        'condition' => 'normal',
                        'note' => null,
                        'image_path' => $imagePath,
                    ]];
                })->all();

                $room->amenities()->sync($payload);
            });
        });
    }

    private function publishDefaultImages(): array
    {
        $paths = [];

        foreach (self::DEFAULT_IMAGES as $assetName => $fileName) {
            $source = database_path('seed-assets/room-assets/'.$fileName);
            if (! is_file($source)) {
                throw new \RuntimeException("Thiếu ảnh seed mặc định: {$fileName}");
            }

            $target = 'room-assets/defaults/'.$fileName;
            Storage::disk('public')->put($target, file_get_contents($source));
            $paths[$assetName] = $target;
        }

        return $paths;
    }
}
