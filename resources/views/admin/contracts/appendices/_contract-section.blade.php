@php
    $canCreateAppendix = $contract->signed_at && in_array($contract->status, [
        \App\Models\Contract::STATUS_PENDING_DEPOSIT, \App\Models\Contract::STATUS_AWAITING_MOVE_IN,
        \App\Models\Contract::STATUS_ACTIVE, \App\Models\Contract::STATUS_EXPIRED,
    ], true) && !$contract->appendices->contains(fn ($item) => in_array($item->status, ['draft', 'pending_tenant'], true));
@endphp
@if($contract->signed_at)
<section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
        <div><h3 class="font-semibold text-slate-950">Phụ lục hợp đồng</h3><p class="mt-1 text-xs text-slate-500">Mọi bản đã gửi và phản hồi của khách đều được lưu lịch sử.</p></div>
        @if($canCreateAppendix)<a href="{{ route('admin.contracts.appendices.create', $contract) }}" class="inline-flex h-10 items-center rounded-lg bg-indigo-700 px-4 text-sm font-bold text-white">Lập phụ lục</a>@endif
    </div>
    <div class="divide-y divide-slate-100">
        @forelse($contract->appendices as $appendix)
            <article class="flex flex-col justify-between gap-3 p-5 sm:flex-row sm:items-center">
                <div><div class="flex flex-wrap items-center gap-2"><a href="{{ route('admin.contract-appendices.show', $appendix) }}" class="font-bold text-indigo-700">{{ $appendix->code }}</a><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $appendix->status_label }}</span></div><p class="mt-1 text-sm text-slate-700">{{ $appendix->title }}</p>@if($appendix->rejection_reason)<p class="mt-2 text-sm text-rose-700">Khách từ chối: {{ $appendix->rejection_reason }}</p>@endif</div>
                <a href="{{ route('admin.contract-appendices.show', $appendix) }}" class="text-sm font-bold text-indigo-700">Xem chi tiết →</a>
            </article>
        @empty
            <p class="p-5 text-sm text-slate-500">Chưa có phụ lục nào.</p>
        @endforelse
    </div>
</section>
@endif
