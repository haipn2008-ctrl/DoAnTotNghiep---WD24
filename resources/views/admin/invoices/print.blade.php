@extends('layouts.admin.index')

@section('title', 'In hóa đơn | Quản lý phòng trọ')

@push('styles')
    <style>@media print { aside, header, footer, .no-print { display: none !important; } main { padding: 0 !important; } .lg\:pl-72 { padding-left: 0 !important; } }</style>
@endpush

@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <div class="no-print flex justify-end gap-2"><button onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"><i class="bx bx-printer text-lg"></i> In hóa đơn</button><a href="{{ route('admin.invoices.show', $invoice) }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Quay lại</a></div>
        <article class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
            <div class="border-b border-slate-200 pb-6 text-center"><h1 class="text-2xl font-bold text-slate-950">HÓA ĐƠN THUÊ PHÒNG</h1><p class="mt-2 text-sm text-slate-500">{{ $invoice->invoice_code ?? 'HDON' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }} · Kỳ {{ $invoice->month }}/{{ $invoice->year }}</p></div>
            <div class="grid gap-4 py-6 sm:grid-cols-2"><p><span class="text-slate-500">Phòng:</span> <strong>{{ $invoice->room->room_code ?? $invoice->contract->room->room_code ?? '-' }}</strong></p><p><span class="text-slate-500">Khách thuê:</span> <strong>{{ $invoice->contract->tenant->full_name ?? '-' }}</strong></p><p><span class="text-slate-500">Ngày lập:</span> <strong>{{ $invoice->invoice_date?->format('d/m/Y') }}</strong></p><p><span class="text-slate-500">Hạn thanh toán:</span> <strong>{{ $invoice->due_date?->format('d/m/Y') }}</strong></p></div>
            <table class="w-full border-collapse text-sm"><thead><tr class="bg-slate-50 text-left"><th class="border border-slate-200 px-4 py-3">Khoản thu</th><th class="border border-slate-200 px-4 py-3 text-right">Thành tiền</th></tr></thead><tbody>@foreach($invoice->details as $detail)<tr><td class="border border-slate-200 px-4 py-3">{{ $detail->name }}</td><td class="border border-slate-200 px-4 py-3 text-right">{{ number_format($detail->amount, 0, ',', '.') }}đ</td></tr>@endforeach</tbody><tfoot><tr><th class="border border-slate-200 px-4 py-4 text-right">Tổng cộng</th><th class="border border-slate-200 px-4 py-4 text-right text-lg text-emerald-700">{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</th></tr></tfoot></table>
        </article>
    </div>
@endsection
