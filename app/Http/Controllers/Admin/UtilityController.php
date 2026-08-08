<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Setting;
use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UtilityController extends Controller
{
    public function create(Request $request)
    {
        $period = $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2000,2100',
        ]);

        $month = (int) ($period['month'] ?? Carbon::now()->month);
        $year = (int) ($period['year'] ?? Carbon::now()->year);

        $billingPeriodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $billingPeriodEnd = $billingPeriodStart->copy()->endOfMonth();

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
            ->orderBy('room_code')
            ->get();

        $roomIds = $rooms->pluck('id');
        $currentReadings = UtilityReading::with('invoice')
            ->whereIn('room_id', $roomIds)
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('room_id');

        // Lấy lần ghi gần nhất trước kỳ đang chọn, kể cả khi bị bỏ sót một vài tháng.
        $previousReadings = UtilityReading::whereIn('room_id', $roomIds)
            ->where(function ($query) use ($month, $year) {
                $query->where('year', '<', $year)
                    ->orWhere(function ($query) use ($month, $year) {
                        $query->where('year', $year)->where('month', '<', $month);
                    });
            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->unique('room_id')
            ->keyBy('room_id');

        $readings = [];
        foreach ($rooms as $room) {
            $currentReading = $currentReadings->get($room->id);
            $previousReading = $previousReadings->get($room->id);
            $activeContract = $room->contracts->first();
            $startDate = $activeContract ? Carbon::parse($activeContract->start_date)->format('d/m/Y') : 'N/A';

            $readings[] = [
                'room_id' => $room->id,
                'room_name' => $room->room_code,
                'start_date' => $startDate,
                'electricity_old' => $currentReading?->electricity_old ?? $previousReading?->electricity_new ?? 0,
                'electricity_new' => $currentReading?->electricity_new,
                'electricity_image' => $currentReading?->electricity_image,
                'water_old' => $currentReading?->water_old ?? $previousReading?->water_new ?? 0,
                'water_new' => $currentReading?->water_new,
                'water_image' => $currentReading?->water_image,
                'last_period' => $previousReading ? "{$previousReading->month}/{$previousReading->year}" : null,
                'saved' => (bool) $currentReading,
                'locked' => (bool) $currentReading?->invoice,
            ];
        }

        $savedCount = $currentReadings->count();

        return view('admin.utilities.create', compact('readings', 'month', 'year', 'savedCount'));
    }

    // Lưu chỉ số mới nhập
    public function store(Request $request)
    {
        $data = $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2000,2100',
            'readings' => 'required|array',
            'readings.*.selected' => 'nullable|boolean',
            'readings.*.room_id' => 'required|exists:rooms,id',
            'readings.*.electricity_new' => 'nullable|integer|min:0',
            'readings.*.electricity_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'readings.*.water_new' => 'nullable|integer|min:0',
            'readings.*.water_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $selectedReadings = collect($data['readings'])
            ->filter(fn ($reading) => (bool) ($reading['selected'] ?? false));

        if ($selectedReadings->isEmpty()) {
            throw ValidationException::withMessages([
                'readings' => 'Hãy chọn ít nhất một phòng đã nhập đủ chỉ số để lưu.',
            ]);
        }

        $periodStart = Carbon::createFromDate($data['year'], $data['month'], 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $eligibleRoomIds = Room::where('status', 'occupied')
            ->whereHas('contracts', function ($query) use ($periodStart, $periodEnd) {
                $query->where('status', 'active')
                    ->whereDate('start_date', '<=', $periodEnd)
                    ->whereDate('end_date', '>=', $periodStart);
            })
            ->pluck('id');

        DB::transaction(function () use ($request, $data, $selectedReadings, $eligibleRoomIds) {
            foreach ($selectedReadings as $index => $readingData) {
                $roomId = (int) $readingData['room_id'];

                if (! $eligibleRoomIds->contains($roomId)) {
                    throw ValidationException::withMessages([
                        "readings.{$index}.room_id" => 'Phòng không còn hợp đồng hợp lệ trong kỳ đã chọn.',
                    ]);
                }

                if ($readingData['electricity_new'] === null || $readingData['water_new'] === null) {
                    throw ValidationException::withMessages([
                        "readings.{$index}.electricity_new" => 'Phòng đã chọn phải có đủ chỉ số điện và nước mới.',
                    ]);
                }

                $reading = UtilityReading::firstOrNew([
                    'room_id' => $roomId,
                    'month' => $data['month'],
                    'year' => $data['year'],
                ]);

                if ($reading->exists && $reading->invoice()->exists()) {
                    throw ValidationException::withMessages([
                        "readings.{$index}.room_id" => 'Không thể sửa chỉ số vì phòng này đã phát hành hóa đơn.',
                    ]);
                }

                $previousReading = UtilityReading::where('room_id', $roomId)
                    ->where(function ($query) use ($data) {
                        $query->where('year', '<', $data['year'])
                            ->orWhere(function ($query) use ($data) {
                                $query->where('year', $data['year'])->where('month', '<', $data['month']);
                            });
                    })
                    ->orderByDesc('year')
                    ->orderByDesc('month')
                    ->first();

                $electricityOld = $reading->exists ? $reading->electricity_old : ($previousReading?->electricity_new ?? 0);
                $waterOld = $reading->exists ? $reading->water_old : ($previousReading?->water_new ?? 0);

                if ($readingData['electricity_new'] < $electricityOld || $readingData['water_new'] < $waterOld) {
                    throw ValidationException::withMessages([
                        "readings.{$index}.electricity_new" => 'Chỉ số mới không được nhỏ hơn lần ghi gần nhất.',
                    ]);
                }

                $payload = [
                    'electricity_old' => $electricityOld,
                    'electricity_new' => $readingData['electricity_new'],
                    'water_old' => $waterOld,
                    'water_new' => $readingData['water_new'],
                    'record_date' => now()->toDateString(),
                    'status' => 'confirmed',
                ];

                foreach (['electricity', 'water'] as $type) {
                    $image = $request->file("readings.{$index}.{$type}_image");
                    $column = "{$type}_image";
                    if ($image) {
                        if ($reading->{$column}) {
                            Storage::disk('public')->delete($reading->{$column});
                        }
                        $payload[$column] = $image->store("utility-readings/{$type}", 'public');
                    }
                }

                $reading->fill($payload)->save();
            }
        });

        $savedCount = $selectedReadings->count();
        $message = "Đã lưu chỉ số cho {$savedCount} phòng. Bạn có thể tiếp tục nhập các phòng còn lại bất cứ lúc nào.";

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
