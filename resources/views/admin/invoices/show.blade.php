@extends('layouts.admin.index')

@section('title', 'Chi tiết hóa đơn | Quản lý phòng trọ')
@section('page_title', 'Chi tiết hóa đơn')

@php
    $statusMap = [
        'unpaid' => ['text' => 'Chưa thanh toán', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
        'partial' => ['text' => 'Thanh toán một phần', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200', 'dot' => 'bg-sky-500'],
        'paid' => ['text' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
    ];
    $statusData = $statusMap[$invoice->status] ?? ['text' => $invoice->status, 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'];
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">HDON{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Chi tiết hóa đơn</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.invoices.print', $invoice) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-printer text-lg"></i>
                    In hóa đơn
                </a>
                <a href="{{ route('admin.invoices.edit', $invoice) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-edit text-lg"></i>
                    Cập nhật
                </a>
                <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-arrow-back text-lg"></i>
                    Quay lại
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="space-y-6">
                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h3 class="font-semibold text-slate-950">Các khoản thu</h3>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusData['class'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $statusData['dot'] }}"></span>
                            {{ $statusData['text'] }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Khoản thu</th>
                                    <th class="px-5 py-3 text-center">Chỉ số cũ</th>
                                    <th class="px-5 py-3 text-center">Chỉ số mới</th>
                                    <th class="px-5 py-3 text-center">Số lượng</th>
                                    <th class="px-5 py-3 text-right">Đơn giá</th>
                                    <th class="px-5 py-3 text-right">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($invoice->details as $detail)
                                    <tr>
                                        <td class="px-5 py-4">
                                            <p class="font-semibold text-slate-950">{{ $detail->name }}</p>
                                            @if ($detail->note)
                                                <p class="mt-1 text-xs text-slate-500">{{ $detail->note }}</p>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-center text-slate-600">{{ $detail->old_index ?? '-' }}</td>
                                        <td class="px-5 py-4 text-center text-slate-600">{{ $detail->new_index ?? '-' }}</td>
                                        <td class="px-5 py-4 text-center text-slate-600">{{ number_format($detail->quantity, 0, ',', '.') }} {{ $detail->unit }}</td>
                                        <td class="px-5 py-4 text-right text-slate-600">{{ number_format($detail->unit_price, 0, ',', '.') }}đ</td>
                                        <td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($detail->amount, 0, ',', '.') }}đ</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50">
                                <tr>
                                    <th colspan="5" class="px-5 py-4 text-right font-semibold text-slate-700">Tổng cộng</th>
                                    <th class="px-5 py-4 text-right text-lg font-bold text-emerald-700">{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="font-semibold text-slate-950">Lịch sử thanh toán</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Ngày thanh toán</th>
                                    <th class="px-5 py-3">Phương thức</th>
                                    <th class="px-5 py-3 text-right">Số tiền</th>
                                    <th class="px-5 py-3">Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($invoice->payments as $payment)
                                    <tr>
                                        <td class="px-5 py-4 text-slate-600">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $payment->payment_method ?? 'cash' }}</td>
                                        <td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($payment->amount_paid, 0, ',', '.') }}đ</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $payment->note ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-10 text-center text-slate-500">Chưa có thanh toán.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <aside class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Thông tin hóa đơn</h3>

                <div class="mt-5 space-y-4 text-sm">
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Kỳ hóa đơn</span><span class="font-semibold text-slate-950">Tháng {{ $invoice->month }}/{{ $invoice->year }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Ngày lập</span><span class="font-semibold text-slate-950">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Hạn thanh toán</span><span class="font-semibold text-slate-950">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</span></div>
                    <div class="border-t border-slate-200 pt-4"></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Phòng</span><span class="font-semibold text-slate-950">{{ $invoice->room->room_code ?? $invoice->contract->room->room_code ?? 'N/A' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Khách thuê</span><span class="font-semibold text-slate-950">{{ $invoice->contract->tenant->full_name ?? 'N/A' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Hợp đồng</span><span class="font-semibold text-slate-950">{{ $invoice->contract->contract_code ?? 'N/A' }}</span></div>
                    <div class="border-t border-slate-200 pt-4"></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Tổng tiền</span><span class="font-semibold text-slate-950">{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Đã thu</span><span class="font-semibold text-sky-700">{{ number_format($paidAmount, 0, ',', '.') }}đ</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Còn lại</span><span class="font-semibold text-rose-700">{{ number_format($remainingAmount, 0, ',', '.') }}đ</span></div>
                </div>

                @if ($remainingAmount > 0)
                    <form method="POST" action="{{ route('admin.invoices.payments.store', $invoice) }}" class="mt-6 space-y-4 border-t border-slate-200 pt-5">
                        @csrf
                        <h4 class="font-semibold text-slate-950">Ghi nhận thanh toán</h4>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Số tiền</label>
                            <input type="number" name="amount_paid" min="1" max="{{ (int) $remainingAmount }}" value="{{ old('amount_paid', (int) $remainingAmount) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày thanh toán</label>
                            <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Phương thức</label>
                            <select name="payment_method" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                                <option value="cash">Tiền mặt</option>
                                <option value="bank_transfer">Chuyển khoản</option>
                                <option value="qr">QR</option>
                            </select>
                        </div>
                        <input type="text" name="transaction_code" value="{{ old('transaction_code') }}" placeholder="Mã giao dịch (nếu có)" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <textarea name="note" rows="2" placeholder="Ghi chú" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('note') }}</textarea>
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                            <i class="bx bx-check-circle text-lg"></i>
                            Xác nhận thanh toán
                        </button>
                    </form>
                @endif
            </aside>
        </div>
    </div>
@endsection
