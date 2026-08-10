@extends('layouts.client.index')

@section('title', 'Chi tiết hóa đơn | Cổng khách thuê')
@section('page_title', 'Chi tiết hóa đơn')

@php
    $isDeposit = $invoice->invoice_type === 'deposit';

    $statuses = [
        'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'partial' => ['label' => 'Thanh toán một phần', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
    ];
    $status = $statuses[$invoice->status] ?? ['label' => $invoice->status, 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'];
    $methodLabels = ['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản', 'qr' => 'QR'];
    $paymentStatuses = ['pending' => 'Chờ xác nhận', 'success' => 'Thành công', 'failed' => 'Từ chối'];
@endphp

@section('content')
    <div class="space-y-5">
        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><p class="font-semibold">Chưa thể gửi xác nhận</p><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <a href="{{ route('client.invoices.index') }}" class="text-sm font-semibold text-indigo-700">← Hóa đơn của tôi</a>
                <h2 class="mt-2 text-2xl font-bold text-slate-950">
                    {{ $isDeposit ? 'Hóa đơn tiền cọc' : 'Hóa đơn tháng ' . $invoice->month . '/' . $invoice->year }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">{{ $invoice->invoice_code }} · Phòng {{ $invoice->room->room_code ?? '-' }}</p>
            </div>
            <a href="{{ route('client.invoices.print', $invoice) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm"><i class="bx bx-printer text-lg"></i>In hóa đơn</a>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Tổng hóa đơn</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Đã thanh toán</p><p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($paidAmount, 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Còn phải trả</p><p class="mt-2 text-2xl font-bold {{ $remainingAmount > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format($remainingAmount, 0, ',', '.') }}đ</p></div>
        </div>

        @if ($isDeposit)
            <section class="overflow-hidden rounded-lg border border-amber-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-100 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-slate-950">Tiền cọc hợp đồng</h3>
                        <p class="mt-1 text-sm text-slate-500">Khoản tiền đặt cọc cho hợp đồng</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">
                        {{ $status['label'] }}
                    </span>
                </div>
                <div class="p-5">
                    <div class="flex items-center justify-between rounded-lg bg-amber-50 px-5 py-4">
                        <span class="font-semibold text-slate-700">Tiền cọc</span>
                        <span class="text-xl font-bold text-amber-600">
                            {{ number_format($invoice->total_amount, 0, ',', '.') }}đ
                        </span>
                    </div>
                </div>
            </section>
        @else
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-slate-950">Các khoản cần thanh toán</h3>
                        <p class="mt-1 text-sm text-slate-500">Hạn thanh toán {{ $invoice->due_date?->format('d/m/Y') }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">
                        {{ $status['label'] }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Khoản tiền</th>
                                <th class="px-5 py-3 text-center">Đã sử dụng</th>
                                <th class="px-5 py-3 text-right">Đơn giá</th>
                                <th class="px-5 py-3 text-right">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($invoice->details as $detail)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-950">{{ $detail->name }}</p>
                                        @if($detail->note)
                                            <p class="mt-1 text-xs text-slate-500">{{ $detail->note }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-center text-slate-700">
                                        {{ number_format($detail->quantity, 0, ',', '.') }} {{ $detail->unit }}
                                    </td>
                                    <td class="px-5 py-4 text-right text-slate-600">
                                        {{ number_format($detail->unit_price, 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-5 py-4 text-right font-semibold text-slate-950">
                                        {{ number_format($detail->amount, 0, ',', '.') }}đ
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-50">
                            <tr>
                                <th colspan="3" class="px-5 py-4 text-right">Tổng cộng</th>
                                <th class="px-5 py-4 text-right text-lg text-emerald-700">
                                    {{ number_format($invoice->total_amount, 0, ',', '.') }}đ
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        @endif

        @if ($remainingAmount > 0)
            <section class="grid gap-5 rounded-lg border border-indigo-200 bg-white p-5 shadow-sm lg:grid-cols-[320px_1fr]">
                <div>
                    <p class="text-sm font-semibold text-indigo-700">Xác nhận đã chuyển khoản</p>
                    <h3 class="mt-1 text-xl font-bold text-slate-950">Gửi biên lai cho ban quản lý</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Sau khi chuyển khoản theo thông tin do ban quản lý cung cấp, hãy gửi mã giao dịch và ảnh biên lai. Tiền chỉ được ghi nhận sau khi được duyệt.</p>
                    @if ($pendingAmount > 0)
                        <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Đang chờ xác nhận: <strong>{{ number_format($pendingAmount, 0, ',', '.') }}đ</strong></div>
                    @endif
                </div>
                @if ($availableAmount > 0)
                    <form method="POST" action="{{ route('client.invoices.payments.store', $invoice) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Số tiền đã chuyển</label><input type="number" name="amount_paid" min="1" max="{{ (int) $availableAmount }}" value="{{ old('amount_paid', (int) $availableAmount) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày chuyển</label><input type="date" name="payment_date" max="{{ now()->toDateString() }}" value="{{ old('payment_date', now()->toDateString()) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Hình thức</label><select name="payment_method" class="h-11 w-full rounded-lg border border-slate-200 px-3"><option value="bank_transfer">Chuyển khoản</option><option value="qr">Quét mã QR</option></select></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Mã giao dịch</label><input name="transaction_code" value="{{ old('transaction_code') }}" required maxlength="255" placeholder="VD: FT260809123456" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ảnh biên lai</label><input type="file" name="proof_image" accept="image/*" capture="environment" required class="block w-full rounded-lg border border-slate-200 p-2 text-sm"></div>
                        <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ghi chú (nếu có)</label><textarea name="note" rows="2" maxlength="1000" class="w-full rounded-lg border border-slate-200 px-3 py-2">{{ old('note') }}</textarea></div>
                        <div class="sm:col-span-2"><button class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">Gửi xác nhận thanh toán</button></div>
                    </form>
                @else
                    <div class="flex items-center justify-center rounded-lg bg-amber-50 p-5 text-center text-sm font-medium text-amber-800">Toàn bộ số tiền còn lại đang chờ ban quản lý xác nhận.</div>
                @endif
            </section>
        @endif

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Lịch sử thanh toán</h3></div>
            <div class="divide-y divide-slate-100">
                @forelse ($invoice->payments as $payment)
                    <div class="flex flex-col justify-between gap-3 px-5 py-4 sm:flex-row sm:items-center"><div><p class="font-semibold text-slate-950">{{ number_format($payment->amount_paid, 0, ',', '.') }}đ · {{ $methodLabels[$payment->payment_method] ?? $payment->payment_method }}</p><p class="mt-1 text-xs text-slate-500">{{ $payment->payment_date?->format('d/m/Y') }}{{ $payment->transaction_code ? ' · Mã '.$payment->transaction_code : '' }}</p>@if($payment->review_note)<p class="mt-2 text-xs text-rose-600">Phản hồi: {{ $payment->review_note }}</p>@endif</div><div class="flex items-center gap-3">@if($payment->proof_image)<a href="{{ asset('storage/'.$payment->proof_image) }}" target="_blank" class="text-xs font-semibold text-indigo-700">Xem biên lai</a>@endif<span class="text-sm font-semibold text-slate-600">{{ $paymentStatuses[$payment->status] ?? $payment->status }}</span></div></div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-500">Chưa có giao dịch thanh toán.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
