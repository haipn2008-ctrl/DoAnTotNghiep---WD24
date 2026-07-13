@extends('layouts.admin.index')

@section('title', 'Danh sách hóa đơn | Quản lý phòng trọ')
@section('page_title', 'Danh sách hóa đơn')

@php
    $statusMap = [
        'unpaid' => ['text' => 'Chưa thanh toán', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
        'partial' => ['text' => 'Thanh toán một phần', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200', 'dot' => 'bg-sky-500'],
        'paid' => ['text' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
    ];
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Quản lý hóa đơn</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Danh sách hóa đơn</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.invoices.payments') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-credit-card text-lg"></i>
                    Thanh toán
                </a>
                <a href="{{ route('admin.invoices.export', request()->query()) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-export text-lg"></i>
                    Xuất CSV
                </a>
                <a href="{{ route('admin.invoices.generate') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-file-plus text-lg"></i>
                    Sinh hóa đơn
                </a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tổng hóa đơn</p>
                <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($summary['count']) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tổng phải thu</p>
                <p class="mt-3 text-3xl font-bold text-emerald-700">{{ number_format($summary['total_amount'], 0, ',', '.') }}đ</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Chưa thanh toán</p>
                <p class="mt-3 text-3xl font-bold text-amber-700">{{ number_format($summary['unpaid']) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Đã thanh toán</p>
                <p class="mt-3 text-3xl font-bold text-sky-700">{{ number_format($summary['paid']) }}</p>
            </div>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form action="{{ route('admin.invoices.index') }}" method="GET" class="grid gap-3 lg:grid-cols-[1fr_140px_140px_190px_auto] lg:items-end">
                <div>
                    <label for="keyword" class="mb-1.5 block text-sm font-semibold text-slate-700">Tìm kiếm</label>
                    <input id="keyword" type="text" name="keyword" value="{{ $keyword }}" placeholder="Mã HĐ, phòng, khách thuê..." class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                </div>
                <div>
                    <label for="month" class="mb-1.5 block text-sm font-semibold text-slate-700">Tháng</label>
                    <select id="month" name="month" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Tất cả</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($month == $m)>Tháng {{ $m }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="year" class="mb-1.5 block text-sm font-semibold text-slate-700">Năm</label>
                    <select id="year" name="year" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Tất cả</option>
                        @for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" @selected($year == $y)>Năm {{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Trạng thái</label>
                    <select id="status" name="status" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Tất cả</option>
                        @foreach ($statusMap as $value => $meta)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $meta['text'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.invoices.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Xóa lọc</a>
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">
                        <i class="bx bx-filter-alt text-lg"></i>
                        Lọc
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Hóa đơn đã phát hành</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Hóa đơn</th>
                            <th class="px-5 py-3">Phòng</th>
                            <th class="px-5 py-3">Khách thuê</th>
                            <th class="px-5 py-3 text-right">Tổng tiền</th>
                            <th class="px-5 py-3 text-right">Đã thu</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="px-5 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($invoices as $invoice)
                            @php
                                $paidAmount = $invoice->paid_amount ?? 0;
                                $statusData = $statusMap[$invoice->status] ?? ['text' => $invoice->status, 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'];
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950">HDON{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Tháng {{ $invoice->month }}/{{ $invoice->year }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-700">{{ $invoice->room->room_code ?? $invoice->contract->room->room_code ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-medium text-slate-900">{{ $invoice->contract->tenant->full_name ?? 'N/A' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $invoice->contract->contract_code ?? '' }}</p>
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-right text-slate-600">{{ number_format($paidAmount, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusData['class'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $statusData['dot'] }}"></span>
                                        {{ $statusData['text'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.invoices.print', $invoice) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100" title="In hóa đơn">
                                            <i class="bx bx-printer text-lg"></i>
                                        </a>
                                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100" title="Xem chi tiết">
                                            <i class="bx bx-show text-lg"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">Chưa có hóa đơn phù hợp với bộ lọc.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($invoices->hasPages())
            <div class="flex justify-end">{{ $invoices->links() }}</div>
        @endif
    </div>
@endsection
