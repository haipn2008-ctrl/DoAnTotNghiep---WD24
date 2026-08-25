@extends('layouts.admin.index')

@section('title', 'Đối soát thu tiền | Quản lý phòng trọ')
@section('page_title', 'Đối soát thu tiền')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div><p class="text-sm font-medium text-slate-500">Kỳ {{ $month }}/{{ $year }}</p><h2 class="mt-1 text-2xl font-bold text-slate-950">Báo cáo đối soát thu tiền</h2><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Đối chiếu hóa đơn phát hành trong kỳ với số đã thu, đồng thời tách riêng dòng tiền thực nhận theo ngày thanh toán.</p></div>
            <form method="GET" action="{{ route('admin.reconciliation.index') }}" class="flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Tháng</label><select name="month" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">@foreach(range(1, 12) as $value)<option value="{{ $value }}" @selected($month === $value)>Tháng {{ $value }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Năm</label><select name="year" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">@foreach($years as $value)<option value="{{ $value }}" @selected($year === (int) $value)>{{ $value }}</option>@endforeach</select></div>
                <button class="h-10 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white">Xem báo cáo</button>
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Hóa đơn phát hành</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($summary['gross_billed'], 0, ',', '.') }}đ</p><p class="mt-1 text-xs text-slate-400">{{ $summary['invoice_count'] }} hóa đơn, đã loại hóa đơn hủy</p></div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-5"><p class="text-sm text-emerald-700">Đã thu của nhóm hóa đơn</p><p class="mt-2 text-2xl font-bold text-emerald-800">{{ number_format($summary['cohort_collected'], 0, ',', '.') }}đ</p><p class="mt-1 text-xs text-emerald-600">Có thể được thu ở kỳ sau</p></div>
            <div class="rounded-lg border border-sky-200 bg-sky-50 p-5"><p class="text-sm text-sky-700">Tiền thực nhận trong kỳ</p><p class="mt-2 text-2xl font-bold text-sky-800">{{ number_format($summary['cash_received'], 0, ',', '.') }}đ</p><p class="mt-1 text-xs text-sky-600">Bao gồm thanh toán hóa đơn cũ</p></div>
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-5"><p class="text-sm text-rose-700">Còn phải thu</p><p class="mt-2 text-2xl font-bold text-rose-800">{{ number_format($summary['outstanding_amount'], 0, ',', '.') }}đ</p><p class="mt-1 text-xs text-rose-600">Chưa trừ khoản đang chờ duyệt</p></div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-5"><p class="text-sm text-amber-700">Đang chờ duyệt</p><p class="mt-2 text-xl font-bold text-amber-800">{{ number_format($summary['pending_amount'], 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-violet-200 bg-violet-50 p-5"><p class="text-sm text-violet-700">Đã xóa nợ</p><p class="mt-2 text-xl font-bold text-violet-800">{{ number_format($summary['written_off_amount'], 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-fuchsia-200 bg-fuchsia-50 p-5"><p class="text-sm text-fuchsia-700">Thu thừa cần đối soát</p><p class="mt-2 text-xl font-bold text-fuchsia-800">{{ number_format($summary['overpaid_amount'], 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Tỷ lệ thu hồi nhóm hóa đơn</p><p class="mt-2 text-xl font-bold text-slate-950">{{ $summary['gross_billed'] > 0 ? min(100, round($summary['cohort_collected'] / $summary['gross_billed'] * 100, 1)) : 0 }}%</p></div>
        </div>

        <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-800"><strong>Phân biệt:</strong> “Đã thu của nhóm hóa đơn” bám theo tháng phát hành hóa đơn; “Tiền thực nhận trong kỳ” bám theo ngày thanh toán được ghi nhận.</div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Chi tiết hóa đơn phát hành trong kỳ</h3></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Hóa đơn</th><th class="px-5 py-3">Khách thuê / phòng</th><th class="px-5 py-3">Ngày phát hành</th><th class="px-5 py-3 text-right">Phải thu</th><th class="px-5 py-3 text-right">Đã thu</th><th class="px-5 py-3 text-right">Chờ duyệt</th><th class="px-5 py-3 text-right">Chênh lệch</th><th class="px-5 py-3">Trạng thái</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($invoices as $invoice)
                            @php
                                $paid = (float) $invoice->paid_amount;
                                $pending = (float) $invoice->pending_amount;
                                $difference = max(0, $invoice->payable_amount - $paid);
                                $overpaid = max(0, $paid - $invoice->payable_amount);
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4"><a href="{{ route('admin.invoices.show', $invoice) }}" class="font-bold text-indigo-700">{{ $invoice->invoice_code }}</a><p class="mt-1 text-xs text-slate-500">Lần phát hành {{ $invoice->revision }}</p></td>
                                <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $invoice->contract->tenant->full_name ?? '-' }}</p><p class="mt-1 text-xs text-slate-500">Phòng {{ $invoice->room->room_code ?? '-' }}</p></td>
                                <td class="px-5 py-4 text-slate-600">{{ $invoice->invoice_date?->format('d/m/Y') }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($invoice->payable_amount, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-right font-semibold text-emerald-700">{{ number_format($paid, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-right font-semibold text-amber-700">{{ number_format($pending, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-right font-bold {{ $overpaid > 0 ? 'text-fuchsia-700' : ($invoice->status === \App\Models\Invoice::STATUS_WRITTEN_OFF ? 'text-violet-700' : 'text-rose-700') }}">{{ number_format($overpaid > 0 ? $overpaid : $difference, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4"><span class="text-xs font-semibold text-slate-600">{{ $overpaid > 0 ? 'Thu thừa' : ($invoice->status === \App\Models\Invoice::STATUS_WRITTEN_OFF ? 'Đã xóa nợ' : $invoice->status_label) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">Không có hóa đơn phát hành trong kỳ này.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if($invoices->hasPages())<div>{{ $invoices->links() }}</div>@endif
    </div>
@endsection
