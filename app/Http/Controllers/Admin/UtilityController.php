<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\FeeSchedule;
use App\Models\Room;
use App\Models\Setting;
use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UtilityController extends Controller
{
    public function create(Request $request)
    {
        $period = $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2000,2100',
            'mode' => 'nullable|in:final,checkpoint',
            'reading_date' => 'nullable|date',
            'room_id' => 'nullable|integer|exists:rooms,id',
        ]);

        $month = (int) ($period['month'] ?? Carbon::now()->month);
        $year = (int) ($period['year'] ?? Carbon::now()->year);
        $mode = $period['mode'] ?? 'final';

        $billingPeriodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $billingPeriodEnd = $billingPeriodStart->copy()->endOfMonth();
        $canFinalize = ! today()->lt($billingPeriodEnd->copy()->startOfDay());
        $recordDate = $billingPeriodEnd->toDateString();
        $checkpointDate = isset($period['reading_date'])
            ? Carbon::parse($period['reading_date'])->startOfDay()
            : (today()->betweenIncluded($billingPeriodStart, $billingPeriodEnd)
                ? today()
                : $billingPeriodEnd->copy()->startOfDay());
        if ($mode === 'checkpoint' && ! $checkpointDate->betweenIncluded($billingPeriodStart, $billingPeriodEnd)) {
            throw ValidationException::withMessages([
                'reading_date' => "Ngày ghi mốc phải nằm trong tháng {$month}/{$year}.",
            ]);
        }

        $rooms = Room::with(['contracts' => function ($query) use ($billingPeriodStart, $billingPeriodEnd) {
            $query->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED])
                ->whereDate('start_date', '<=', $billingPeriodEnd)
                ->where(fn ($query) => $query->where('status', Contract::STATUS_EXPIRED)->orWhereDate('end_date', '>=', $billingPeriodStart))
                ->orderByDesc('start_date');
        }])
            ->where('status', 'occupied')
            ->when($period['room_id'] ?? null, fn ($query, $roomId) => $query->whereKey($roomId))
            ->whereHas('contracts', function ($query) use ($billingPeriodStart, $billingPeriodEnd) {
                $query->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED])
                    ->whereDate('start_date', '<=', $billingPeriodEnd)
                    ->where(fn ($query) => $query->where('status', Contract::STATUS_EXPIRED)->orWhereDate('end_date', '>=', $billingPeriodStart));
            })
            ->orderBy('room_code')
            ->get();

        $roomIds = $rooms->pluck('id');
        $periodReadings = UtilityReading::with('invoice')
            ->whereIn('room_id', $roomIds)
            ->where('month', $month)
            ->where('year', $year)
            ->where('reading_type', 'periodic')
            ->get();

        // Lấy lần ghi gần nhất trước kỳ đang chọn, kể cả khi bị bỏ sót một vài tháng.
        $readings = [];
        foreach ($rooms as $room) {
            $activeContract = $room->contracts->first();
            $contractStart = $activeContract?->start_date?->toDateString();
            $periodicReading = $activeContract ? $periodReadings
                ->where('room_id', $room->id)
                ->first(fn ($reading) => (int) $reading->contract_id === $activeContract->id)
                ?? $periodReadings->where('room_id', $room->id)->first(fn ($reading) => $reading->contract_id === null && $reading->record_date?->toDateString() >= $contractStart
                ) : null;
            $currentReading = $mode === 'checkpoint' ? null : $periodicReading;
            $previousReading = $activeContract ? UtilityReading::where('room_id', $room->id)
                ->where(fn ($query) => $query->where('contract_id', $activeContract->id)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('contract_id')->whereDate('record_date', '>=', $contractStart)))
                ->when($mode !== 'checkpoint', fn ($query) => $query->where('reading_type', '!=', 'interim'))
                ->where(function ($query) use ($mode, $recordDate, $checkpointDate) {
                    $targetDate = $mode === 'checkpoint' ? $checkpointDate->toDateString() : $recordDate;
                    $query->whereDate('record_date', '<', $targetDate)
                        ->orWhere(fn ($sameDay) => $sameDay
                            ->whereDate('record_date', $targetDate)
                            ->whereIn('reading_type', ['handover', 'transfer_handover']));
                })
                ->latest('record_date')->latest('id')->first() : null;
            $startDate = $activeContract ? Carbon::parse($activeContract->start_date)->format('d/m/Y') : 'Không có';

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
                'last_period' => $previousReading
                    ? ($mode === 'checkpoint'
                        ? $previousReading->record_date?->format('d/m/Y')
                        : "{$previousReading->month}/{$previousReading->year}")
                    : null,
                'saved' => (bool) $currentReading,
                'status' => $currentReading?->status,
                'locked' => $periodicReading?->isLocked() || (bool) $periodicReading?->invoice,
                'editable' => $mode === 'checkpoint'
                    ? ! ($periodicReading?->isLocked() || (bool) $periodicReading?->invoice)
                    : (! $currentReading || $currentReading->isDraft()),
            ];
        }

        $savedCount = collect($readings)->where('saved', true)->count();

        return view('admin.utilities.create', compact(
            'readings',
            'month',
            'year',
            'mode',
            'recordDate',
            'checkpointDate',
            'savedCount',
            'canFinalize',
        ));
    }

    // Lưu chỉ số mới nhập
    public function store(Request $request)
    {
        $data = $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2000,2100',
            'intent' => 'nullable|in:draft,confirm,checkpoint',
            'reading_date' => 'nullable|date',
            'readings' => 'required|array',
            'readings.*.selected' => 'nullable|boolean',
            'readings.*.room_id' => 'required|distinct|exists:rooms,id',
            'readings.*.electricity_new' => 'nullable|integer|min:0',
            'readings.*.electricity_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'readings.*.water_new' => 'nullable|integer|min:0',
            'readings.*.water_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $selectedReadings = collect($data['readings'])
            ->filter(fn ($reading) => (bool) ($reading['selected'] ?? false));
        $isCheckpoint = ($data['intent'] ?? 'confirm') === 'checkpoint';
        $targetStatus = ($data['intent'] ?? 'confirm') === 'draft'
            ? UtilityReading::STATUS_DRAFT
            : UtilityReading::STATUS_CONFIRMED;

        if ($selectedReadings->isEmpty()) {
            throw ValidationException::withMessages([
                'readings' => 'Hãy chọn ít nhất một phòng đã nhập đủ chỉ số để lưu.',
            ]);
        }

        $periodStart = Carbon::createFromDate($data['year'], $data['month'], 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $recordDate = $isCheckpoint
            ? Carbon::parse($data['reading_date'] ?? now())->startOfDay()
            : $periodEnd->copy()->startOfDay();
        if ($isCheckpoint && ! $recordDate->betweenIncluded($periodStart, $periodEnd)) {
            throw ValidationException::withMessages([
                'reading_date' => "Ngày ghi mốc phải nằm trong tháng {$data['month']}/{$data['year']}.",
            ]);
        }
        $data['record_date'] = $recordDate->toDateString();

        if (! $isCheckpoint && $targetStatus === UtilityReading::STATUS_CONFIRMED && today()->lt($periodEnd->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'readings' => "Chưa đến ngày chốt điện nước. Kỳ {$data['month']}/{$data['year']} chỉ được xác nhận từ ngày {$periodEnd->format('d/m/Y')}; hiện tại chỉ có thể lưu nháp.",
            ]);
        }

        $newImages = [];
        $replacedImages = [];

        try {
            DB::transaction(function () use ($request, $data, $selectedReadings, $periodStart, $periodEnd, $targetStatus, $isCheckpoint, &$newImages, &$replacedImages) {
                foreach ($selectedReadings as $index => $readingData) {
                    $roomId = (int) $readingData['room_id'];

                    $room = Room::query()->lockForUpdate()->findOrFail($roomId);
                    $eligible = $room->status === Room::STATUS_OCCUPIED
                        && $room->contracts()->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED])
                            ->whereDate('start_date', '<=', $periodEnd)
                            ->where(fn ($query) => $query->where('status', Contract::STATUS_EXPIRED)->orWhereDate('end_date', '>=', $periodStart))
                            ->exists();

                    if (! $eligible) {
                        throw ValidationException::withMessages([
                            "readings.{$index}.room_id" => 'Phòng không còn hợp đồng hợp lệ trong kỳ đã chọn.',
                        ]);
                    }

                    if ($readingData['electricity_new'] === null || $readingData['water_new'] === null) {
                        throw ValidationException::withMessages([
                            "readings.{$index}.electricity_new" => 'Phòng đã chọn phải có đủ chỉ số điện và nước mới.',
                        ]);
                    }

                    $contract = $room->contracts()->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED])
                        ->whereDate('start_date', '<=', $periodEnd)
                        ->where(fn ($query) => $query->where('status', Contract::STATUS_EXPIRED)->orWhereDate('end_date', '>=', $periodStart))
                        ->latest('start_date')->firstOrFail();

                    $periodicReading = UtilityReading::query()
                        ->where('room_id', $roomId)
                        ->where(fn ($query) => $query->where('contract_id', $contract->id)
                            ->orWhere(fn ($legacy) => $legacy->whereNull('contract_id')->whereDate('record_date', '>=', $contract->start_date)))
                        ->where('month', $data['month'])
                        ->where('year', $data['year'])
                        ->where('reading_type', 'periodic')
                        ->lockForUpdate()->first();

                    if ($isCheckpoint && ($periodicReading?->isLocked() || (bool) $periodicReading?->invoice)) {
                        throw ValidationException::withMessages([
                            "readings.{$index}.room_id" => 'Không thể ghi thêm mốc vì kỳ này đã phát hành hóa đơn.',
                        ]);
                    }

                    if ($isCheckpoint) {
                        $latestCheckpoint = UtilityReading::query()
                            ->where('contract_id', $contract->id)
                            ->where('room_id', $roomId)
                            ->where('month', $data['month'])
                            ->where('year', $data['year'])
                            ->where('reading_type', 'interim')
                            ->latest('record_date')
                            ->latest('id')
                            ->lockForUpdate()
                            ->first();
                        if ($latestCheckpoint && $latestCheckpoint->record_date->gte($data['record_date'])) {
                            throw ValidationException::withMessages([
                                'reading_date' => 'Mốc mới phải sau mốc gừa kỳ gần nhất của phòng.',
                            ]);
                        }
                        $reading = new UtilityReading([
                            'room_id' => $roomId,
                            'contract_id' => $contract->id,
                            'month' => $data['month'],
                            'year' => $data['year'],
                            'reading_type' => 'interim',
                            'lifecycle_event_key' => "utility-checkpoint:{$contract->id}:{$data['record_date']}",
                        ]);
                    } else {
                        $reading = $periodicReading ?? new UtilityReading([
                            'room_id' => $roomId,
                            'contract_id' => $contract->id,
                            'month' => $data['month'],
                            'year' => $data['year'],
                            'reading_type' => 'periodic',
                        ]);
                    }

                    $previousStatus = $reading->exists ? $reading->status : null;
                    $previousSnapshot = $reading->exists ? $this->readingSnapshot($reading) : null;

                    if (! $isCheckpoint && $reading->exists && ($reading->isLocked() || $reading->invoice()->exists())) {
                        throw ValidationException::withMessages([
                            "readings.{$index}.room_id" => 'Không thể sửa chỉ số vì phòng này đã phát hành hóa đơn.',
                        ]);
                    }

                    if (! $isCheckpoint && $reading->exists && $reading->isConfirmed()) {
                        throw ValidationException::withMessages([
                            "readings.{$index}.room_id" => 'Chỉ số đã được xác nhận. Hãy chuyển về bản nháp trước khi sửa.',
                        ]);
                    }

                    $previousReading = UtilityReading::where('room_id', $roomId)
                        ->where(fn ($query) => $query->where('contract_id', $contract->id)
                            ->orWhere(fn ($legacy) => $legacy->whereNull('contract_id')->whereDate('record_date', '>=', $contract->start_date)))
                        ->when(! $isCheckpoint, fn ($query) => $query->where('reading_type', '!=', 'interim'))
                        ->where(function ($query) use ($data) {
                            $query->whereDate('record_date', '<', $data['record_date'])
                                ->orWhere(fn ($sameDay) => $sameDay
                                    ->whereDate('record_date', $data['record_date'])
                                    ->whereIn('reading_type', ['handover', 'transfer_handover']));
                        })
                        ->latest('record_date')->latest('id')->first();

                    if (! $reading->exists && ! $previousReading) {
                        throw ValidationException::withMessages([
                            "readings.{$index}.room_id" => 'Hợp đồng chưa có chỉ số bàn giao. Không thể mặc định chỉ số đầu bằng 0.',
                        ]);
                    }

                    if (! $isCheckpoint && $targetStatus === UtilityReading::STATUS_CONFIRMED) {
                        $this->ensurePreviousPeriodIsClosed(
                            $roomId,
                            $contract,
                            (int) $data['month'],
                            (int) $data['year'],
                            "readings.{$index}.room_id",
                        );
                    }

                    $electricityOld = $reading->exists ? $reading->electricity_old : ($previousReading?->electricity_new ?? 0);
                    $waterOld = $reading->exists ? $reading->water_old : ($previousReading?->water_new ?? 0);

                    if ($readingData['electricity_new'] < $electricityOld || $readingData['water_new'] < $waterOld) {
                        throw ValidationException::withMessages([
                            "readings.{$index}.electricity_new" => 'Chỉ số mới không được nhỏ hơn lần ghi gần nhất.',
                        ]);
                    }

                    $nextReading = $isCheckpoint ? null : UtilityReading::where('room_id', $roomId)
                        ->where(fn ($query) => $query->where('contract_id', $contract->id)
                            ->orWhere(fn ($legacy) => $legacy->whereNull('contract_id')->whereDate('record_date', '>=', $contract->start_date)))
                        ->where('reading_type', '!=', 'interim')
                        ->whereDate('record_date', '>', $data['record_date'])
                        ->orderBy('record_date')->orderBy('id')->lockForUpdate()->first();

                    if ($nextReading && ((int) $readingData['electricity_new'] !== $nextReading->electricity_old
                        || (int) $readingData['water_new'] !== $nextReading->water_old)) {
                        throw ValidationException::withMessages([
                            "readings.{$index}.electricity_new" => 'Không thể thay đổi vì chỉ số đầu kỳ tiếp theo phải khớp kỳ này.',
                        ]);
                    }

                    $payload = [
                        'electricity_old' => $electricityOld,
                        'electricity_new' => $readingData['electricity_new'],
                        'water_old' => $waterOld,
                        'water_new' => $readingData['water_new'],
                        'record_date' => $data['record_date'],
                        'reading_type' => $isCheckpoint ? 'interim' : 'periodic',
                        'status' => $targetStatus,
                    ];

                    foreach (['electricity', 'water'] as $type) {
                        $image = $request->file("readings.{$index}.{$type}_image");
                        $column = "{$type}_image";
                        if ($image) {
                            if ($reading->{$column}) {
                                $replacedImages[] = $reading->{$column};
                            }
                            $newImages[] = $payload[$column] = $image->store("utility-readings/{$type}", 'local');
                        }
                    }

                    $reading->fill($payload)->save();
                    $this->recordHistory(
                        $reading,
                        $request->user()?->id,
                        $reading->wasRecentlyCreated
                            ? ($isCheckpoint
                                ? 'checkpoint_recorded'
                                : ($targetStatus === UtilityReading::STATUS_CONFIRMED ? 'created_and_confirmed' : 'draft_created'))
                            : ($targetStatus === UtilityReading::STATUS_CONFIRMED ? 'confirmed' : 'draft_updated'),
                        $previousStatus,
                        $previousSnapshot,
                    );
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newImages);
            throw $exception;
        }

        Storage::disk('local')->delete($replacedImages);

        $savedCount = $selectedReadings->count();
        $message = $isCheckpoint
            ? "Đã ghi mốc giữa kỳ cho {$savedCount} phòng."
            : ($targetStatus === UtilityReading::STATUS_DRAFT
            ? "Đã lưu nháp chỉ số cho {$savedCount} phòng."
            : "Đã xác nhận chỉ số cho {$savedCount} phòng.");

        return redirect()
            ->route('admin.utilities.index', ['month' => $data['month'], 'year' => $data['year']])
            ->with('success', $message);
    }

    public function confirm(UtilityReading $reading)
    {
        $actorId = request()->user()?->id;

        $closingDate = Carbon::createFromDate($reading->year, $reading->month, 1)->endOfMonth()->startOfDay();
        if (today()->lt($closingDate)) {
            throw ValidationException::withMessages([
                'reading' => 'Chưa đến ngày chốt điện nước. Kỳ này chỉ được xác nhận từ ngày '.$closingDate->format('d/m/Y').'.',
            ]);
        }

        DB::transaction(function () use ($reading, $actorId): void {
            $reading = UtilityReading::query()->lockForUpdate()->findOrFail($reading->id);
            $this->ensurePeriodicReadingCanChangeStatus($reading);

            if ($reading->isLocked()) {
                throw ValidationException::withMessages(['reading' => 'Chỉ số đã bị khóa bởi hóa đơn.']);
            }

            if ($reading->isConfirmed()) {
                return;
            }

            $contract = $this->contractForReading($reading);
            $this->ensurePreviousPeriodIsClosed(
                (int) $reading->room_id,
                $contract,
                (int) $reading->month,
                (int) $reading->year,
                'reading',
            );

            $previousSnapshot = $this->readingSnapshot($reading);
            $reading->update([
                'record_date' => Carbon::createFromDate($reading->year, $reading->month, 1)->endOfMonth()->toDateString(),
                'status' => UtilityReading::STATUS_CONFIRMED,
            ]);
            $this->recordHistory(
                $reading,
                $actorId,
                'confirmed',
                UtilityReading::STATUS_DRAFT,
                $previousSnapshot,
            );
        });

        return back()->with('success', 'Đã xác nhận chỉ số điện nước.');
    }

    public function reopen(UtilityReading $reading)
    {
        $actorId = request()->user()?->id;

        DB::transaction(function () use ($reading, $actorId): void {
            $reading = UtilityReading::query()->lockForUpdate()->findOrFail($reading->id);
            $this->ensurePeriodicReadingCanChangeStatus($reading);

            if ($reading->isLocked() || $reading->invoice()->exists()) {
                throw ValidationException::withMessages(['reading' => 'Không thể mở lại chỉ số đã dùng để phát hành hóa đơn.']);
            }

            $contract = $this->contractForReading($reading);
            $hasClosedLaterPeriod = $this->periodicReadingsForContract($reading->room_id, $contract)
                ->whereDate('record_date', '>', $reading->record_date)
                ->whereIn('status', [UtilityReading::STATUS_CONFIRMED, UtilityReading::STATUS_LOCKED])
                ->exists();

            if ($hasClosedLaterPeriod) {
                throw ValidationException::withMessages([
                    'reading' => 'Phải mở lại các kỳ sau trước khi mở lại kỳ này.',
                ]);
            }

            $previousSnapshot = $this->readingSnapshot($reading);
            $reading->update(['status' => UtilityReading::STATUS_DRAFT]);
            $this->recordHistory(
                $reading,
                $actorId,
                'reopened',
                UtilityReading::STATUS_CONFIRMED,
                $previousSnapshot,
            );
        });

        return back()->with('success', 'Đã chuyển chỉ số về bản nháp để chỉnh sửa.');
    }

    private function ensurePeriodicReadingCanChangeStatus(UtilityReading $reading): void
    {
        if ($reading->reading_type !== 'periodic') {
            throw ValidationException::withMessages(['reading' => 'Chỉ số bàn giao hoặc trả phòng không dùng quy trình chốt số hằng tháng.']);
        }
    }

    private function ensurePreviousPeriodIsClosed(
        int $roomId,
        Contract $contract,
        int $month,
        int $year,
        string $errorKey,
    ): void {
        $period = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $previousPeriod = $period->copy()->subMonthNoOverflow();
        $readings = $this->periodicReadingsForContract($roomId, $contract);

        $previousReading = (clone $readings)
            ->where('month', $previousPeriod->month)
            ->where('year', $previousPeriod->year)
            ->first();

        if ($previousReading && in_array($previousReading->status, [
            UtilityReading::STATUS_CONFIRMED,
            UtilityReading::STATUS_LOCKED,
        ], true)) {
            return;
        }

        // Dữ liệu cũ có thể chưa có kỳ định kỳ nào. Cho phép kỳ đầu tiên lấy mốc
        // từ chỉ số bàn giao đã chốt; từ kỳ thứ hai trở đi bắt buộc liên tục từng tháng.
        $hasEarlierPeriodicReading = (clone $readings)
            ->whereDate('record_date', '<', $period)
            ->exists();
        $hasConfirmedHandover = UtilityReading::query()
            ->where('room_id', $roomId)
            ->where(function ($query) use ($contract): void {
                $query->where('contract_id', $contract->id)
                    ->orWhere(fn ($legacy) => $legacy
                        ->whereNull('contract_id')
                        ->whereDate('record_date', '>=', $contract->start_date));
            })
            ->whereIn('reading_type', ['handover', 'transfer_handover'])
            ->whereDate('record_date', '<=', $period->copy()->endOfMonth())
            ->whereIn('status', [UtilityReading::STATUS_CONFIRMED, UtilityReading::STATUS_LOCKED])
            ->exists();

        if (! $hasEarlierPeriodicReading && $hasConfirmedHandover) {
            return;
        }

        throw ValidationException::withMessages([
            $errorKey => "Phải chốt chỉ số tháng {$previousPeriod->month}/{$previousPeriod->year} trước khi chốt tháng {$month}/{$year}.",
        ]);
    }

    private function periodicReadingsForContract(int $roomId, Contract $contract)
    {
        return UtilityReading::query()
            ->where('room_id', $roomId)
            ->where(function ($query) use ($contract): void {
                $query->where('contract_id', $contract->id)
                    ->orWhere(fn ($legacy) => $legacy
                        ->whereNull('contract_id')
                        ->whereDate('record_date', '>=', $contract->start_date));
            })
            ->where('reading_type', 'periodic');
    }

    private function contractForReading(UtilityReading $reading): Contract
    {
        if ($reading->contract_id) {
            return Contract::query()->findOrFail($reading->contract_id);
        }

        return Contract::query()
            ->where('room_id', $reading->room_id)
            ->whereDate('start_date', '<=', $reading->record_date)
            ->whereDate('end_date', '>=', $reading->record_date)
            ->latest('start_date')
            ->firstOrFail();
    }

    private function recordHistory(
        UtilityReading $reading,
        ?int $actorId,
        string $action,
        ?string $fromStatus,
        ?array $previousSnapshot = null,
    ): void {
        $reading->histories()->create([
            'actor_id' => $actorId,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $reading->status,
            'snapshot' => $this->readingSnapshot($reading),
            'previous_snapshot' => $previousSnapshot,
            'performed_at' => now(),
        ]);
    }

    private function readingSnapshot(UtilityReading $reading): array
    {
        return [
            'room_id' => $reading->room_id,
            'contract_id' => $reading->contract_id,
            'month' => $reading->month,
            'year' => $reading->year,
            'reading_type' => $reading->reading_type,
            'record_date' => $reading->record_date?->toDateString(),
            'electricity_old' => $reading->electricity_old,
            'electricity_new' => $reading->electricity_new,
            'water_old' => $reading->water_old,
            'water_new' => $reading->water_new,
            'electricity_image' => $reading->electricity_image,
            'water_image' => $reading->water_image,
            'status' => $reading->status,
        ];
    }

    // Màn hình 2: KIỂM TRA CHỈ SỐ
    public function index(Request $request)
    {
        $period = $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2000,2100',
        ]);
        $month = (int) ($period['month'] ?? Carbon::now()->month);
        $year = (int) ($period['year'] ?? Carbon::now()->year);

        // Lấy ngày cuối cùng của kỳ chốt số
        $billingPeriodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $billingPeriodEnd = $billingPeriodStart->copy()->endOfMonth();

        // 1. Lấy danh sách các phòng đang cho thuê có hợp đồng active tính đến kỳ chốt
        // Đồng bộ logic với hàm create để Tiến độ chốt số (A/B phòng) chính xác tuyệt đối
        $totalRooms = Room::where('status', 'occupied')
            ->whereHas('contracts', function ($query) use ($billingPeriodStart, $billingPeriodEnd) {
                $query->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED])
                    ->whereDate('start_date', '<=', $billingPeriodEnd)
                    ->where(fn ($query) => $query->where('status', Contract::STATUS_EXPIRED)->orWhereDate('end_date', '>=', $billingPeriodStart));
            })
            ->count();

        // 2. Lấy dữ liệu chốt số của tháng hiện tại kèm hợp đồng active để hiển thị ngày thuê
        $readings = UtilityReading::with([
            'room.contracts' => function ($query) use ($billingPeriodStart, $billingPeriodEnd) {
                $query->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED])
                    ->whereDate('start_date', '<=', $billingPeriodEnd)
                    ->where(fn ($query) => $query->where('status', Contract::STATUS_EXPIRED)->orWhereDate('end_date', '>=', $billingPeriodStart))
                    ->orderByDesc('start_date');
            },
            'histories.actor',
        ])
            ->where('month', $month)
            ->where('year', $year)
            ->where('reading_type', 'periodic')
            ->get();

        $checkpoints = UtilityReading::with(['room', 'contract', 'histories.actor'])
            ->where('month', $month)
            ->where('year', $year)
            ->where('reading_type', 'interim')
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->get();

        // 3. Tính toán các con số thống kê
        $billableReadings = $readings->whereIn('status', [
            UtilityReading::STATUS_CONFIRMED,
            UtilityReading::STATUS_LOCKED,
        ]);
        $roomsRead = $billableReadings->pluck('room_id')->unique()->count();

        // Tính tổng điện/nước tiêu thụ (Số mới - Số cũ)
        $totalElectricity = $billableReadings->sum(function ($reading) {
            return $reading->electricity_new - $reading->electricity_old;
        });

        $totalWater = $billableReadings->sum(function ($reading) {
            return $reading->water_new - $reading->water_old;
        });

        $currentSetting = Setting::currentOrCreate([
            'electric_price' => 0,
            'water_price' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
        ]);
        $setting = FeeSchedule::forPeriod($billingPeriodStart) ?? $currentSetting;

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
            'readings',
            'checkpoints',
        ));
    }

    public function image(UtilityReading $reading, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['electricity', 'water'], true), 404);
        $path = $reading->{$type.'_image'};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
