<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Room;
use App\Models\RoomImage;
use App\Services\RoomEvidenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomEvidenceController extends Controller
{
    public function __construct(private readonly RoomEvidenceService $evidenceService) {}

    public function store(Request $request, Room $room): RedirectResponse
    {
        $data = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:15'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'evidence_type' => ['required', Rule::in(RoomImage::UPLOAD_TYPES)],
            'contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ], [
            'images.required' => 'Vui lòng chọn ít nhất một ảnh.',
            'images.max' => 'Mỗi lần chỉ được tải tối đa 15 ảnh.',
            'images.*.image' => 'Mỗi file phải là ảnh.',
            'images.*.mimes' => 'Ảnh chỉ hỗ trợ JPG, JPEG, PNG hoặc WebP.',
            'images.*.max' => 'Mỗi ảnh tối đa 8 MB.',
        ]);

        $requiresContract = $data['evidence_type'] === RoomImage::TYPE_CHECKOUT;
        if ($requiresContract && empty($data['contract_id'])) {
            throw ValidationException::withMessages(['contract_id' => 'Ảnh sau khi nhận lại phòng phải gắn với một hợp đồng.']);
        }
        if (! empty($data['contract_id']) && ! Contract::query()->whereKey($data['contract_id'])->where('room_id', $room->id)->exists()) {
            throw ValidationException::withMessages(['contract_id' => 'Hợp đồng không thuộc phòng này.']);
        }

        $storedImages = collect();
        try {
            DB::transaction(function () use ($data, $request, $room, &$storedImages): void {
                $lockedRoom = Room::query()->lockForUpdate()->findOrFail($room->id);
                $storedImages = $this->evidenceService->store($lockedRoom, $request->file('images'), [
                    'contract_id' => $data['contract_id'] ?? null,
                    'uploaded_by' => $request->user()->id,
                    'evidence_type' => $data['evidence_type'],
                    // Thời điểm bằng chứng do máy chủ ghi nhận, không tin dữ liệu thời gian từ trình duyệt.
                    'taken_at' => now(),
                    'caption' => $data['caption'] ?? null,
                ]);

            });
        } catch (\Throwable $exception) {
            $this->evidenceService->deleteFiles($storedImages);
            throw $exception;
        }

        return back()->with('success', 'Đã thêm ảnh vào nhật ký hiện trạng. Ảnh cũ không bị thay thế.');
    }

    public function image(Room $room, RoomImage $roomImage)
    {
        abort_unless($roomImage->room_id === $room->id, 404);
        abort_unless(Storage::disk($roomImage->disk)->exists($roomImage->path), 404);

        return Storage::disk($roomImage->disk)->response($roomImage->path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
