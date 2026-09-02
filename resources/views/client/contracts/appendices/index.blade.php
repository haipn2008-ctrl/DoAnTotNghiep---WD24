@extends('layouts.client.index')

@section('title', 'Lịch sử phụ lục | Cổng khách thuê')
@section('page_title', 'Lịch sử phụ lục')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 p-6 text-white shadow-lg shadow-indigo-200/60 sm:p-8">
            <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/10"></div>
            <div class="relative flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
                <div class="flex items-center gap-4">
                    <span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 sm:flex"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h7.5L18 7.5v12.75H6.75V3.75Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75V7.5H18M9.5 11h5M9.5 14.25h5M9.5 17.5h3" /></svg></span>
                    <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-indigo-100">Hợp đồng {{ $contract->contract_code }}</p><h2 class="mt-1 text-2xl font-bold sm:text-3xl">Lịch sử phụ lục</h2><p class="mt-2 text-sm text-indigo-100">Theo dõi các thay đổi và thỏa thuận bổ sung của hợp đồng.</p></div>
                </div>
                <a href="{{ route('client.contracts.show', $contract) }}" class="inline-flex h-11 w-fit items-center justify-center gap-2 rounded-xl border border-white/20 bg-white px-4 text-sm font-bold text-indigo-700 shadow-sm hover:bg-indigo-50"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m5-5-5 5 5 5" /></svg>Quay lại hợp đồng</a>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6"><div><h3 class="font-bold text-slate-900">Danh sách phụ lục</h3><p class="mt-0.5 text-sm text-slate-500">Sắp xếp theo thời gian cập nhật của hợp đồng.</p></div><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $contract->appendices->count() }} phụ lục</span></div>
            <div class="divide-y divide-slate-100">
                @forelse($contract->appendices as $appendix)
                    <article class="group flex flex-col justify-between gap-4 p-5 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:px-6">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h7.5L18 7.5v12.75H6.75V3.75Z" /><path stroke-linecap="round" d="M9.5 12h5M9.5 15.5h3" /></svg></span>
                            <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-slate-950">{{ $appendix->code }}</p>
                                <span class="rounded-full {{ $appendix->status === 'pending_tenant' ? 'bg-amber-100 text-amber-800' : ($appendix->status === 'accepted' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700') }} px-2.5 py-1 text-xs font-semibold">{{ $appendix->status_label }}</span>
                            </div>
                            <p class="mt-1 truncate text-sm text-slate-600">{{ $appendix->title }}</p>
                            </div>
                        </div>
                        <a href="{{ route('client.contract-appendices.show', $appendix) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-bold text-indigo-700 hover:bg-indigo-100">{{ $appendix->status === 'pending_tenant' ? 'Kiểm tra và phản hồi' : 'Xem chi tiết' }}<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg></a>
                    </article>
                @empty
                    <div class="px-6 py-14 text-center"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h7.5L18 7.5v12.75H6.75V3.75Z" /><path stroke-linecap="round" d="M9.5 12h5M9.5 15.5h3" /></svg></span><p class="mt-4 font-semibold text-slate-800">Hợp đồng chưa có phụ lục</p><p class="mt-1 text-sm text-slate-500">Các phụ lục được lập sau này sẽ hiển thị tại đây.</p></div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
