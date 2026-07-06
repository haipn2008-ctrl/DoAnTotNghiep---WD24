<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Models\Amenity;
use App\Models\Contract;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Room::query()->with('amenities');

        if ($request->room_code) {
            $query->where(
                'room_code',
                'like',
                '%' . $request->room_code . '%'
            );
        }

        if ($request->status) {
            $query->where(
                'status',
                $request->status
            );
        }

        $rooms = $query
            ->latest()
            ->paginate(10);

        return view(
            'admin.rooms.index',
            compact('rooms')
        );
    }

    /**
     * Form xuất CSV
     */
    public function exportForm()
    {
        $rooms = Room::with('amenities')
            ->latest()
            ->paginate(10);

        return view('admin.rooms.export', compact('rooms'));
    }

    /**
     * Xuất CSV
     */
    public function export()
    {
        $rooms = Room::with('amenities')
            ->latest()
            ->get();

        $filename = 'danh_sach_phong_' . now()->format('Ymd_His') . '.csv';

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

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, $columns);

            foreach ($rooms as $room) {

                $status = match ($room->status) {

                    Room::STATUS_AVAILABLE => 'Trống',

                    Room::STATUS_OCCUPIED => 'Đang thuê',

                    Room::STATUS_MAINTENANCE => 'Bảo trì',

                    default => ucfirst($room->status),
                };

                $amenities = $room->amenities
                    ->pluck('name')
                    ->filter()
                    ->implode(', ');

                fputcsv($file, [

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

    /**
     * Form tạo phòng
     */
    public function create()
    {
        $amenities = Amenity::all();

        return view(
            'admin.rooms.create',
            compact('amenities')
        );
    }

    /**
     * Lưu phòng
     */
    public function store(RoomRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {

            $data['thumbnail'] = $request
                ->file('image')
                ->store('rooms', 'public');
        }

        $room = Room::create($data);

        $room->amenities()->sync(
            $request->input('amenities', [])
        );

        return redirect()
            ->route('admin.rooms.index')
            ->with(
                'success',
                'Thêm phòng thành công'
            );
    }

    /**
     * Chi tiết phòng
     */
    public function show(Room $room)
    {
        $room->load('amenities');

        return view(
            'admin.rooms.show',
            compact('room')
        );
    }

    /**
     * Form sửa phòng
     */
    public function edit(Room $room)
    {
        $room->load('amenities');

        $amenities = Amenity::all();

        return view(
            'admin.rooms.edit',
            compact(
                'room',
                'amenities'
            )
        );
    }

    /**
     * Cập nhật phòng
     */
    public function update(RoomRequest $request, Room $room)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {

            $data['thumbnail'] = $request
                ->file('image')
                ->store('rooms', 'public');
        }

        $room->update($data);

        $room->amenities()->sync(
            $request->input('amenities', [])
        );

        return redirect()
            ->route('admin.rooms.index')
            ->with(
                'success',
                'Cập nhật phòng thành công'
            );
    }

    /**
     * Xóa phòng
     */
    public function destroy(Room $room)
    {
        $hasActiveContract = $room->contracts()
            ->where('status', Contract::STATUS_ACTIVE)
            ->exists();

        if ($hasActiveContract) {

            return redirect()
                ->route('admin.rooms.index')
                ->with(
                    'error',
                    'Không thể xóa phòng đang có người thuê'
                );
        }

        $room->delete();

        return redirect()
            ->route('admin.rooms.index')
            ->with(
                'success',
                'Xóa phòng thành công'
            );
    }
}
