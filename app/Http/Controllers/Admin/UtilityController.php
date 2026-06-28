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

        $billingPeriodEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // 1. Dùng 'with' để lấy kèm theo Hợp đồng đang active (tránh N+1 query)
        $rooms = Room::with(['contracts' => function ($query) {
            $query->where('status', 'active');
        }])
            ->where('status', 'occupied')
            ->whereHas('contracts', function ($query) use ($billingPeriodEnd) {
                $query->where('status', 'active')
                    ->whereDate('start_date', '<=', $billingPeriodEnd);
            })
            ->get();

        $readings = [];
        foreach ($rooms as $room) {
            $lastMonth = $month == 1 ? 12 : $month - 1;
            $lastYear = $month == 1 ? $year - 1 : $year;

            $lastReading = UtilityReading::where('room_id', $room->id)
                ->where('month', $lastMonth)
                ->where('year', $lastYear)
                ->first();

            // 2. Lấy ngày bắt đầu của hợp đồng hiện tại
            $activeContract = $room->contracts->first();
            $startDate = $activeContract ? Carbon::parse($activeContract->start_date)->format('d/m/Y') : 'N/A';

            $readings[] = [
                'room_id' => $room->id,
                'room_name' => $room->room_code,
                'start_date' => $startDate, // Gắn thêm ngày bắt đầu thuê vào mảng
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

        // Lấy ngày cuối cùng của kỳ chốt số
        $billingPeriodEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // 1. Lấy danh sách các phòng đang cho thuê có hợp đồng active tính đến kỳ chốt
        // Đồng bộ logic với hàm create để Tiến độ chốt số (A/B phòng) chính xác tuyệt đối
        $totalRooms = Room::where('status', 'occupied')
            ->whereHas('contracts', function ($query) use ($billingPeriodEnd) {
                $query->where('status', 'active')
                    ->whereDate('start_date', '<=', $billingPeriodEnd);
            })
            ->count();

        // 2. Lấy dữ liệu chốt số của tháng hiện tại kèm hợp đồng active để hiển thị ngày thuê
        $readings = UtilityReading::with([
            'room.contracts' => function ($query) use ($billingPeriodEnd) {
                $query->where('status', 'active')
                    ->whereDate('start_date', '<=', $billingPeriodEnd)
                    ->orderByDesc('start_date');
            },
        ])
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
