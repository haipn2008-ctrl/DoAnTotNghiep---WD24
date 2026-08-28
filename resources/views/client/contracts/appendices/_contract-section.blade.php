@if($contract->appendices->where('status', '!=', \App\Models\ContractAppendix::STATUS_DRAFT)->isNotEmpty())
<section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-5"><h3 class="font-semibold text-slate-950">Phụ lục hợp đồng</h3><p class="mt-1 text-sm text-slate-500">Kiểm tra các nội dung bổ sung và phản hồi phụ lục đang chờ.</p></div>
    <div class="divide-y divide-slate-100">
        @foreach($contract->appendices->where('status', '!=', \App\Models\ContractAppendix::STATUS_DRAFT) as $appendix)
            <article class="flex flex-col justify-between gap-3 p-5 sm:flex-row sm:items-center">
                <div><div class="flex flex-wrap items-center gap-2"><p class="font-bold text-slate-950">{{ $appendix->code }}</p><span class="rounded-full {{ $appendix->status === 'pending_tenant' ? 'bg-amber-100 text-amber-800' : ($appendix->status === 'accepted' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700') }} px-2.5 py-1 text-xs font-semibold">{{ $appendix->status_label }}</span></div><p class="mt-1 text-sm text-slate-600">{{ $appendix->title }}</p></div>
                <a href="{{ route('client.contract-appendices.show', $appendix) }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-700 px-4 text-sm font-bold text-white">{{ $appendix->status === 'pending_tenant' ? 'Kiểm tra và phản hồi' : 'Xem chi tiết' }}</a>
            </article>
        @endforeach
    </div>
</section>
@endif
