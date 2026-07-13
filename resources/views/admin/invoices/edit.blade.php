@extends('layouts.admin.index')

@section('title', 'Cập nhật hóa đơn | Quản lý phòng trọ')
@section('page_title', 'Cập nhật hóa đơn')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">{{ $invoice->invoice_code ?? 'HDON' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Cập nhật trạng thái hóa đơn</h2>
            </div>
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i class="bx bx-arrow-back text-lg"></i> Quay lại
            </a>
        </div>

        <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <label for="status" class="mb-2 block text-sm font-semibold text-slate-700">Trạng thái</label>
            <select id="status" name="status" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                <option value="unpaid" @selected(old('status', $invoice->status) === 'unpaid')>Chưa thanh toán</option>
                <option value="partial" @selected(old('status', $invoice->status) === 'partial')>Thanh toán một phần</option>
                <option value="paid" @selected(old('status', $invoice->status) === 'paid')>Đã thanh toán</option>
            </select>
            @error('status')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror

            <div class="mt-6 flex justify-end">
                <button class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    <i class="bx bx-save text-lg"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
@endsection
