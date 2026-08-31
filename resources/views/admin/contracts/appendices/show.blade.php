@extends('layouts.admin.index')

@section('title', $appendix->code)
@section('page_title', 'Chi tiết phụ lục hợp đồng')

@section('content')
@php
    $colors = [
        'draft' => 'bg-slate-100 text-slate-700', 'pending_tenant' => 'bg-amber-100 text-amber-800',
        'accepted' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-rose-100 text-rose-800',
        'superseded' => 'bg-violet-100 text-violet-800',
        'pending_signature' => 'bg-sky-100 text-sky-800',
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
            <a data-contract-print href="{{ route('admin.contract-appendices.print', $appendix) }}" class="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700">In phụ lục</a>
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

    @if($appendix->status === \App\Models\ContractAppendix::STATUS_PENDING_SIGNATURE && $appendix->isExtension())
        <section class="rounded-xl border border-sky-200 bg-sky-50 p-5">
            <h3 class="font-bold text-sky-950">Hoàn tất phụ lục gia hạn</h3>
            <ol class="mt-3 list-decimal space-y-1 pl-5 text-sm leading-6 text-sky-900"><li>In phụ lục và để đại diện hai bên ký.</li><li>Chụp rõ toàn bộ các trang có chữ ký.</li><li>Tải ảnh lên bên dưới để hệ thống chính thức gia hạn hợp đồng.</li></ol>
            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.contract-appendices.complete-extension', $appendix) }}" class="mt-4 rounded-lg border border-sky-200 bg-white p-4" onsubmit="return confirm('Xác nhận ảnh đã thể hiện phụ lục được hai bên ký đầy đủ? Hợp đồng sẽ được gia hạn ngay sau thao tác này.')">
                @csrf
                <label class="block text-sm font-semibold text-slate-700">Ảnh phụ lục đã ký</label>
                <input type="file" name="signed_evidence[]" accept="image/jpeg,image/png,image/webp" multiple required class="mt-2 block w-full rounded-lg border border-slate-200 bg-white p-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">Tối đa 10 ảnh; mỗi ảnh không quá 5 MB. Có thể chọn nhiều trang cùng lúc.</p>
                @error('signed_evidence')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                @error('signed_evidence.*')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                <button class="mt-4 h-11 w-full rounded-lg bg-sky-700 px-5 text-sm font-bold text-white hover:bg-sky-800">Tải minh chứng và hoàn tất gia hạn</button>
            </form>
        </section>
    @endif

    @if(filled($appendix->signed_evidence_paths))
        <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
            <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="font-bold text-emerald-950">Minh chứng phụ lục đã ký</h3><p class="text-xs text-emerald-800">Tải lên {{ $appendix->signed_evidence_uploaded_at?->format('H:i d/m/Y') }} bởi {{ $appendix->evidenceUploader?->name ?? 'Tài khoản đã ngừng hoạt động' }}</p></div>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach($appendix->signed_evidence_paths as $index => $path)
                    <a href="{{ route('admin.contract-appendices.signed-evidence', [$appendix, $index]) }}" data-image-modal data-image-title="Bản cứng phụ lục {{ $appendix->code }} - Trang {{ $index + 1 }}" class="overflow-hidden rounded-lg border border-emerald-200 bg-white"><img src="{{ route('admin.contract-appendices.signed-evidence', [$appendix, $index]) }}" alt="Minh chứng trang {{ $index + 1 }}" class="h-40 w-full object-cover"><span class="block px-3 py-2 text-center text-xs font-semibold text-emerald-800">Trang {{ $index + 1 }}</span></a>
                @endforeach
            </div>
        </section>
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
