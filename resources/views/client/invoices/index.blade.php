@extends('layouts.client.index')

@section('title', 'Hóa đơn của tôi | Cổng khách thuê')
@section('page_title', 'Hóa đơn của tôi')

@php
    $statuses = [
        'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'partial' => ['label' => 'Thanh toán một phần', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'written_off' => ['label' => 'Đã xóa nợ theo quyết toán', 'class' => 'bg-violet-50 text-violet-700 ring-violet-200'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-slate-100 text-slate-700 ring-slate-300'],
    ];
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold text-indigo-600">Quản lý thanh toán</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Hóa đơn của tôi</h2>
                <p class="mt-2 text-sm text-slate-500">Theo dõi các khoản tiền, hạn thanh toán và lịch sử giao dịch của bạn.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-600 shadow-sm"><i class="bx bx-receipt text-lg text-indigo-600"></i>{{ $invoices->total() }} hóa đơn</span>
        </div>

        <form method="GET" action="{{ route('client.invoices.index') }}" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:grid-cols-[minmax(200px,1fr)_minmax(180px,0.65fr)_auto] lg:items-end">
            <div>
                <label for="invoice-status" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Trạng thái thanh toán</label>
                <select id="invoice-status" name="status" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($statuses as $value => $meta)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="invoice-year" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Năm phát hành</label>
                <select id="invoice-year" name="year" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Tất cả các năm</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 hover:shadow-md"><i class="bx bx-filter-alt text-lg"></i>Lọc hóa đơn</button>
                @if (request()->hasAny(['status', 'year']))
                    <a href="{{ route('client.invoices.index') }}" class="inline-flex h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Xóa lọc</a>
                @endif
            </div>
        </form>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div><h3 class="font-bold text-slate-950">Danh sách hóa đơn</h3><p class="mt-0.5 text-xs text-slate-500">Sắp xếp từ kỳ mới nhất đến cũ nhất</p></div>
                @if(request()->hasAny(['status', 'year']))<span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">Đang áp dụng bộ lọc</span>@endif
            </div>
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Kỳ hóa đơn</th>
                            <th class="px-5 py-3">Phòng</th>
                            <th class="px-5 py-3">Hạn thanh toán</th>
                            <th class="px-5 py-3 text-right">Tổng tiền</th>
                            <th class="px-5 py-3 text-right">Còn phải trả</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="w-20 px-5 py-3 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($invoices as $invoice)
                            @php
                                $status = $statuses[$invoice->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'];
                                $remaining = (float) $invoice->remaining_amount;
                            @endphp
                            <tr class="transition hover:bg-indigo-50/40">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950">
                                        {{ $invoice->isSupplemental() ? 'Hóa đơn bổ sung' : ($invoice->isDeposit() ? 'Tiền cọc hợp đồng' : ($invoice->isFirstMonthRent()
                                            ? 'Tiền phòng tháng đầu'
                                            : 'Tháng ' . $invoice->month . '/' . $invoice->year)) }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $invoice->invoice_code }}</p>
                                </td>
                                <td class="px-5 py-4"><span class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-bold text-slate-700">{{ $invoice->room->room_code ?? '-' }}</span></td>
                                <td class="px-5 py-4"><p class="font-medium text-slate-700">{{ $invoice->due_date?->format('d/m/Y') }}</p>@if($remaining > 0 && $invoice->due_date?->isPast())<p class="mt-1 text-xs font-semibold text-rose-600">Đã quá hạn</p>@endif</td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($invoice->payable_amount, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-right font-bold {{ $remaining > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format($remaining, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4"><span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                                <td class="px-5 py-4 text-center">
                                    <div class="group relative inline-flex">
                                        <a href="{{ route('client.invoices.show', $invoice) }}" aria-label="Xem chi tiết hóa đơn {{ $invoice->invoice_code }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-indigo-600 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-600 hover:text-white focus:outline-none focus:ring-4 focus:ring-indigo-100"><i class="bx bx-show text-xl"></i></a>
                                        <span role="tooltip" class="pointer-events-none absolute bottom-full right-0 z-10 mb-2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus-within:opacity-100">Xem chi tiết<span class="absolute right-4 top-full border-4 border-transparent border-t-slate-900"></span></span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-14 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><i class="bx bx-receipt text-2xl"></i></span><p class="mt-3 font-semibold text-slate-700">Chưa có hóa đơn phù hợp</p><p class="mt-1 text-xs text-slate-500">Hãy thử thay đổi điều kiện lọc.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden">
                @forelse ($invoices as $invoice)
                    @php
                        $status = $statuses[$invoice->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'];
                        $remaining = (float) $invoice->remaining_amount;
                    @endphp
                    <article class="space-y-3 p-4 transition hover:bg-indigo-50/30">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-slate-950">
                                    {{ $invoice->isSupplemental() ? 'Hóa đơn bổ sung' : ($invoice->isDeposit() ? 'Tiền cọc hợp đồng' : ($invoice->isFirstMonthRent()
                                        ? 'Tiền phòng tháng đầu'
                                        : 'Tháng ' . $invoice->month . '/' . $invoice->year)) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $invoice->invoice_code }} · Phòng {{ $invoice->room->room_code ?? '-' }} · Hạn {{ $invoice->due_date?->format('d/m/Y') }}
                                </p>
                            </div>
                            <span class="rounded-full px-2 py-1 text-[11px] font-semibold ring-1 {{ $status['class'] }}">{{ $status['label'] }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-sm"><span class="text-slate-500">Còn phải trả</span><div class="flex items-center gap-3"><strong class="{{ $remaining > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format($remaining, 0, ',', '.') }}đ</strong><a href="{{ route('client.invoices.show', $invoice) }}" aria-label="Xem chi tiết hóa đơn {{ $invoice->invoice_code }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-sm"><i class="bx bx-show text-lg"></i></a></div></div>
                    </article>
                @empty
                    <div class="p-10 text-center text-sm text-slate-500">Chưa có hóa đơn phù hợp.</div>
                @endforelse
            </div>

            @if ($invoices->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">{{ $invoices->links() }}</div>
            @endif
        </section>
    </div>
@endsection
