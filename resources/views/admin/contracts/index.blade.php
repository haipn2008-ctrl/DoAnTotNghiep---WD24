@extends('layouts.admin.index')

@section('title', 'Danh sách hợp đồng | Quản lý phòng trọ')
@section('page_title', 'Danh sách hợp đồng')

@php
    $statusOptions = [
        'active' => ['label' => 'Đang thuê', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'terminated' => ['label' => 'Đã kết thúc', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        'expired' => ['label' => 'Hết hạn', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
        'pending' => ['label' => 'Chờ xử lý', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'],
    ];
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Quản lý hợp đồng</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Danh sách hợp đồng</h2>
            </div>

            <a href="{{ route('admin.contracts.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                <i class="bx bx-plus text-lg"></i>
                Tạo hợp đồng mới
            </a>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.contracts.index') }}" class="grid gap-3 md:grid-cols-[1fr_220px_auto] md:items-end">
                <div>
                    <label for="keyword" class="mb-1.5 block text-sm font-semibold text-slate-700">Tìm kiếm</label>
                    <div class="relative">
                        <i class="bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                        <input id="keyword" type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Mã HĐ, người thuê, số phòng..." class="h-11 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    </div>
                </div>

                <div>
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Trạng thái</label>
                    <select id="status" name="status" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statusOptions as $value => $meta)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">
                        <i class="bx bx-filter-alt text-lg"></i>
                        Lọc
                    </button>
                    <a href="{{ route('admin.contracts.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Làm mới
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-slate-950">Tất cả hợp đồng</h3>
                    <p class="text-sm text-slate-500">Tìm thấy {{ $contracts->count() }} hợp đồng</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Hợp đồng</th>
                            <th class="px-5 py-3">Người thuê</th>
                            <th class="px-5 py-3">Phòng</th>
                            <th class="px-5 py-3">Thời hạn</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="px-5 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($contracts as $contract)
                            @php
                                $status = $statusOptions[$contract->status] ?? ['label' => $contract->status, 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'];
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950">{{ $contract->contract_code ?: 'HD' . str_pad($contract->id, 3, '0', STR_PAD_LEFT) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Tiền cọc {{ number_format($contract->deposit_amount ?? 0, 0, ',', '.') }}đ</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-medium text-slate-900">{{ $contract->tenant->full_name ?? 'N/A' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $contract->tenant->phone ?? 'Chưa có SĐT' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">
                                        Phòng {{ $contract->room->room_code ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <p>{{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}</p>
                                    <p class="text-xs text-slate-500">đến {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>
                                        {{ $status['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.contracts.show', $contract) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100" title="Xem chi tiết">
                                            <i class="bx bx-show text-lg"></i>
                                        </a>
                                        <a href="{{ route('admin.contracts.print', $contract->id) }}" target="_blank" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="In hợp đồng">
                                            <i class="bx bx-printer text-lg"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <i class="bx bx-file text-2xl"></i>
                                    </div>
                                    <p class="mt-3 font-semibold text-slate-900">Chưa có hợp đồng nào</p>
                                    <p class="mt-1 text-sm text-slate-500">Tạo hợp đồng mới sau khi đã có phòng và khách thuê.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
