@extends('layouts.client.index')

@section('title', 'Lịch sử phụ lục | Cổng khách thuê')
@section('page_title', 'Lịch sử phụ lục')

@section('content')
    <div class="space-y-5">
        <div>
            <a href="{{ route('client.contracts.show', $contract) }}" class="text-sm font-semibold text-indigo-700">← Hợp đồng {{ $contract->contract_code }}</a>
            <h2 class="mt-2 text-2xl font-bold text-slate-950">Lịch sử phụ lục</h2>
            <p class="mt-1 text-sm text-slate-500">Các phụ lục đã gửi và trạng thái phản hồi của bạn.</p>
        </div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse($contract->appendices as $appendix)
                    <article class="flex flex-col justify-between gap-3 p-5 sm:flex-row sm:items-center">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-slate-950">{{ $appendix->code }}</p>
                                <span class="rounded-full {{ $appendix->status === 'pending_tenant' ? 'bg-amber-100 text-amber-800' : ($appendix->status === 'accepted' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700') }} px-2.5 py-1 text-xs font-semibold">{{ $appendix->status_label }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $appendix->title }}</p>
                        </div>
                        <a href="{{ route('client.contract-appendices.show', $appendix) }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-700 px-4 text-sm font-bold text-white">{{ $appendix->status === 'pending_tenant' ? 'Kiểm tra và phản hồi' : 'Xem chi tiết' }}</a>
                    </article>
                @empty
                    <p class="p-8 text-center text-sm text-slate-500">Hợp đồng chưa có phụ lục.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
