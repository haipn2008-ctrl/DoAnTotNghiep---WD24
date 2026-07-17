@extends('layouts.admin.index')

@section('title', 'Lịch sử thanh toán | Quản lý phòng trọ')
@section('page_title', 'Lịch sử thanh toán')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Quản lý công nợ</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Lịch sử thanh toán</h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.invoices.payments.export', request()->query()) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="bx bx-export text-lg"></i> Xuất CSV</a>
                <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="bx bx-arrow-back text-lg"></i> Hóa đơn</a>
            </div>
        </div>

        <form method="GET" class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_180px_180px_auto] md:items-end">
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Tìm kiếm</label><input name="keyword" value="{{ request('keyword') }}" placeholder="Mã giao dịch, hóa đơn, phòng..." class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Phương thức</label><select name="method" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"><option value="">Tất cả</option><option value="cash" @selected(request('method') === 'cash')>Tiền mặt</option><option value="bank_transfer" @selected(request('method') === 'bank_transfer')>Chuyển khoản</option><option value="qr" @selected(request('method') === 'qr')>QR</option></select></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Trạng thái</label><select name="status" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"><option value="">Tất cả</option><option value="success" @selected(request('status') === 'success')>Thành công</option><option value="pending" @selected(request('status') === 'pending')>Chờ xử lý</option><option value="failed" @selected(request('status') === 'failed')>Thất bại</option></select></div>
            <button class="h-11 rounded-lg bg-slate-900 px-5 text-sm font-semibold text-white hover:bg-slate-800">Lọc</button>
        </form>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Giao dịch</th><th class="px-5 py-3">Hóa đơn</th><th class="px-5 py-3">Phòng / khách thuê</th><th class="px-5 py-3 text-right">Số tiền</th><th class="px-5 py-3">Phương thức</th><th class="px-5 py-3">Ngày</th><th class="px-5 py-3">Trạng thái</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($payments as $payment)
                            <tr class="hover:bg-slate-50/70"><td class="px-5 py-4 font-semibold text-slate-950">{{ $payment->transaction_code ?? 'GD-' . $payment->id }}</td><td class="px-5 py-4"><a class="font-semibold text-indigo-600 hover:text-indigo-700" href="{{ route('admin.invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_code ?? 'HDON' . str_pad($payment->invoice_id, 5, '0', STR_PAD_LEFT) }}</a></td><td class="px-5 py-4"><p class="font-medium text-slate-900">{{ $payment->invoice->room->room_code ?? '-' }}</p><p class="mt-1 text-xs text-slate-500">{{ $payment->invoice->contract->tenant->full_name ?? '-' }}</p></td><td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($payment->amount_paid, 0, ',', '.') }}đ</td><td class="px-5 py-4 text-slate-600">{{ $payment->payment_method }}</td><td class="px-5 py-4 text-slate-600">{{ $payment->payment_date?->format('d/m/Y') ?? '-' }}</td><td class="px-5 py-4"><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">{{ $payment->status }}</span></td></tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">Không có thanh toán phù hợp.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($payments->hasPages())<div class="flex justify-end">{{ $payments->links() }}</div>@endif
    </div>
@endsection
