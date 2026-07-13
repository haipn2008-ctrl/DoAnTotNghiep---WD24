@extends('layouts.admin.index')

@section('title', 'Xuất danh sách thanh toán | Quản lý phòng trọ')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div><p class="text-sm font-medium text-slate-500">Quản lý công nợ</p><h2 class="mt-1 text-2xl font-bold text-slate-950">Xuất danh sách thanh toán</h2></div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.invoices.payments.export.download', request()->only(['status', 'method', 'keyword'])) }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700"><i class="bx bx-download text-lg"></i> Tải file CSV</a>
                <a href="{{ route('admin.invoices.payments', request()->only(['status', 'method', 'keyword'])) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="bx bx-arrow-back text-lg"></i> Quay lại</a>
            </div>
        </div>

        <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-700">File CSV sẽ giữ nguyên bộ lọc hiện tại và có thể mở trực tiếp bằng Excel.</div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Giao dịch</th><th class="px-5 py-3">Hóa đơn</th><th class="px-5 py-3">Phòng / khách thuê</th><th class="px-5 py-3 text-right">Số tiền</th><th class="px-5 py-3">Phương thức</th><th class="px-5 py-3">Ngày</th><th class="px-5 py-3">Trạng thái</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($payments as $payment)
                    <tr class="hover:bg-slate-50/70"><td class="px-5 py-4 font-semibold text-slate-950">{{ $payment->transaction_code ?? 'GD-' . $payment->id }}</td><td class="px-5 py-4 text-indigo-600">{{ $payment->invoice->invoice_code ?? '-' }}</td><td class="px-5 py-4"><p class="font-medium text-slate-900">{{ $payment->invoice->room->room_code ?? '-' }}</p><p class="mt-1 text-xs text-slate-500">{{ $payment->invoice->contract->tenant->full_name ?? '-' }}</p></td><td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($payment->amount_paid, 0, ',', '.') }}đ</td><td class="px-5 py-4 text-slate-600">{{ $payment->payment_method }}</td><td class="px-5 py-4 text-slate-600">{{ $payment->payment_date?->format('d/m/Y') ?? '-' }}</td><td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $payment->status }}</span></td></tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">Không có thanh toán để xuất.</td></tr>
                @endforelse
            </tbody>
        </table></div></div>
        @if ($payments->hasPages())<div class="flex justify-end">{{ $payments->links() }}</div>@endif
    </div>
@endsection
