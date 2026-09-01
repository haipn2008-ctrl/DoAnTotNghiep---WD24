@extends('layouts.client.index')

@section('title', 'Lịch sử yêu cầu')

@section('content')

<div class="max-w-6xl mx-auto px-4 py-6">

    {{-- HEADER --}}
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
            HỢP ĐỒNG
        </p>

        <h1 class="mt-1 text-2xl font-bold text-slate-900">
            Lịch sử yêu cầu
        </h1>

    </div>

    {{-- FILTER --}}
    <div class="mb-5 flex flex-wrap gap-2">
        <button type="button"
                class="request-filter rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white"
                data-filter="all">
            Tất cả
        </button>

        <button type="button"
                class="request-filter rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600"
                data-filter="extension">
            Gia hạn
        </button>

        <button type="button"
                class="request-filter rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600"
                data-filter="termination">
            Trả phòng
        </button>
    </div>

    {{-- DANH SÁCH --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        @if($requests->isEmpty())

            <div class="px-6 py-16 text-center">

                <div class="text-4xl">
                    🕘
                </div>

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
                                    ↗
                                @else
                                    ↩
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
