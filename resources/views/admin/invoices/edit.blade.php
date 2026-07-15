@extends('layouts.admin.index')

@section('title', 'Cập nhật hóa đơn | Quản lý phòng trọ')
@section('page_title', 'Cập nhật hóa đơn')

@section('content')
    @php
        $statusMap = [
            'unpaid' => ['text' => 'Chưa thanh toán', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
            'partial' => ['text' => 'Thanh toán một phần', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200'],
            'paid' => ['text' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        ];
        $statusData = $statusMap[$invoice->status] ?? ['text' => $invoice->status, 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'];
    @endphp

    <div class="mx-auto max-w-2xl space-y-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">{{ $invoice->invoice_code ?? 'HDON' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Trạng thái hóa đơn</h2>
            </div>
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i class="bx bx-arrow-back text-lg"></i> Quay lại
            </a>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">Trạng thái hiện tại</p>
            <div class="mt-3">
                <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold ring-1 {{ $statusData['class'] }}">
                    {{ $statusData['text'] }}
                </span>
            </div>

            <div class="mt-5 rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-700">
                Trạng thái hóa đơn được cập nhật tự động dựa trên các khoản thanh toán thành công.
                Vui lòng ghi nhận thanh toán tại trang chi tiết hóa đơn.
            </div>

            <div class="mt-6 flex justify-end">
                <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    <i class="bx bx-show text-lg"></i> Đến chi tiết hóa đơn
                </a>
            </div>
        </div>
    </div>
@endsection
