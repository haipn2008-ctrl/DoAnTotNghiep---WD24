@extends('layouts.client.index')

@section('title', 'Hợp đồng của tôi | Cổng khách thuê')
@section('page_title', 'Hợp đồng của tôi')

@section('content')

<style>
    .contract-info-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 16px 28px;
        align-items: start;
    }

    @media (max-width: 900px) {
        .contract-info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 560px) {
        .contract-info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="mx-auto max-w-6xl">

    {{-- HEADER --}}
    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-indigo-500">Hợp đồng</p>
        <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-950">Hợp đồng của tôi</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Xem nhanh phòng thuê, thời hạn và trạng thái các hợp đồng của bạn.
                </p>
            </div>

            @if($tenant && !$contracts->isEmpty())
                <div class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    {{ $contracts->count() }} hợp đồng
                </div>
            @endif
        </div>
    </div>

    @if(!$tenant)
        <div class="rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-xl">📄</div>
            <h2 class="mt-4 text-lg font-bold text-slate-900">Chưa có thông tin khách thuê</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                Tài khoản của bạn chưa được liên kết với hồ sơ khách thuê.
            </p>
        </div>

    @elseif($contracts->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-xl">📄</div>
            <h2 class="mt-4 text-lg font-bold text-slate-900">Chưa có hợp đồng</h2>
            <p class="mt-2 text-sm text-slate-500">Hiện tại bạn chưa có hợp đồng thuê phòng.</p>
        </div>

    @else
    <div class="space-y-5">
        @foreach($contracts as $contract)
            @php
                $statusConfig = match($contract->status) {
                    'pending_signature' => [
                        'text' => 'Chờ bạn ký',
                        'class' => 'bg-amber-100 text-amber-700 ring-amber-200',
                        'dot' => 'bg-amber-500'
                    ],
                    'signed' => [
                        'text' => 'Đã ký',
                        'class' => 'bg-blue-100 text-blue-700 ring-blue-200',
                        'dot' => 'bg-blue-500'
                    ],
                    'deposit_paid' => [
                        'text' => 'Đã đóng cọc',
                        'class' => 'bg-cyan-100 text-cyan-700 ring-cyan-200',
                        'dot' => 'bg-cyan-500'
                    ],
                    'active' => [
                        'text' => 'Đang hiệu lực',
                        'class' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                        'dot' => 'bg-emerald-500'
                    ],
                    'expired' => [
                        'text' => 'Đã hết hạn',
                        'class' => 'bg-orange-100 text-orange-700 ring-orange-200',
                        'dot' => 'bg-orange-500'
                    ],
                    'terminated' => [
                        'text' => 'Đã chấm dứt',
                        'class' => 'bg-rose-100 text-rose-700 ring-rose-200',
                        'dot' => 'bg-rose-500'
                    ],
                    'deposit_returned' => [
                        'text' => 'Đã hoàn cọc',
                        'class' => 'bg-sky-100 text-sky-700 ring-sky-200',
                        'dot' => 'bg-sky-500'
                    ],
                    default => [
                        'text' => 'Không xác định',
                        'class' => 'bg-slate-100 text-slate-600 ring-slate-200',
                        'dot' => 'bg-slate-400'
                    ],
                };
            @endphp

            <article
                class="group overflow-hidden rounded-2xl border border-slate-200
                       bg-white shadow-sm transition-all duration-200
                       hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">

                {{-- HEADER --}}
                <div class="flex flex-col gap-4 px-6 py-5
                            sm:flex-row sm:items-center sm:justify-between">

                    <div class="flex min-w-0 items-center gap-4">

                        {{-- ICON --}}
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center
                                    rounded-xl bg-indigo-100 text-indigo-600
                                    ring-1 ring-indigo-200">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5
                                        a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375
                                        0 0010.125 2.25H8.25m0 12.75h7.5m-7.5
                                        3h7.5M10.5 2.25H5.625c-.621 0-1.125.504-1.125
                                        1.125v17.25c0 .621.504 1.125 1.125
                                        1.125h12.75c.621 0 1.125-.504
                                        1.125-1.125V11.625a9 9 0 00-9-9z"/>
                            </svg>
                        </div>

                        <div class="min-w-0">

                            <div class="flex flex-wrap items-center gap-2">

                                <h2 class="text-lg font-bold text-slate-900">
                                    {{ $contract->contract_code }}
                                </h2>

                                <span class="inline-flex items-center gap-1.5
                                             rounded-full px-2.5 py-1 text-xs
                                             font-semibold ring-1 ring-inset
                                             {{ $statusConfig['class'] }}">

                                    <span class="h-1.5 w-1.5 rounded-full
                                                 {{ $statusConfig['dot'] }}">
                                    </span>

                                    {{ $statusConfig['text'] }}
                                </span>

                            </div>

                            <div class="mt-1 flex flex-wrap items-center gap-1
                                        text-sm text-slate-500">

                                <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 12 3l9.75 9M4.5 10v10h5.25v-6h4.5v6h5.25V10"/></svg>

                                <span>Phòng</span>

                                <span class="font-semibold text-slate-700">
                                    {{ $contract->room->room_code ?? '---' }}
                                </span>

                                <span class="mx-1 text-slate-300">•</span>

                                <span>
                                    {{ $contract->start_date?->format('d/m/Y') ?? '---' }}
                                    →
                                    {{ $contract->end_date?->format('d/m/Y') ?? '---' }}
                                </span>

                            </div>

                        </div>
                    </div>

                    {{-- DETAIL BUTTON --}}
                    <a href="{{ route('client.contracts.show', $contract) }}"
                       class="inline-flex shrink-0 items-center justify-center
                              gap-2 rounded-xl bg-indigo-600 px-4 py-2.5
                              text-sm font-semibold text-white shadow-sm
                              transition hover:bg-indigo-700 hover:shadow">

                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="2.75"/></svg>

                        Xem chi tiết

                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5 5 5-5 5"/></svg>
                    </a>

                </div>


                {{-- INFORMATION --}}
                <div class="border-t border-slate-100 bg-slate-50/70 px-6 py-5">

                    <div class="contract-info-grid">

                        {{-- RENT --}}
                        <div class="flex items-start gap-3">

                            <div class="flex h-9 w-9 shrink-0 items-center
                                        justify-center rounded-lg
                                        bg-emerald-100 text-emerald-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="2.75" y="6.25" width="18.5" height="11.5" rx="2"/><circle cx="12" cy="12" r="2.5"/><path stroke-linecap="round" d="M6.5 9.5H5.25M18.75 14.5H17.5"/></svg>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold uppercase
                                          tracking-wide text-slate-400">
                                    Giá thuê
                                </p>

                                <p class="mt-1 text-sm font-bold text-slate-900">
                                    {{ number_format($contract->monthly_rent) }} VNĐ
                                </p>

                                <p class="text-xs text-slate-400">
                                    / tháng
                                </p>
                            </div>

                        </div>


                        {{-- DEPOSIT --}}
                        <div class="flex items-start gap-3">

                            <div class="flex h-9 w-9 shrink-0 items-center
                                        justify-center rounded-lg
                                        bg-amber-100 text-amber-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h13.75A2.75 2.75 0 0 1 21 9.5v8.25A2.25 2.25 0 0 1 18.75 20H5.25A2.25 2.25 0 0 1 3 17.75V6.25A2.25 2.25 0 0 1 5.25 4h11"/><path d="M16.5 11H21v4h-4.5a2 2 0 1 1 0-4Z"/></svg>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold uppercase
                                          tracking-wide text-slate-400">
                                    Tiền cọc
                                </p>

                                <p class="mt-1 text-sm font-bold text-slate-900">
                                    {{ number_format($contract->deposit_amount ?? 0) }} VNĐ
                                </p>
                            </div>

                        </div>


                        {{-- START DATE --}}
                        <div class="flex items-start gap-3">

                            <div class="flex h-9 w-9 shrink-0 items-center
                                        justify-center rounded-lg
                                        bg-blue-100 text-blue-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18"/><path stroke-linecap="round" stroke-linejoin="round" d="m8.5 15 2 2 5-5"/></svg>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold uppercase
                                          tracking-wide text-slate-400">
                                    Bắt đầu
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-800">
                                    {{ $contract->start_date?->format('d/m/Y') ?? '---' }}
                                </p>
                            </div>

                        </div>


                        {{-- END DATE --}}
                        <div class="flex items-start gap-3">

                            <div class="flex h-9 w-9 shrink-0 items-center
                                        justify-center rounded-lg
                                        bg-rose-100 text-rose-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18"/><path stroke-linecap="round" d="m9.5 13.5 5 5m0-5-5 5"/></svg>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold uppercase
                                          tracking-wide text-slate-400">
                                    Kết thúc
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-800">
                                    {{ $contract->end_date?->format('d/m/Y') ?? '---' }}
                                </p>
                            </div>

                        </div>


                        {{-- TENANT --}}
                        <div class="flex items-start gap-3">

                            <div class="flex h-9 w-9 shrink-0 items-center justify-center
                                        rounded-lg bg-violet-100 text-violet-600">

                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 6a3.75 3.75 0 11-7.5 0
                                            3.75 3.75 0 017.5 0zM4.501
                                            20.118a7.5 7.5 0 0114.998 0
                                            A17.933 17.933 0 0112
                                            21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                </svg>

                            </div>

                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase
                                        tracking-wide text-slate-400">
                                    Người đứng tên
                                </p>

                                <p class="mt-1 truncate text-sm font-semibold text-slate-800">
                                    {{ $contract->tenant->full_name ?? '---' }}
                                </p>
                            </div>

                        </div>

                    </div>
                </div>


                {{-- PENDING SIGNATURE --}}
                @if($contract->isPendingSignature())

                    <div class="flex flex-col gap-3 border-t border-amber-200
                                bg-amber-50 px-6 py-4
                                sm:flex-row sm:items-center
                                sm:justify-between">

                        <div class="flex items-start gap-3">

                            <div class="mt-0.5 flex h-9 w-9 shrink-0
                                        items-center justify-center
                                        rounded-full bg-amber-100
                                        text-amber-600">

                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7.5v5M12 16.5h.01"/></svg>

                            </div>

                            <div>
                                <p class="text-sm font-semibold text-amber-900">
                                    Hợp đồng đang chờ chữ ký của bạn
                                </p>

                                <p class="mt-0.5 text-xs text-amber-700">
                                    Vui lòng kiểm tra nội dung hợp đồng trước khi xác nhận ký.
                                </p>
                            </div>

                        </div>

                        <a href="{{ route('client.contracts.show', $contract) }}"
                           class="inline-flex shrink-0 items-center
                                  justify-center gap-2 rounded-xl
                                  bg-amber-500 px-4 py-2.5
                                  text-sm font-semibold text-white
                                  shadow-sm transition
                                  hover:bg-amber-600">

                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m15.5 5.5 3 3M4 20l4.25-1 10.6-10.6a2.12 2.12 0 0 0-3-3L5.25 16 4 20Z"/></svg>

                            Xem và ký hợp đồng
                        </a>

                    </div>

                @endif

            </article>

        @endforeach
    </div>
@endif
</div>
@endsection
