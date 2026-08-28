<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Models\Amenity;
use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\Room;
use App\Models\UtilityReading;
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
        $initialElectricity = (int) $data['initial_electricity'];
        $initialWater = (int) $data['initial_water'];
        $files = $request->file('images', []);
        if ($request->hasFile('image')) {
            $files[] = $request->file('image');
        }

        unset(
            $data['image'], $data['images'], $data['amenities'], $data['inventory'],
            $data['status'], $data['current_people'], $data['initial_electricity'], $data['initial_water']
        );
        $data['status'] = Room::STATUS_AVAILABLE;
        $data['current_people'] = 0;
        $storedImages = collect();

        try {
            DB::transaction(function () use ($data, $files, $request, $initialElectricity, $initialWater, &$storedImages): void {
                $room = Room::create($data);
                $room->amenities()->sync($this->inventoryPayload($request));

                UtilityReading::query()->forceCreate([
                    'room_id' => $room->id,
                    'contract_id' => null,
                    'month' => today()->month,
                    'year' => today()->year,
                    'record_date' => today(),
                    'reading_type' => 'baseline',
                    'lifecycle_event_key' => "room:{$room->id}:baseline",
                    'electricity_old' => $initialElectricity,
                    'electricity_new' => $initialElectricity,
                    'water_old' => $initialWater,
                    'water_new' => $initialWater,
                    'status' => 'confirmed',
                    'note' => 'Chỉ số công tơ ban đầu khi tạo phòng.',
                ]);

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
            ->where('status', ContractTenant::STATUS_CHECKED_IN)
            ->values() ?? collect();
        $unidentifiedMembers = $occupancyContract
            ? max(0, (int) $room->current_people - $members->count())
            : 0;

        return view('admin.rooms.show', compact('room', 'occupancyContract', 'members', 'unidentifiedMembers'));
    }

    public function edit(Room $room)
    {
        abort_if($room->status === Room::STATUS_RETIRED, 409, 'Phòng đã ngừng khai thác không thể chỉnh sửa.');
        $room->load(['amenities', 'images']);
        $amenities = Amenity::query()->active()->orderBy('category')->orderBy('name')->get();

        return view('admin.rooms.edit', compact('room', 'amenities'));
    }

    public function update(RoomRequest $request, Room $room)
    {
        abort_if($room->status === Room::STATUS_RETIRED, 409, 'Phòng đã ngừng khai thác không thể chỉnh sửa.');
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

            $hasOperationalReadings = $lockedRoom->utilityReadings()
                ->where(fn ($query) => $query
                    ->whereNull('reading_type')
                    ->orWhere('reading_type', '!=', 'baseline'))
                ->exists();

            if ($lockedRoom->contracts()->exists() || $hasOperationalReadings) {
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

    public function retire(Request $request, Room $room)
    {
        $data = $request->validate([
            'retirement_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $result = DB::transaction(function () use ($room, $request, $data): string {
            $lockedRoom = Room::query()->lockForUpdate()->findOrFail($room->id);

            if ($lockedRoom->status === Room::STATUS_RETIRED) {
                return 'already_retired';
            }

            if ($lockedRoom->activeContract()->exists() || (int) $lockedRoom->current_people > 0) {
                return 'occupied';
            }

            $hasHistory = $lockedRoom->contracts()->exists()
                || $lockedRoom->utilityReadings()
                    ->where(fn ($query) => $query->whereNull('reading_type')->orWhere('reading_type', '!=', 'baseline'))
                    ->exists();
            if (! $hasHistory) {
                return 'unused';
            }

            $lockedRoom->forceFill([
                'status' => Room::STATUS_RETIRED,
                'retired_at' => now(),
                'retired_by' => $request->user()->id,
                'retirement_reason' => $data['retirement_reason'],
            ])->save();

            return 'retired';
        }, 3);

        return match ($result) {
            'retired' => redirect()->route('admin.rooms.index')->with('success', 'Đã ngừng khai thác phòng và giữ nguyên toàn bộ lịch sử.'),
            'occupied' => back()->with('error', 'Không thể ngừng khai thác phòng đang có người thuê.'),
            'unused' => back()->with('error', 'Phòng chưa từng vận hành; hãy dùng chức năng xóa cứng nếu đây là dữ liệu tạo nhầm.'),
            default => back()->with('error', 'Phòng đã ngừng khai thác trước đó.'),
        };
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
                'note' => filled($item['note'] ?? null) ? trim((string) $item['note']) : null,
            ];
        }

        return $payload;
    }

    private function roomQuery(Request $request)
    {
        $filters = $request->validate([
            'room_code' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in([Room::STATUS_AVAILABLE, Room::STATUS_OCCUPIED, Room::STATUS_MAINTENANCE, Room::STATUS_RETIRED])],
        ]);
        $query = Room::with('amenities')->withCount([
            'contracts',
            'activeContract as active_contracts_count',
            'utilityReadings as operational_utility_readings_count' => fn ($query) => $query
                ->where(fn ($query) => $query->whereNull('reading_type')->orWhere('reading_type', '!=', 'baseline')),
        ]);

        if (! empty($filters['room_code'])) {
            $query->where('room_code', 'like', '%'.$filters['room_code'].'%');
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest();
    }
}
