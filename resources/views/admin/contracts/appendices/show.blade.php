@extends('layouts.admin.index')

@section('title', $appendix->code)
@section('page_title', 'Chi tiết phụ lục hợp đồng')

@section('content')
@php
    $colors = [
        'draft' => 'bg-slate-100 text-slate-700', 'pending_tenant' => 'bg-amber-100 text-amber-800',
        'accepted' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-rose-100 text-rose-800',
        'superseded' => 'bg-violet-100 text-violet-800',
    ];
@endphp
<div class="mx-auto max-w-5xl space-y-5">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <a href="{{ route('admin.contracts.show', $appendix->contract) }}" class="text-sm font-semibold text-indigo-700">← Hợp đồng {{ $appendix->contract->contract_code }}</a>
            <div class="mt-2 flex flex-wrap items-center gap-3"><h2 class="text-2xl font-bold text-slate-950">{{ $appendix->code }}</h2><span class="rounded-full px-3 py-1 text-xs font-bold {{ $colors[$appendix->status] ?? 'bg-slate-100' }}">{{ $appendix->status_label }}</span></div>
            <p class="mt-1 text-sm text-slate-600">Phụ lục số {{ $appendix->appendix_number }} · Bản sửa đổi {{ $appendix->revision }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($appendix->status === \App\Models\ContractAppendix::STATUS_DRAFT)
                <a href="{{ route('admin.contract-appendices.edit', $appendix) }}" class="inline-flex h-11 items-center rounded-lg border border-indigo-200 bg-white px-4 text-sm font-bold text-indigo-700">Sửa bản nháp</a>
                <form method="POST" action="{{ route('admin.contract-appendices.send', $appendix) }}" onsubmit="return confirm('Gửi phụ lục này cho khách xác nhận? Sau khi gửi sẽ không thể sửa trực tiếp.')">@csrf<button class="h-11 rounded-lg bg-indigo-700 px-5 text-sm font-bold text-white">Gửi khách xác nhận</button></form>
            @elseif($appendix->status === \App\Models\ContractAppendix::STATUS_REJECTED)
                <form method="POST" action="{{ route('admin.contract-appendices.revise', $appendix) }}">@csrf<button class="h-11 rounded-lg bg-indigo-700 px-5 text-sm font-bold text-white">Tạo bản sửa đổi</button></form>
            @endif
        </div>
    </div>

    @if($appendix->rejection_reason)
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-5 text-rose-950"><h3 class="font-bold">Lý do khách từ chối</h3><p class="mt-2 whitespace-pre-line text-sm leading-6">{{ $appendix->rejection_reason }}</p></div>
    @endif

    <article class="mx-auto max-w-3xl bg-white px-8 py-10 shadow-lg ring-1 ring-slate-200 sm:px-14 sm:py-14">
        @include('shared.contract-appendix-document', ['appendix' => $appendix])
    </article>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Người lập</p><p class="mt-1 font-semibold">{{ $appendix->creator?->name ?? '—' }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Thời điểm gửi</p><p class="mt-1 font-semibold">{{ $appendix->sent_at?->format('H:i d/m/Y') ?? 'Chưa gửi' }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Khách phản hồi</p><p class="mt-1 font-semibold">{{ $appendix->responded_at?->format('H:i d/m/Y') ?? 'Chưa phản hồi' }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Kiểm tra toàn vẹn</p><p class="mt-1 font-semibold {{ $appendix->sent_at && $appendix->hasValidContentHash() ? 'text-emerald-700' : 'text-slate-600' }}">{{ $appendix->sent_at ? ($appendix->hasValidContentHash() ? 'SHA-256 hợp lệ' : 'Không hợp lệ') : 'Chưa khóa' }}</p></div>
    </div>
</div>
@endsection
