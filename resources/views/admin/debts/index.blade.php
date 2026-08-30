@extends('layouts.admin.index')

@section('title', 'Công nợ | Quản lý phòng trọ')
@section('page_title', 'Công nợ')

@section('content')
    <div class="space-y-5">
        <div>
            <p class="text-sm font-medium text-slate-500">Theo dõi phải thu</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Danh sách công nợ</h2>
            <p class="mt-2 text-sm text-slate-500">Thanh toán chờ duyệt được hiển thị riêng và chưa được tính là đã thu.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Số hóa đơn</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($summary->invoice_count ?? 0) }}</p></div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-5"><p class="text-sm text-amber-700">Đang chờ duyệt</p><p class="mt-2 text-2xl font-bold text-amber-800">{{ number_format($summary->pending_amount ?? 0, 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-5"><p class="text-sm text-rose-700">Tổng còn nợ</p><p class="mt-2 text-2xl font-bold text-rose-800">{{ number_format($summary->remaining_amount ?? 0, 0, ',', '.') }}đ</p></div>
        </div>

        <form method="GET" action="{{ route('admin.debts.index') }}" class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_240px_auto] lg:items-end">
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Tìm kiếm</label><input name="keyword" value="{{ $keyword }}" placeholder="Hóa đơn, hợp đồng, phòng, khách thuê..." class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nhóm công nợ</label><select name="bucket" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"><option value="">Tất cả</option>@foreach($buckets as $value => $label)<option value="{{ $value }}" @selected($bucket === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="flex gap-2"><button class="h-11 rounded-lg bg-slate-900 px-5 text-sm font-semibold text-white">Lọc</button><a href="{{ route('admin.debts.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600">Xóa lọc</a></div>
        </form>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Hóa đơn / khách thuê</th><th class="px-5 py-3">Hạn & mức ưu tiên</th><th class="px-5 py-3 text-right">Phải thu</th><th class="px-5 py-3 text-right">Đã duyệt</th><th class="px-5 py-3 text-right">Chờ duyệt</th><th class="px-5 py-3 text-right">Còn nợ</th><th class="px-5 py-3">Lần nhắc gần nhất</th><th class="px-5 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($invoices as $invoice)
                            @php
                                $paid = (float) $invoice->paid_amount;
                                $pending = (float) $invoice->pending_amount;
                                $remaining = max(0, $invoice->payable_amount - $paid);
                                $bucketClass = $invoice->days_overdue > 0 ? 'bg-rose-50 text-rose-700' : ($invoice->debt_bucket === 'due_today' ? 'bg-amber-50 text-amber-700' : 'bg-sky-50 text-sky-700');
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4"><a href="{{ route('admin.invoices.show', $invoice) }}" class="font-bold text-indigo-700">{{ $invoice->invoice_code }}</a><p class="mt-1 font-medium text-slate-900">{{ $invoice->contract->tenant->full_name ?? '-' }}</p><p class="mt-1 text-xs text-slate-500">Phòng {{ $invoice->room->room_code ?? '-' }} · {{ $invoice->contract->contract_code ?? '-' }}</p></td>
                                <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $invoice->effective_due_date?->format('d/m/Y') }}</p>@if($invoice->payment_extension_until)<p class="mt-1 text-xs text-slate-500">Hạn gốc {{ $invoice->due_date?->format('d/m/Y') }}</p>@endif<span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $bucketClass }}">{{ $invoice->debt_bucket_label }}</span></td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($invoice->payable_amount, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-right font-semibold text-emerald-700">{{ number_format($paid, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-right font-semibold text-amber-700">{{ number_format($pending, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-right font-bold text-rose-700">{{ number_format($remaining, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-slate-600">@if($invoice->reminders->first())<p class="font-medium text-slate-900">{{ $invoice->reminders->first()->reminded_at?->format('d/m/Y H:i') }}</p><p class="mt-1 text-xs">{{ $invoice->reminders->first()->channel_label }} · {{ $invoice->reminders_count }} lần</p>@else<span class="text-slate-400">Chưa nhắc</span>@endif</td>
                                <td class="px-5 py-4 text-right"><a href="{{ route('admin.debts.show', $invoice) }}" class="inline-flex h-9 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Gửi nhắc / lịch sử</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">Không có công nợ phù hợp.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if($invoices->hasPages())<div>{{ $invoices->links() }}</div>@endif
    </div>
@endsection
