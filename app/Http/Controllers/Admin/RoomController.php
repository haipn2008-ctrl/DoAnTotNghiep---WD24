<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Models\Amenity;
use App\Models\Contract;
use App\Models\Room;
use App\Services\RoomEvidenceService;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function __construct(private readonly RoomEvidenceService $evidenceService) {}

    public function index(Request $request)
    {
        $rooms = $this->roomQuery($request)->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('admin.rooms.partials.results', compact('rooms'));
        }

        return view('admin.rooms.index', compact('rooms'));
    }

    public function export(Request $request)
    {
        $rooms = $this->roomQuery($request)->reorder('id');
        $filename = 'danh_sach_phong_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        $columns = ['Mã phòng', 'Tầng', 'Giá thuê', 'Diện tích (m²)', 'Số người hiện tại', 'Trạng thái', 'Tài sản bàn giao'];

        $callback = function () use ($rooms, $columns): void {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            Csv::writeRow($file, $columns);

            foreach ($rooms->lazy(500) as $room) {
                $status = match ($room->status) {
                    Room::STATUS_AVAILABLE => 'Trống',
                    Room::STATUS_OCCUPIED => 'Đang thuê',
                    Room::STATUS_MAINTENANCE => 'Bảo trì',
                    default => ucfirst($room->status),
                };
                $assets = $room->amenities->where('category', Amenity::CATEGORY_ASSET)->map(function (Amenity $amenity): string {
                    $quantity = $amenity->is_quantifiable ? ' x'.$amenity->pivot->quantity : '';

                    return $amenity->name.$quantity;
                })->implode(', ');

                Csv::writeRow($file, [$room->room_code, $room->floor, number_format($room->price), $room->area,
                    $room->current_people, $status, $assets]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $amenities = Amenity::query()->active()->orderBy('category')->orderBy('name')->get();

        return view('admin.rooms.create', compact('amenities'));
    }

    public function store(RoomRequest $request)
    {
        $data = $request->validated();
        $files = $request->file('images', []);
        if ($request->hasFile('image')) {
            $files[] = $request->file('image');
        }

        unset($data['image'], $data['images'], $data['amenities'], $data['inventory'], $data['status'], $data['current_people']);
        $data['status'] = Room::STATUS_AVAILABLE;
        $data['current_people'] = 0;
        $storedImages = collect();

        try {
            DB::transaction(function () use ($data, $files, $request, &$storedImages): void {
                $room = Room::create($data);
                $room->amenities()->sync($this->inventoryPayload($request));

                $storedImages = $this->evidenceService->store($room, $files, [
                    'evidence_type' => 'baseline',
                    'uploaded_by' => $request->user()->id,
                    'taken_at' => now(),
                    'caption' => 'Ảnh trước khi bàn giao phòng, được tải lên lúc tạo phòng.',
                ]);

                if ($storedImages->isNotEmpty()) {
                    $room->update(['thumbnail' => $storedImages->first()->path]);
                }
            });
        } catch (\Throwable $exception) {
            $this->evidenceService->deleteFiles($storedImages);
            throw $exception;
        }

        return redirect()->route('admin.rooms.index')->with('success', 'Thêm phòng thành công. Phòng mặc định Trống và chưa có người thuê.');
    }

    public function show(Room $room)
    {
        $room->load(['amenities', 'images.uploader', 'images.contract', 'contracts']);
        $occupancyContract = Contract::query()
            ->with(['representative.user', 'tenant.user', 'members.tenant'])
            ->where('room_id', $room->id)
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
            ->latest('actual_move_in_at')
            ->latest('id')
            ->first();
        $members = $occupancyContract?->members
            ->where('status', \App\Models\ContractTenant::STATUS_CHECKED_IN)
            ->values() ?? collect();
        $unidentifiedMembers = $occupancyContract
            ? max(0, (int) $room->current_people - $members->count())
            : 0;

        return view('admin.rooms.show', compact('room', 'occupancyContract', 'members', 'unidentifiedMembers'));
    }

    public function edit(Room $room)
    {
        $room->load(['amenities', 'images']);
        $amenities = Amenity::query()->active()->orderBy('category')->orderBy('name')->get();

        return view('admin.rooms.edit', compact('room', 'amenities'));
    }

    public function update(RoomRequest $request, Room $room)
    {
        $data = $request->validated();
        $files = $request->file('images', []);
        if ($request->hasFile('image')) {
            $files[] = $request->file('image');
        }

        unset($data['image'], $data['images'], $data['amenities'], $data['inventory'], $data['current_people']);
        $storedImages = collect();

        try {
            DB::transaction(function () use ($data, $files, $request, $room, &$storedImages): void {
                $lockedRoom = Room::query()->lockForUpdate()->findOrFail($room->id);
                $lockedRoom->update($data);
                $lockedRoom->amenities()->sync($this->inventoryPayload($request));
                $storedImages = $this->evidenceService->store($lockedRoom, $files, [
                    'evidence_type' => 'baseline',
                    'uploaded_by' => $request->user()->id,
                    'taken_at' => now(),
                    'caption' => 'Ảnh bổ sung trước khi bàn giao phòng.',
                ]);

                if (! $lockedRoom->thumbnail && $storedImages->isNotEmpty()) {
                    $lockedRoom->update(['thumbnail' => $storedImages->first()->path]);
                }
            });
        } catch (\Throwable $exception) {
            $this->evidenceService->deleteFiles($storedImages);
            throw $exception;
        }

        return redirect()->route('admin.rooms.show', $room)->with('success', 'Cập nhật phòng thành công. Ảnh cũ vẫn được giữ trong nhật ký bằng chứng.');
    }

    public function destroy(Room $room)
    {
        $legacyThumbnail = $room->thumbnail;
        $images = $room->images()->get();
        $cannotDelete = DB::transaction(function () use ($room): bool {
            $lockedRoom = Room::query()->lockForUpdate()->findOrFail($room->id);

            if ($lockedRoom->contracts()->exists() || $lockedRoom->utilityReadings()->exists()) {
                return true;
            }

            $lockedRoom->delete();

            return false;
        });

        if ($cannotDelete) {
            return redirect()->route('admin.rooms.index')->with('error', 'Không thể xóa phòng đã có dữ liệu thuê hoặc chỉ số điện nước.');
        }

        $this->evidenceService->deleteFiles($images);
        if ($legacyThumbnail && ! $images->contains('path', $legacyThumbnail)) {
            Storage::disk('public')->delete($legacyThumbnail);
        }

        return redirect()->route('admin.rooms.index')->with('success', 'Xóa phòng thành công.');
    }

    private function inventoryPayload(RoomRequest $request): array
    {
        $payload = [];

        if (! $request->has('inventory') && ! $request->route('room')) {
            $payload = Amenity::query()->active()->assets()->pluck('id')
                ->mapWithKeys(fn (int $amenityId): array => [
                    $amenityId => ['quantity' => 1, 'condition' => 'normal', 'note' => null],
                ])->all();
        }

        $legacyAssetIds = Amenity::query()->active()->assets()
            ->whereKey((array) $request->input('amenities', []))->pluck('id');
        foreach ($legacyAssetIds as $amenityId) {
            $payload[(int) $amenityId] = ['quantity' => 1, 'condition' => 'normal', 'note' => null];
        }

        $inventory = (array) $request->input('inventory', []);
        $amenities = Amenity::query()->active()->assets()->whereKey(array_keys($inventory))->get()->keyBy('id');
        foreach ($inventory as $amenityId => $item) {
            if (! filter_var($item['selected'] ?? false, FILTER_VALIDATE_BOOL) || ! $amenities->has((int) $amenityId)) {
                continue;
            }

            $amenity = $amenities->get((int) $amenityId);
            $payload[(int) $amenityId] = [
                'quantity' => $amenity->is_quantifiable ? (int) ($item['quantity'] ?? 1) : 1,
                'condition' => $item['condition'] ?? 'normal',
                'note' => null,
            ];
        }

        return $payload;
    }

    private function roomQuery(Request $request)
    {
        $filters = $request->validate([
            'room_code' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in([Room::STATUS_AVAILABLE, Room::STATUS_OCCUPIED, Room::STATUS_MAINTENANCE])],
        ]);
        $query = Room::with('amenities')->withCount(['contracts', 'utilityReadings']);

        if (! empty($filters['room_code'])) {
            $query->where('room_code', 'like', '%'.$filters['room_code'].'%');
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest();
    }
}
