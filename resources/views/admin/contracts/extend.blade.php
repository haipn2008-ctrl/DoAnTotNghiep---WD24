@extends('layouts.admin.index')

@section('title', 'Gia hạn hợp đồng | Quản lý phòng trọ')
@section('page_title', 'Gia hạn hợp đồng')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Quản lý hợp đồng</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Danh sách gia hạn hợp đồng</h2>
                <p class="mt-1 text-sm text-slate-500">Hiển thị toàn bộ hợp đồng đang thuê hoặc đã quá hạn nhưng khách chưa trả phòng.</p>
            </div>

            <span class="inline-flex w-fit rounded-full bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200">
                {{ $contracts->count() }} hợp đồng
            </span>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.contracts.extend.list') }}" class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                <div>
                    <label for="keyword" class="mb-1.5 block text-sm font-semibold text-slate-700">Tìm kiếm</label>
                    <div class="relative">
                        <i class="bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                        <input id="keyword" type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Mã HĐ, người thuê, phòng..." class="h-11 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button class="inline-flex h-11 items-center gap-2 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">
                        <i class="bx bx-search text-lg"></i>
                        Tìm kiếm
                    </button>
                    <a href="{{ route('admin.contracts.extend.list') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Làm mới</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Hợp đồng</th>
                            <th class="px-5 py-3">Người thuê</th>
                            <th class="px-5 py-3">Phòng</th>
                            <th class="px-5 py-3">Thời hạn</th>
                            <th class="px-5 py-3">Còn lại</th>
                            <th class="px-5 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($contracts as $contract)
                            @php
                                $today = \Carbon\Carbon::today();
                                $endDate = \Carbon\Carbon::parse($contract->end_date);
                                $days = $today->diffInDays($endDate, false);
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4 font-semibold text-slate-950">{{ $contract->contract_code }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ $contract->tenant->full_name }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">Phòng {{ $contract->room->room_code }}</span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}
                                    <span class="text-slate-400">-</span>
                                    {{ $endDate->format('d/m/Y') }}
                                </td>
                                <td class="px-5 py-4">
                                    @if ($days > 0)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Còn {{ $days }} ngày</span>
                                    @elseif ($days == 0)
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Hôm nay</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">Quá hạn {{ abs($days) }} ngày</span>
                                    @endif
                                    @if($contract->scheduled_move_out_at)
                                        <span class="mt-1.5 block w-fit rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">Đã có lịch trả phòng {{ $contract->scheduled_move_out_at->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end">
                                        @if($contract->scheduled_move_out_at)
                                            <a data-keep-action-label href="{{ route('admin.contracts.check-out.form', $contract) }}" class="inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-rose-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 hover:shadow-md" title="Phải xử lý lịch trả phòng trước khi gia hạn">
                                                <i class="bx bx-log-out-circle text-lg"></i>
                                                Xử lý trả phòng
                                            </a>
                                        @else
                                            <a data-keep-action-label href="{{ route('admin.contracts.extend.form', $contract->id) }}" class="inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 hover:shadow-md">
                                                <i class="bx bx-calendar-plus text-lg"></i>
                                                Gia hạn
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-600"><i class="bx bx-check"></i></span>
                                    <p class="mt-3 font-semibold text-slate-800">Chưa có hợp đồng có thể gia hạn</p>
                                    <p class="mt-1 text-sm text-slate-500">Hiện không có hợp đồng đang thuê hoặc đã quá hạn nhưng chưa trả phòng.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
