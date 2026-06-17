<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Http\Requests\RoomRequest;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Room::query();

        // Tìm theo mã phòng
        if ($request->room_code) {
            $query->where(
                'room_code',
                'like',
                '%' . $request->room_code . '%'
            );
        }

        // Tìm theo trạng thái
        if ($request->status) {
            $query->where('status', $request->status);
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.rooms.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoomRequest $request)
    {
        Room::create($request->validated());

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Thêm phòng thành công');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Room $room)
    {
        return view('admin.rooms.edit', compact('room'));
    }

    public function update(RoomRequest $request, Room $room)
    {
        $room->update($request->validated());

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Cập nhật phòng thành công');
    }

    public function destroy(Room $room)
    {
        if ($room->contracts()->exists()) {

            return redirect()
                ->route('admin.rooms.index')
                ->with(
                    'error',
                    'Không thể xóa phòng vì đã có hợp đồng'
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
