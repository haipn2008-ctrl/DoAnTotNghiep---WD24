@extends('layouts.admin.index')

@section('title', 'Đối soát giao dịch ngân hàng | Quản lý phòng trọ')
@section('page_title', 'Đối soát giao dịch ngân hàng')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div><p class="text-sm text-slate-500">Thanh toán và hóa đơn</p><h2 class="mt-1 text-2xl font-bold text-slate-950">Nhật ký webhook ngân hàng</h2></div>
            <form method="GET"><select name="status" onchange="this.form.submit()" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm"><option value="">Tất cả trạng thái</option><option value="unmatched" @selected(request('status') === 'unmatched')>Chưa khớp hóa đơn</option><option value="matched" @selected(request('status') === 'matched')>Đã đối soát</option></select></form>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto"><table class="min-w-[950px] w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Giao dịch</th><th class="px-5 py-3">Nội dung</th><th class="px-5 py-3 text-right">Số tiền</th><th class="px-5 py-3">Trạng thái</th><th class="px-5 py-3">Đối soát</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($events as $event)
                        <tr><td class="px-5 py-4"><p class="font-semibold text-slate-950">{{ $event->provider_transaction_id }}</p><p class="mt-1 text-xs text-slate-500">{{ $event->transaction_at?->format('d/m/Y H:i') }}</p></td><td class="max-w-sm px-5 py-4 text-slate-600">{{ $event->content ?: 'Không có nội dung' }}</td><td class="px-5 py-4 text-right font-bold">{{ number_format($event->amount, 0, ',', '.') }}đ</td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $event->status === 'matched' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $event->status === 'matched' ? 'Đã đối soát' : 'Chưa khớp' }}</span><p class="mt-2 text-xs text-slate-500">{{ $event->message }}</p></td><td class="px-5 py-4">
                            @if($event->invoice)<a class="font-semibold text-indigo-700" href="{{ route('admin.invoices.show', $event->invoice) }}">{{ $event->invoice->invoice_code }}</a>
                            @else<form method="POST" action="{{ route('admin.payment-webhooks.reconcile', $event) }}" class="flex gap-2">@csrf<input name="invoice_code" required placeholder="INV-YYYYMM-XXXXXX" class="h-9 w-48 rounded-lg border border-slate-200 px-2 text-xs"><button class="rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white">Đối soát</button></form>@endif
                        </td></tr>
                    @empty<tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">Chưa có giao dịch webhook.</td></tr>@endforelse
                </tbody>
            </table></div>
            @if($events->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $events->links() }}</div>@endif
        </div>
    </div>
@endsection
