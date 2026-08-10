<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Models\Amenity;
use App\Models\Room;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $rooms = $this->roomQuery($request)
            ->paginate(10);

        return view(
            'admin.rooms.index',
            compact('rooms')
        );
    }

    public function exportForm(Request $request)
    {
        $rooms = $this->roomQuery($request)
            ->paginate(10);

        return view('admin.rooms.export', compact('rooms'));
    }

    public function export(Request $request)
    {
        $rooms = $this->roomQuery($request)
            ->reorder('id');

        $filename = 'danh_sach_phong_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = [
            'Mã phòng',
            'Tầng',
            'Giá thuê',
            'Diện tích (m²)',
            'Số người hiện tại',
            'Trạng thái',
            'Tiện ích',
        ];

        $callback = function () use ($rooms, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            Csv::writeRow($file, $columns);

            foreach ($rooms->lazy(500) as $room) {
                $status = match ($room->status) {
                    'available' => 'Trống',
                    'occupied' => 'Đang thuê',
                    'maintenance' => 'Bảo trì',
                    default => ucfirst($room->status),
                };

                $amenities = $room->amenities
                    ->pluck('name')
                    ->filter()
                    ->implode(', ');

                Csv::writeRow($file, [
                    $room->room_code,
                    $room->floor,
                    number_format($room->price),
                    $room->area,
                    $room->current_people,
                    $status,
                    $amenities,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $amenities = Amenity::all();

        return view(
            'admin.rooms.create',
            compact('amenities')
        );
    }

    public function store(RoomRequest $request)
    {
        $data = $request->validated();
        $storedImage = null;

        if ($request->hasFile('image')) {
            $storedImage = $data['thumbnail'] = $request
                ->file('image')
                ->store('rooms', 'public');
        }

        unset($data['image']);

        try {
            DB::transaction(function () use ($data, $request) {
                $room = Room::create($data);
                $room->amenities()->sync($request->input('amenities', []));
            });
        } catch (\Throwable $exception) {
            if ($storedImage) {
                Storage::disk('public')->delete($storedImage);
            }
            throw $exception;
        }

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Thêm phòng thành công');
    }

    public function show(Room $room)
    {
        $room->load('amenities');

        return view(
            'admin.rooms.show',
            compact('room')
        );
    }

    public function edit(Room $room)
    {
        $room->load('amenities');

        $amenities = Amenity::all();

        return view(
            'admin.rooms.edit',
            compact('room', 'amenities')
        );
    }

    public function update(RoomRequest $request, Room $room)
    {
        $data = $request->validated();
        $oldImage = $room->thumbnail;
        $storedImage = null;

        if ($request->hasFile('image')) {
            $storedImage = $data['thumbnail'] = $request
                ->file('image')
                ->store('rooms', 'public');
        }

        unset($data['image']);

        try {
            DB::transaction(function () use ($data, $request, $room) {
                $room->update($data);
                $room->amenities()->sync($request->input('amenities', []));
            });
        } catch (\Throwable $exception) {
            if ($storedImage) {
                Storage::disk('public')->delete($storedImage);
            }
            throw $exception;
        }

        if ($storedImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Cập nhật phòng thành công');
    }

    public function destroy(Room $room)
    {
        $image = $room->thumbnail;

        $hasActiveContract = $room->activeContract()->exists();

        if ($hasActiveContract) {
            return redirect()
                ->route('admin.rooms.index')
                ->with(
                    'error',
                    'Không thể xóa phòng đang có hợp đồng hoạt động.'
                );
        }

        if ($room->utilityReadings()->exists()) {
            return redirect()
                ->route('admin.rooms.index')
                ->with(
                    'error',
                    'Không thể xóa phòng vì đã có dữ liệu điện nước.'
                );
        }

        if ($room->contracts()->exists()) {
            return redirect()
                ->route('admin.rooms.index')
                ->with(
                    'error',
                    'Không thể xóa phòng vì phòng đã có lịch sử hợp đồng.'
                );
        }

        $room->delete();

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Xóa phòng thành công');
    }

    private function roomQuery(Request $request)
    {
        $filters = $request->validate([
            'room_code' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in([Room::STATUS_AVAILABLE, Room::STATUS_OCCUPIED, Room::STATUS_MAINTENANCE])],
        ]);
        $query = Room::with('amenities')
            ->withCount(['contracts', 'utilityReadings']);

        if (! empty($filters['room_code'])) {
            $query->where(
                'room_code',
                'like',
                '%'.$filters['room_code'].'%'
            );
        }

        if (! empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        return $query->latest();
    }
}
