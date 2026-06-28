<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\Setting;
use App\Models\UtilityReading;
use Carbon\Carbon;

class UtilityController extends Controller
{
    public function create(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $billingPeriodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $billingPeriodEnd = $billingPeriodStart->copy()->endOfMonth();

        // 1. Dùng 'with' để lấy kèm theo Hợp đồng đang active (tránh N+1 query)
        $rooms = Room::with(['contracts' => function ($query) use ($billingPeriodStart, $billingPeriodEnd) {
            $query->where('status', 'active')
                ->whereDate('start_date', '<=', $billingPeriodEnd)
                ->whereDate('end_date', '>=', $billingPeriodStart)
                ->orderByDesc('start_date');
        }])
            ->where('status', 'occupied')
            ->whereHas('contracts', function ($query) use ($billingPeriodStart, $billingPeriodEnd) {
                $query->where('status', 'active')
                    ->whereDate('start_date', '<=', $billingPeriodEnd)
                    ->whereDate('end_date', '>=', $billingPeriodStart);
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

        $setting = Setting::firstOrCreate([], [
            'electric_price' => 0,
            'water_price' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
        ]);

        $billingPeriodStart = Carbon::createFromDate($data['year'], $data['month'], 1)->startOfMonth();
        $billingPeriodEnd = $billingPeriodStart->copy()->endOfMonth();
        $invoiceDate = $billingPeriodEnd->toDateString();
        $dueDate = $billingPeriodEnd->copy()->addDays(7)->toDateString();
        $skippedInvoices = 0;

        foreach ($data['readings'] as $readingData) {
            $reading = UtilityReading::updateOrCreate(
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

            $contract = Contract::where('room_id', $readingData['room_id'])
                ->where('status', 'active')
                ->whereDate('start_date', '<=', $billingPeriodEnd)
                ->whereDate('end_date', '>=', $billingPeriodStart)
                ->orderByDesc('start_date')
                ->first();

            if (!$contract) {
                $skippedInvoices++;
                continue;
            }

            $electricityUsage = $readingData['electricity_new'] - $readingData['electricity_old'];
            $waterUsage = $readingData['water_new'] - $readingData['water_old'];
            $electricityFee = $electricityUsage * $setting->electric_price;
            $waterFee = $waterUsage * $setting->water_price;
            $roomFee = $contract->monthly_rent;
            $totalAmount = $roomFee + $electricityFee + $waterFee + $setting->internet_fee + $setting->service_fee;

            $invoice = Invoice::firstOrNew([
                'contract_id' => $contract->id,
                'month' => $data['month'],
                'year' => $data['year'],
            ]);

            $invoice->fill([
                'utility_reading_id' => $reading->id,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'room_fee' => $roomFee,
                'electricity_fee' => $electricityFee,
                'water_fee' => $waterFee,
                'internet_fee' => $setting->internet_fee,
                'service_fee' => $setting->service_fee,
                'total_amount' => $totalAmount,
                'status' => $invoice->exists ? $invoice->status : 'unpaid',
            ]);

            $invoice->save();
        }

        $message = 'Đã lưu chỉ số và tính tiền điện/nước thành công!';

        if ($skippedInvoices > 0) {
            $message .= " Có {$skippedInvoices} phòng chưa tạo được hóa đơn vì không tìm thấy hợp đồng active.";
        }

        return redirect()
            ->route('admin.utilities.index', ['month' => $data['month'], 'year' => $data['year']])
            ->with('success', $message);
    }

    // Màn hình 2: KIỂM TRA CHỈ SỐ
    public function index(Request $request)
    {
        // Lấy tháng/năm từ request (mặc định là tháng hiện tại)
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        // Lấy ngày cuối cùng của kỳ chốt số
        $billingPeriodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $billingPeriodEnd = $billingPeriodStart->copy()->endOfMonth();

        // 1. Lấy danh sách các phòng đang cho thuê có hợp đồng active tính đến kỳ chốt
        // Đồng bộ logic với hàm create để Tiến độ chốt số (A/B phòng) chính xác tuyệt đối
        $totalRooms = Room::where('status', 'occupied')
            ->whereHas('contracts', function ($query) use ($billingPeriodStart, $billingPeriodEnd) {
                $query->where('status', 'active')
                    ->whereDate('start_date', '<=', $billingPeriodEnd)
                    ->whereDate('end_date', '>=', $billingPeriodStart);
            })
            ->count();

        // 2. Lấy dữ liệu chốt số của tháng hiện tại kèm hợp đồng active để hiển thị ngày thuê
        $readings = UtilityReading::with([
            'room.contracts' => function ($query) use ($billingPeriodStart, $billingPeriodEnd) {
                $query->where('status', 'active')
                    ->whereDate('start_date', '<=', $billingPeriodEnd)
                    ->whereDate('end_date', '>=', $billingPeriodStart)
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

        $setting = Setting::firstOrCreate([], [
            'electric_price' => 0,
            'water_price' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
        ]);

        $totalElectricityFee = $totalElectricity * $setting->electric_price;
        $totalWaterFee = $totalWater * $setting->water_price;
        $totalUtilityFee = $totalElectricityFee + $totalWaterFee;

        // Truyền tất cả dữ liệu thật sang View
        return view('admin.utilities.index', compact(
            'month',
            'year',
            'totalRooms',
            'roomsRead',
            'totalElectricity',
            'totalWater',
            'totalElectricityFee',
            'totalWaterFee',
            'totalUtilityFee',
            'setting',
            'readings'
        ));
    }
}
