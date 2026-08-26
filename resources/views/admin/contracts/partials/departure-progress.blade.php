@php
    if (! isset($departureSteps)) {
        $progressBeforeCheckout = in_array($contract->status, \App\Models\Contract::OPEN_OCCUPANCY_STATUSES, true);
        $progressCompleted = $contract->status === \App\Models\Contract::STATUS_COMPLETED;
        $progressOpenInvoices = $contract->invoices->filter(
            fn ($invoice) => in_array($invoice->status, [
                \App\Models\Invoice::STATUS_UNPAID,
                \App\Models\Invoice::STATUS_PARTIAL,
            ], true)
        );
        $progressDebtResolved = ! $progressBeforeCheckout && $progressOpenInvoices->isEmpty();
        $progressDepositResolved = (float) $contract->deposit_amount <= 0
            || $contract->deposit_resolution === \App\Models\Contract::DEPOSIT_NOT_REQUIRED
            || in_array($contract->deposit_resolution, [
                \App\Models\Contract::DEPOSIT_REFUNDED,
                \App\Models\Contract::DEPOSIT_DEDUCTED,
                \App\Models\Contract::DEPOSIT_RETAINED,
            ], true);
        $progressStep1Done = ! $progressBeforeCheckout && $progressDebtResolved;
        $progressStep2Done = ! $progressBeforeCheckout && $progressDepositResolved;
        $departureSteps = [
            ['number' => 1, 'title' => 'Bàn giao & chốt quyết toán', 'description' => 'Trả phòng, lập hóa đơn và xử lý công nợ', 'done' => $progressStep1Done || $progressCompleted, 'active' => ! $progressStep1Done && ! $progressCompleted],
            ['number' => 2, 'title' => 'Xử lý tiền cọc', 'description' => 'Hoàn hoặc khấu trừ tiền cọc', 'done' => $progressStep2Done || $progressCompleted, 'active' => $progressStep1Done && ! $progressStep2Done && ! $progressCompleted],
            ['number' => 3, 'title' => 'Hoàn tất hợp đồng', 'description' => 'Xác nhận hai bên hết nghĩa vụ', 'done' => $progressCompleted, 'active' => $progressStep1Done && $progressStep2Done && ! $progressCompleted],
        ];
    }
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm {{ $progressClass ?? '' }}">
    <div class="flex flex-col gap-2 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div><h3 class="font-bold text-slate-950">Tiến độ trả phòng</h3><p class="mt-1 text-xs text-slate-500">Trạng thái được lưu tự động; bạn có thể rời trang và quay lại tiếp tục bất cứ lúc nào.</p></div>
        @if($contract->status === \App\Models\Contract::STATUS_SETTLING)<span class="w-fit rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-700">Đang quyết toán</span>@endif
    </div>
    <div class="grid gap-3 p-5 sm:p-6 lg:grid-cols-3">
        @foreach($departureSteps as $step)
            <article class="rounded-xl border p-4 {{ $step['done'] ? 'border-emerald-200 bg-emerald-50/60' : ($step['active'] ? 'border-violet-300 bg-violet-50 ring-2 ring-violet-100' : 'border-slate-200 bg-slate-50 opacity-65') }}">
                <div class="flex items-start gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white {{ $step['done'] ? 'bg-emerald-600' : ($step['active'] ? 'bg-violet-600' : 'bg-slate-400') }}">{{ $step['done'] ? '✓' : $step['number'] }}</span>
                    <div><p class="text-xs font-bold uppercase tracking-wide {{ $step['done'] ? 'text-emerald-700' : ($step['active'] ? 'text-violet-700' : 'text-slate-500') }}">{{ $step['done'] ? 'Đã xong' : ($step['active'] ? 'Đang thực hiện' : 'Đang khóa') }}</p><h4 class="mt-1 font-bold text-slate-950">{{ $step['title'] }}</h4><p class="mt-1 text-xs leading-5 text-slate-500">{{ $step['description'] }}</p></div>
                </div>
            </article>
        @endforeach
    </div>
</section>
