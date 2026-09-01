@php
    $canCreateAppendix = $contract->signed_at && in_array($contract->status, [
        \App\Models\Contract::STATUS_PENDING_DEPOSIT, \App\Models\Contract::STATUS_AWAITING_MOVE_IN,
        \App\Models\Contract::STATUS_ACTIVE, \App\Models\Contract::STATUS_EXPIRED,
    ], true) && !$contract->appendices->contains(fn ($item) => in_array($item->status, ['draft', 'pending_tenant'], true));
@endphp
@if($contract->signed_at)
<section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3.5">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600"><i class="bx bx-file-blank text-lg"></i></span>
            <div>
                <h3 class="text-sm font-semibold text-slate-950">Phụ lục hợp đồng <span class="ml-1 font-normal text-slate-400">({{ $contract->appendices->count() }})</span></h3>
                @if($contract->appendices->isEmpty())<p class="mt-0.5 text-xs text-slate-500">Chưa có phụ lục.</p>@else<p class="mt-0.5 text-xs text-slate-500">Lưu đầy đủ lịch sử gửi và phản hồi.</p>@endif
            </div>
        </div>
        @if($canCreateAppendix)<a href="{{ route('admin.contracts.appendices.create', $contract) }}" class="inline-flex h-10 items-center rounded-lg bg-indigo-700 px-4 text-sm font-bold text-white">Lập phụ lục</a>@endif
    </div>
    @if($contract->appendices->isNotEmpty())
    <div class="divide-y divide-slate-100 border-t border-slate-200">
        @foreach($contract->appendices as $appendix)
            <article class="flex flex-col justify-between gap-3 p-5 sm:flex-row sm:items-center">
                <div><div class="flex flex-wrap items-center gap-2"><a href="{{ route('admin.contract-appendices.show', $appendix) }}" class="font-bold text-indigo-700">{{ $appendix->code }}</a><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $appendix->status_label }}</span></div><p class="mt-1 text-sm text-slate-700">{{ $appendix->title }}</p>@if($appendix->rejection_reason)<p class="mt-2 text-sm text-rose-700">Khách từ chối: {{ $appendix->rejection_reason }}</p>@endif</div>
                <a href="{{ route('admin.contract-appendices.show', $appendix) }}" class="text-sm font-bold text-indigo-700">Xem chi tiết →</a>
            </article>
        @endforeach
    </div>
    @endif
</section>
@endif
