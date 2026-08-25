@extends('layouts.client.index')

@section('title', 'Hóa đơn của tôi | Cổng khách thuê')
@section('page_title', 'Hóa đơn của tôi')

@php
    $statuses = [
        'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'partial' => ['label' => 'Thanh toán một phần', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-slate-100 text-slate-700 ring-slate-300'],
    ];
@endphp

@section('content')
    <div class="space-y-5">
        <div>
            <p class="text-sm font-medium text-slate-500">Theo dõi các khoản thuê phòng</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Hóa đơn của tôi</h2>
            <p class="mt-2 text-sm text-slate-500">Xem chi tiết từng khoản tiền, hạn thanh toán và lịch sử đã trả.</p>
        </div>

        <form method="GET" action="{{ route('client.invoices.index') }}" class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_1fr_auto]">
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase text-slate-500">Trạng thái</label>
                <select name="status" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($statuses as $value => $meta)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase text-slate-500">Năm</label>
                <select name="year" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm">
                    <option value="">Tất cả các năm</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="h-11 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">Lọc</button>
                @if (request()->hasAny(['status', 'year']))
                    <a href="{{ route('client.invoices.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600">Xóa lọc</a>
                @endif
            </div>
        </form>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
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
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($invoices as $invoice)
                            @php
                                $status = $statuses[$invoice->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'];
                                $remaining = max(0, $invoice->payable_amount - (float) ($invoice->paid_amount ?? 0));
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950">
                                        {{ $invoice->isDeposit() ? 'Tiền cọc hợp đồng' : ($invoice->isFirstMonthRent()
                                            ? 'Tiền phòng tháng đầu'
                                            : 'Tháng ' . $invoice->month . '/' . $invoice->year) }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $invoice->invoice_code }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $invoice->room->room_code ?? '-' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $invoice->due_date?->format('d/m/Y') }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($invoice->payable_amount, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-right font-bold {{ $remaining > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format($remaining, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                                <td class="px-5 py-4 text-right"><a href="{{ route('client.invoices.show', $invoice) }}" class="font-semibold text-indigo-700">Xem chi tiết</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">Chưa có hóa đơn phù hợp.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden">
                @forelse ($invoices as $invoice)
                    @php
                        $status = $statuses[$invoice->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'];
                        $remaining = max(0, $invoice->payable_amount - (float) ($invoice->paid_amount ?? 0));
                    @endphp
                    <a href="{{ route('client.invoices.show', $invoice) }}" class="block space-y-3 p-4 hover:bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-slate-950">
                                    {{ $invoice->isDeposit() ? 'Tiền cọc hợp đồng' : ($invoice->isFirstMonthRent()
                                        ? 'Tiền phòng tháng đầu'
                                        : 'Tháng ' . $invoice->month . '/' . $invoice->year) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $invoice->invoice_code }} · Phòng {{ $invoice->room->room_code ?? '-' }} · Hạn {{ $invoice->due_date?->format('d/m/Y') }}
                                </p>
                            </div>
                            <span class="rounded-full px-2 py-1 text-[11px] font-semibold ring-1 {{ $status['class'] }}">{{ $status['label'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm"><span class="text-slate-500">Còn phải trả</span><strong class="text-rose-700">{{ number_format($remaining, 0, ',', '.') }}đ</strong></div>
                    </a>
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
