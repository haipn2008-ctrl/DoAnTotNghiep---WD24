@extends('layouts.client.index')

@section('title', 'Hợp đồng của tôi | Cổng khách thuê')
@section('page_title', 'Hợp đồng của tôi')

@php
    $statuses = [
        'draft' => ['Bản nháp', 'bg-slate-100 text-slate-700', 'bg-slate-500'],
        'pending_signature' => ['Chờ ký', 'bg-amber-50 text-amber-700', 'bg-amber-500'],
        'pending_deposit' => ['Chờ tiền cọc', 'bg-orange-50 text-orange-700', 'bg-orange-500'],
        'awaiting_move_in' => ['Chờ nhận phòng', 'bg-sky-50 text-sky-700', 'bg-sky-500'],
        'active' => ['Đang thuê', 'bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'expired' => ['Hết hạn - chờ xử lý', 'bg-rose-50 text-rose-700', 'bg-rose-500'],
        'settling' => ['Đang quyết toán', 'bg-violet-50 text-violet-700', 'bg-violet-500'],
        'completed' => ['Đã hoàn tất', 'bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'cancelled' => ['Đã hủy', 'bg-slate-100 text-slate-600', 'bg-slate-400'],
    ];
@endphp

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 px-6 py-7 text-white shadow-lg shadow-indigo-200/60 sm:px-8">
            <div class="absolute -right-12 -top-16 h-52 w-52 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-20 right-28 h-40 w-40 rounded-full bg-white/5"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 sm:flex"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h7.5L18 7.5v12.75H6.75V3.75Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75V7.5H18M9.5 11h5M9.5 14.25h5M9.5 17.5h3" /></svg></span>
                    <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-100">Quản lý lưu trú</p>
                    <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Hợp đồng của tôi</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-indigo-100">Theo dõi thời hạn, phòng thuê và trạng thái hợp đồng của bạn.</p>
                    </div>
                </div>
                <div class="w-fit rounded-xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-sm">
                    <p class="text-xs text-indigo-100">Hợp đồng hiện tại</p>
                    <p class="mt-1 text-2xl font-bold">{{ $currentContracts->count() }}</p>
                </div>
            </div>
        </section>

        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Hợp đồng hiện tại</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Các hợp đồng đang thực hiện hoặc chờ hoàn tất thủ tục.</p>
                </div>
                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $currentContracts->count() }} hợp đồng</span>
            </div>

            @if($currentContracts->isNotEmpty())
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach($currentContracts as $contract)
                        @php
                            $status = $contract->isExpiringSoon()
                                ? ['Sắp hết hạn', 'bg-amber-50 text-amber-700', 'bg-amber-500']
                                : ($statuses[$contract->status] ?? ['Không xác định', 'bg-slate-100 text-slate-700', 'bg-slate-500']);
                            $endDate = $contract->extend_end_date ?? $contract->end_date;
                        @endphp
                        <a href="{{ route('client.contracts.show', $contract) }}" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h7.5L18 7.5v12.75H6.75V3.75Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75V7.5H18M9.5 11h5M9.5 14.25h5M9.5 17.5h3" />
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-base font-bold text-slate-950">{{ $contract->contract_code }}</p>
                                            <p class="mt-0.5 text-sm text-slate-500">Phòng {{ $contract->room->room_code ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $status[1] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $status[2] }}"></span>{{ $status[0] }}
                                    </span>
                                </div>

                                <div class="mt-5 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4">
                                    <div>
                                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Thời hạn</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-700">{{ $contract->start_date->format('d/m/Y') }}</p>
                                        <p class="text-xs text-slate-500">đến {{ $endDate->format('d/m/Y') }}</p>
                                    </div>
                                    <div class="border-l border-slate-200 pl-4">
                                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Tiền thuê/tháng</p>
                                        <p class="mt-1 text-lg font-bold text-slate-950">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3 text-sm font-semibold text-indigo-600">
                                <span>Xem chi tiết hợp đồng</span>
                                <svg class="h-4 w-4 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5 5 5-5 5" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h5l1.5 2h10v9.5a2 2 0 0 1-2 2H5.75a2 2 0 0 1-2-2V6.75Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 17.5 3-5.5h13.5" />
                        </svg>
                    </span>
                    <p class="mt-3 font-semibold text-slate-800">Không có hợp đồng đang thực hiện</p>
                    <p class="mt-1 text-sm text-slate-500">Các hợp đồng đã kết thúc được lưu tại lịch sử bên dưới.</p>
                </div>
            @endif
        </section>

        @if($historicalContracts->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <h3 class="font-bold text-slate-900">Lịch sử hợp đồng</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Hợp đồng đã hoàn tất hoặc đã hủy.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $historicalContracts->total() }} hợp đồng</span>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach($historicalContracts as $contract)
                        @php
                            $status = $statuses[$contract->status] ?? ['Không xác định', 'bg-slate-100 text-slate-700', 'bg-slate-500'];
                            $endDate = $contract->extend_end_date ?? $contract->end_date;
                        @endphp
                        <a href="{{ route('client.contracts.show', $contract) }}" class="group flex flex-col gap-4 px-5 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h15v12h-15v-12Zm-1-3h17v3h-17v-3Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 11h5" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-bold text-slate-900">{{ $contract->contract_code }}</p>
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $status[1] }}"><span class="h-1.5 w-1.5 rounded-full {{ $status[2] }}"></span>{{ $status[0] }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">Phòng {{ $contract->room->room_code ?? '-' }} · {{ $contract->start_date->format('d/m/Y') }} – {{ $endDate->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            <span class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-sm font-semibold text-indigo-700 transition group-hover:border-indigo-300 group-hover:bg-indigo-100">
                                Xem chi tiết
                                <svg class="h-4 w-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 18 15 12 9 6" />
                                </svg>
                            </span>
                        </a>
                    @endforeach
                </div>

                @if($historicalContracts->hasPages())
                    <div class="border-t border-slate-200 px-5 py-4 sm:px-6">{{ $historicalContracts->links() }}</div>
                @endif
            </section>
        @endif
    </div>
@endsection
