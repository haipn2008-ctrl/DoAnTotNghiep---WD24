@extends('layouts.client.index')

@section('title', 'Lịch sử yêu cầu')
@section('page_title', 'Lịch sử yêu cầu')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    {{-- HEADER --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 px-6 py-7 text-white shadow-lg shadow-indigo-200/60 sm:px-8">
        <div class="absolute -right-12 -top-16 h-52 w-52 rounded-full bg-white/10"></div>
        <div class="relative flex items-center gap-4"><span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 sm:flex"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></span><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-indigo-100">
            HỢP ĐỒNG
        </p>

        <h1 class="mt-1 text-2xl font-bold sm:text-3xl">
            Lịch sử yêu cầu
        </h1>
        </div>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <button type="button"
                class="request-filter rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm"
                data-filter="all">
            Tất cả
        </button>

        <button type="button"
                class="request-filter rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600"
                data-filter="extension">
            Gia hạn
        </button>

        <button type="button"
                class="request-filter rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600"
                data-filter="termination">
            Trả phòng
        </button>
    </div>

    {{-- DANH SÁCH --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        @if($requests->isEmpty())

            <div class="px-6 py-16 text-center">

                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></span>

                <h3 class="mt-4 font-semibold text-slate-900">
                    Chưa có yêu cầu
                </h3>


            </div>

        @else

            @foreach($requests as $request)

                <div class="request-item border-b border-slate-100 p-5 last:border-b-0"
                     data-type="{{ $request['type'] }}">

                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                        {{-- LEFT --}}
                        <div class="flex gap-4">

                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl
                                {{ $request['type'] === 'extension'
                                    ? 'bg-indigo-50 text-indigo-600'
                                    : 'bg-orange-50 text-orange-600' }}">

                                @if($request['type'] === 'extension')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 5v4h4M7.5 16.5A7 7 0 0 0 19 11" /></svg>
                                @else
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7 4 12l5 5M4 12h11a5 5 0 0 1 5 5" /></svg>
                                @endif

                            </div>

                            <div>

                                <div class="flex flex-wrap items-center gap-2">

                                    <h3 class="font-semibold text-slate-900">
                                        {{ $request['type_label'] }}
                                    </h3>

                                    {{-- STATUS --}}
                                    @if($request['status'] === 'pending')

                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                            Chờ duyệt
                                        </span>

                                    @elseif($request['status'] === 'approved')

                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            Đã duyệt
                                        </span>

                                    @elseif($request['status'] === 'rejected')

                                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600">
                                            Từ chối
                                        </span>

                                    @else

                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">
                                            {{ $request['status'] }}
                                        </span>

                                    @endif

                                </div>

                                <p class="mt-1 text-sm text-slate-500">
                                    Hợp đồng
                                    <span class="font-medium text-slate-700">
                                        {{ $request['contract_code'] }}
                                    </span>

                                    · Phòng

                                    <span class="font-medium text-slate-700">
                                        {{ $request['room_code'] ?? '-' }}
                                    </span>
                                </p>

                            </div>

                        </div>

                        {{-- DATE --}}
                        <div class="text-sm text-slate-500">

                            Gửi ngày

                            <span class="font-medium text-slate-700">
                                {{ optional($request['created_at'])->format('d/m/Y H:i') }}
                            </span>

                        </div>

                    </div>


                    {{-- DETAIL --}}
                    <div class="mt-4 grid gap-4 rounded-lg bg-slate-50 p-4 md:grid-cols-3">

                        <div>

                            <p class="text-xs font-medium uppercase text-slate-400">
                                @if($request['type'] === 'extension')
                                    Đề nghị gia hạn đến
                                @else
                                    Ngày muốn trả phòng
                                @endif
                            </p>

                            <p class="mt-1 text-sm font-semibold text-slate-800">

                                @if($request['requested_date'])
                                    {{ \Carbon\Carbon::parse($request['requested_date'])->format('d/m/Y') }}
                                @else
                                    -
                                @endif

                            </p>

                        </div>


                        <div>

                            <p class="text-xs font-medium uppercase text-slate-400">
                                Lý do
                            </p>

                            <p class="mt-1 text-sm text-slate-700">
                                {{ $request['reason'] ?: 'Không có' }}
                            </p>

                        </div>


                        <div>

                            <p class="text-xs font-medium uppercase text-slate-400">
                                Phản hồi quản lý
                            </p>

                            <p class="mt-1 text-sm text-slate-700">
                                {{ $request['admin_note'] ?: 'Chưa có phản hồi' }}
                            </p>

                        </div>

                    </div>

                </div>

            @endforeach

        @endif

    </div>

</div>


{{-- FILTER JS --}}
<script>

document.addEventListener('DOMContentLoaded', function () {

    const buttons = document.querySelectorAll('.request-filter');
    const items = document.querySelectorAll('.request-item');

    buttons.forEach(button => {

        button.addEventListener('click', function () {

            const filter = this.dataset.filter;

            buttons.forEach(btn => {
                btn.classList.remove(
                    'bg-indigo-600',
                    'text-white'
                );

                btn.classList.add(
                    'bg-white',
                    'text-slate-600'
                );
            });

            this.classList.remove(
                'bg-white',
                'text-slate-600'
            );

            this.classList.add(
                'bg-indigo-600',
                'text-white'
            );

            items.forEach(item => {

                if (
                    filter === 'all' ||
                    item.dataset.type === filter
                ) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }

            });

        });

    });

});

</script>

@endsection
