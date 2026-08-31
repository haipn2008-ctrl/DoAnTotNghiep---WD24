@extends('layouts.admin.index')

@section('title', 'Yêu cầu gia hạn')
@section('page_title', 'Quản lý phòng trọ')

@section('content')
<div class="space-y-6">
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-semibold">Không thể xử lý yêu cầu gia hạn:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Yêu cầu gia hạn hợp đồng</h1>
        <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">← Quản lý hợp đồng</a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Tổng yêu cầu', $extensionRequests->count(), 'border-slate-200 bg-white text-slate-900', 'bg-indigo-50 text-indigo-600', '#'],
            ['Chờ admin xem', $extensionRequests->where('status', 'pending')->count(), 'border-amber-200 bg-amber-50/60 text-amber-700', 'bg-amber-100 text-amber-700', '◷'],
            ['Chờ ký phụ lục', $extensionRequests->where('status', 'awaiting_confirmation')->count(), 'border-sky-200 bg-sky-50/60 text-sky-700', 'bg-sky-100 text-sky-700', '…'],
            ['Đã gia hạn', $extensionRequests->where('status', 'approved')->count(), 'border-emerald-200 bg-emerald-50/60 text-emerald-700', 'bg-emerald-100 text-emerald-700', '✓'],
        ] as [$label, $count, $cardClass, $iconClass, $icon])
            <div class="rounded-2xl border p-5 shadow-sm {{ $cardClass }}"><div class="flex items-center justify-between"><div><p class="text-sm font-medium opacity-80">{{ $label }}</p><p class="mt-2 text-3xl font-bold">{{ $count }}</p></div><div class="flex h-11 w-11 items-center justify-center rounded-xl text-xl font-bold {{ $iconClass }}">{{ $icon }}</div></div></div>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-5 sm:px-6"><h2 class="font-bold text-slate-900">Danh sách yêu cầu</h2><span class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-600">{{ $extensionRequests->count() }} yêu cầu</span></div>
        <div class="grid gap-4 bg-slate-50/70 p-4 lg:grid-cols-2 sm:p-6">
            @forelse($extensionRequests as $request)
                @php
                    $statusMeta = match ($request->status) {
                        'awaiting_confirmation' => ['Chờ ký phụ lục', 'border-sky-200 bg-sky-50 text-sky-700', 'bg-sky-500'],
                        'approved' => ['Đã gia hạn', 'border-emerald-200 bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
                        'rejected' => ['Admin từ chối', 'border-rose-200 bg-rose-50 text-rose-700', 'bg-rose-500'],
                        'declined_by_tenant' => ['Khách không đồng ý', 'border-violet-200 bg-violet-50 text-violet-700', 'bg-violet-500'],
                        default => ['Chờ admin xem', 'border-amber-200 bg-amber-50 text-amber-700', 'bg-amber-500'],
                    };
                    $outstanding = $request->contract->invoices
                        ->whereIn('status', ['unpaid', 'partial'])
                        ->sum(fn ($invoice) => (float) $invoice->remaining_amount);
                @endphp
                <article id="request-{{ $request->id }}" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm target:ring-2 target:ring-indigo-300">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-sm font-bold text-indigo-700">HĐ</span><div><p class="font-bold text-slate-950">{{ $request->contract->contract_code ?? '-' }}</p><p class="mt-1 text-xs text-slate-500">Phòng {{ $request->contract->room->room_code ?? '-' }}</p></div></div>
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusMeta[1] }}"><span class="h-1.5 w-1.5 rounded-full {{ $statusMeta[2] }}"></span>{{ $statusMeta[0] }}</span>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Người thuê đại diện</p><p class="mt-2 font-semibold text-slate-900">{{ $request->contract->tenant->full_name ?? '-' }}</p><p class="mt-1 text-sm text-slate-500">{{ $request->contract->tenant->phone ?? 'Chưa có số điện thoại' }}</p></div>
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-indigo-400">Thời hạn đề nghị</p><div class="mt-2 flex items-center gap-2 text-sm"><span>{{ $request->current_end_date?->format('d/m/Y') }}</span><span class="text-indigo-400">→</span><span class="font-bold text-indigo-700">{{ ($request->approved_end_date ?: $request->requested_end_date)?->format('d/m/Y') }}</span></div></div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border px-4 py-3 {{ $outstanding > 0 ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50' }}"><p class="text-xs font-semibold uppercase {{ $outstanding > 0 ? 'text-rose-500' : 'text-emerald-600' }}">Công nợ hiện tại</p><p class="mt-1 font-bold {{ $outstanding > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format($outstanding, 0, ',', '.') }}đ</p></div>
                        <div class="rounded-xl border border-slate-200 px-4 py-3"><p class="text-xs font-semibold uppercase text-slate-400">Người tiếp tục thuê</p><p class="mt-1 font-bold text-slate-800">{{ $request->contract->currentMembers->count() }} người</p></div>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-100 px-4 py-3"><p class="text-xs font-semibold uppercase text-slate-400">Lý do gia hạn</p><p class="mt-1.5 text-sm leading-6 text-slate-700">{{ $request->reason ?: 'Không có lý do' }}</p>@if($request->admin_note)<p class="mt-2 border-t border-slate-100 pt-2 text-sm text-slate-600"><span class="font-semibold">Ghi chú quản lý:</span> {{ $request->admin_note }}</p>@endif @if($request->tenant_decline_reason)<p class="mt-2 border-t border-slate-100 pt-2 text-sm text-violet-700"><span class="font-semibold">Lý do khách không đồng ý:</span> {{ $request->tenant_decline_reason }}</p>@endif</div>

                    @if($request->status === 'pending')
                        <div class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                            <form method="POST" action="{{ route('admin.extension-requests.approve', $request) }}" class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
                                @csrf
                                <p class="mb-3 text-sm font-semibold text-emerald-800">Duyệt điều khoản và lập phụ lục</p>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="text-xs font-semibold text-slate-600">Gia hạn đến<input type="date" name="approved_end_date" required value="{{ old('approved_end_date', $request->requested_end_date?->format('Y-m-d')) }}" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm"></label>
                                    <label class="text-xs font-semibold text-slate-600">Giá phòng mới<input type="number" name="proposed_monthly_rent" required min="0" step="1000" value="{{ old('proposed_monthly_rent', (float) $request->contract->monthly_rent) }}" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm"></label>
                                </div>
                                <input name="admin_note" maxlength="1000" placeholder="Ghi chú điều khoản (không bắt buộc)" class="mt-3 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                                @if($outstanding > 0)<textarea name="financial_override_reason" required minlength="3" maxlength="1000" rows="2" placeholder="Hợp đồng còn nợ: nhập lý do vẫn đề nghị gia hạn" class="mt-3 w-full rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm"></textarea>@endif
                                <button class="mt-3 h-11 w-full rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white hover:bg-emerald-700">Lập phụ lục gia hạn</button>
                            </form>
                            <form method="POST" action="{{ route('admin.extension-requests.reject', $request) }}" class="flex gap-2">@csrf<input name="reject_reason" required minlength="3" maxlength="1000" placeholder="Nhập lý do từ chối" class="h-11 min-w-0 flex-1 rounded-xl border border-slate-200 px-3 text-sm"><button class="h-11 shrink-0 rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700">Từ chối</button></form>
                        </div>
                    @elseif($request->status === 'awaiting_confirmation')
                        <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800"><p class="font-semibold">Đang chờ in, ký và tải minh chứng</p><p class="mt-1">Giá phòng đề nghị: <strong>{{ number_format((float) $request->proposed_monthly_rent, 0, ',', '.') }}đ/tháng</strong></p>@if($request->appendix)<a href="{{ route('admin.contract-appendices.show', $request->appendix) }}" class="mt-3 inline-flex h-10 items-center rounded-lg bg-sky-700 px-4 font-semibold text-white">Mở phụ lục {{ $request->appendix->code }}</a>@endif</div>
                    @else
                        <div class="mt-4 border-t border-slate-100 pt-4 text-right text-sm font-medium text-slate-400">Yêu cầu đã được xử lý</div>
                    @endif
                </article>
            @empty
                <div class="py-14 text-center lg:col-span-2"><div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">□</div><h3 class="mt-4 font-semibold text-slate-900">Chưa có yêu cầu gia hạn</h3></div>
            @endforelse
        </div>
    </section>
</div>
@endsection
