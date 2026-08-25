@extends('layouts.client.index')

@section('title', 'In hóa đơn | Cổng khách thuê')

@push('styles')
    <style>@media print { aside, header, footer, .no-print { display: none !important; } main { padding: 0 !important; } .lg\:pl-72 { padding-left: 0 !important; } article { box-shadow: none !important; border: 0 !important; } }</style>
@endpush

@section('content')
    <div class="mx-auto max-w-3xl space-y-4">
        <div class="no-print flex justify-end gap-2"><button onclick="window.print()" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">In hóa đơn</button><a href="{{ route('client.invoices.show', $invoice) }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold">Quay lại</a></div>
        <article class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
            <div class="border-b border-slate-200 pb-6 text-center"><h1 class="text-2xl font-bold">{{ $invoice->isDeposit() ? 'HÓA ĐƠN TIỀN CỌC' : ($invoice->isFirstMonthRent() ? 'HÓA ĐƠN TIỀN PHÒNG THÁNG ĐẦU' : 'HÓA ĐƠN THUÊ PHÒNG') }}</h1><p class="mt-2 text-sm text-slate-500">{{ $invoice->invoice_code }} · Kỳ {{ $invoice->month }}/{{ $invoice->year }}</p></div>
            <div class="grid gap-3 py-6 sm:grid-cols-2"><p><span class="text-slate-500">Phòng:</span> <strong>{{ $invoice->room->room_code ?? '-' }}</strong></p><p><span class="text-slate-500">Khách thuê:</span> <strong>{{ $invoice->contract->tenant->full_name ?? '-' }}</strong></p><p><span class="text-slate-500">Ngày lập:</span> <strong>{{ $invoice->invoice_date?->format('d/m/Y') }}</strong></p><p><span class="text-slate-500">Hạn thanh toán:</span> <strong>{{ $invoice->due_date?->format('d/m/Y') }}</strong></p></div>
            <table class="w-full border-collapse text-sm"><thead><tr class="bg-slate-50"><th class="border border-slate-200 px-3 py-3 text-left">Khoản tiền</th><th class="border border-slate-200 px-3 py-3 text-center">Đã sử dụng</th><th class="border border-slate-200 px-3 py-3 text-right">Đơn giá</th><th class="border border-slate-200 px-3 py-3 text-right">Thành tiền</th></tr></thead><tbody>@foreach($invoice->details as $detail)<tr><td class="border border-slate-200 px-3 py-3">{{ $detail->name }}</td><td class="border border-slate-200 px-3 py-3 text-center">{{ number_format($detail->quantity, 0, ',', '.') }} {{ $detail->unit }}</td><td class="border border-slate-200 px-3 py-3 text-right">{{ number_format($detail->unit_price, 0, ',', '.') }}đ</td><td class="border border-slate-200 px-3 py-3 text-right font-semibold">{{ number_format($detail->amount, 0, ',', '.') }}đ</td></tr>@endforeach @foreach($invoice->adjustments as $adjustment)<tr><td colspan="3" class="border border-slate-200 px-3 py-3">{{ $adjustment->direction === \App\Models\InvoiceAdjustment::DIRECTION_CREDIT ? 'Điều chỉnh giảm' : 'Điều chỉnh tăng' }}: {{ $adjustment->reason }}</td><td class="border border-slate-200 px-3 py-3 text-right font-semibold">{{ $adjustment->direction === \App\Models\InvoiceAdjustment::DIRECTION_CREDIT ? '-' : '+' }}{{ number_format($adjustment->amount, 0, ',', '.') }}đ</td></tr>@endforeach</tbody><tfoot><tr><th colspan="3" class="border border-slate-200 px-3 py-4 text-right">Tổng sau điều chỉnh</th><th class="border border-slate-200 px-3 py-4 text-right text-lg text-emerald-700">{{ number_format($invoice->payable_amount, 0, ',', '.') }}đ</th></tr></tfoot></table>
            <div class="mt-6 grid gap-2 border-t border-slate-200 pt-5 text-sm sm:grid-cols-2"><p>Đã thanh toán: <strong class="text-emerald-700">{{ number_format($paidAmount, 0, ',', '.') }}đ</strong></p><p class="sm:text-right">Còn phải trả: <strong class="text-rose-700">{{ number_format($remainingAmount, 0, ',', '.') }}đ</strong></p></div>
        </article>
    </div>
@endsection
