<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\UtilityReading;
use Carbon\Carbon;

class UtilityController extends Controller
{
    public function create(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $rooms = Room::where('status', 'occupied')->get();

        $readings = [];
        foreach ($rooms as $room) {
            $lastMonth = $month == 1 ? 12 : $month - 1;
            $lastYear = $month == 1 ? $year - 1 : $year;

            $lastReading = UtilityReading::where('room_id', $room->id)
                ->where('month', $lastMonth)
                ->where('year', $lastYear)
                ->first();

            $readings[] = [
                'room_id' => $room->id,
                'room_name' => $room->room_code,
                'electricity_old' => $lastReading ? $lastReading->electricity_new : 0,
                'water_old' => $lastReading ? $lastReading->water_new : 0,
            ];
        }

        return view('admin.utilities.create', compact('readings', 'month', 'year'));
    }

    // Lưu chỉ số mới nhập
    public function store(Request $request)
    {
        $data = $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
            'readings' => 'required|array',
            'readings.*.room_id' => 'required|exists:rooms,id',
            'readings.*.electricity_old' => 'required|numeric',
            'readings.*.electricity_new' => 'required|numeric|gte:readings.*.electricity_old',
            'readings.*.water_old' => 'required|numeric',
            'readings.*.water_new' => 'required|numeric|gte:readings.*.water_old',
        ]);

        foreach ($data['readings'] as $readingData) {
            UtilityReading::updateOrCreate(
                [
                    'room_id' => $readingData['room_id'],
                    'month' => $data['month'],
                    'year' => $data['year']
                ],
                [
                    'electricity_old' => $readingData['electricity_old'],
                    'electricity_new' => $readingData['electricity_new'],
                    'water_old' => $readingData['water_old'],
                    'water_new' => $readingData['water_new'],
                ]
            );
        }

        return redirect()->route('admin.utilities.index')->with('success', 'Đã lưu chỉ số thành công!');
    }

    // Màn hình 2: KIỂM TRA CHỈ SỐ
    public function index(Request $request)
    {
        // Lấy tháng/năm từ request (mặc định là tháng hiện tại)
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        // 1. Lấy danh sách các phòng đang cho thuê (để biết tổng số phòng cần chốt)
        $totalRooms = Room::where('status', 'occupied')->count();

        // 2. Lấy dữ liệu chốt số của tháng hiện tại
        $readings = UtilityReading::with('room')
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        // 3. Tính toán các con số thống kê
        $roomsRead = $readings->count(); // Số phòng đã nhập chỉ số

        // Tính tổng điện/nước tiêu thụ (Số mới - Số cũ)
        $totalElectricity = $readings->sum(function ($reading) {
            return $reading->electricity_new - $reading->electricity_old;
        });

        $totalWater = $readings->sum(function ($reading) {
            return $reading->water_new - $reading->water_old;
        });

        // Truyền tất cả dữ liệu thật sang View
        return view('admin.utilities.index', compact(
            'month',
            'year',
            'totalRooms',
            'roomsRead',
            'totalElectricity',
            'totalWater',
            'readings'
        ));
    }
}
