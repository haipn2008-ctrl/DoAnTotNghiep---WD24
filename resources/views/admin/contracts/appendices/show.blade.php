@extends('layouts.admin.index')

@section('title', $appendix->code)
@section('page_title', 'Chi tiết phụ lục hợp đồng')

@section('content')
@php
    $colors = [
        'draft' => ['class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'],
        'pending_tenant' => ['class' => 'bg-amber-50 text-amber-800 ring-amber-200', 'dot' => 'bg-amber-500'],
        'accepted' => ['class' => 'bg-emerald-50 text-emerald-800 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'rejected' => ['class' => 'bg-rose-50 text-rose-800 ring-rose-200', 'dot' => 'bg-rose-500'],
        'superseded' => ['class' => 'bg-violet-50 text-violet-800 ring-violet-200', 'dot' => 'bg-violet-500'],
        'pending_signature' => ['class' => 'bg-sky-50 text-sky-800 ring-sky-200', 'dot' => 'bg-sky-500'],
    ];
    $statusStyle = $colors[$appendix->status] ?? ['class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'];
@endphp
<div class="mx-auto max-w-7xl space-y-4">
    <header class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
            <div class="min-w-0">
                <a href="{{ route('admin.contracts.show', $appendix->contract) }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                    <i class="bx bx-left-arrow-alt text-lg"></i>Hợp đồng {{ $appendix->contract->contract_code }}
                </a>
                <div class="mt-3 flex flex-wrap items-center gap-2.5">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-950">{{ $appendix->code }}</h2>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $statusStyle['class'] }}"><span class="h-1.5 w-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>{{ $appendix->status_label }}</span>
                </div>
                <p class="mt-1 truncate text-sm font-medium text-slate-600">{{ $appendix->title }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a data-contract-print href="{{ route('admin.contract-appendices.print', $appendix) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 hover:bg-slate-50"><i class="bx bx-printer text-lg"></i>In phụ lục</a>
            @if($appendix->status === \App\Models\ContractAppendix::STATUS_DRAFT)
                <a href="{{ route('admin.contract-appendices.edit', $appendix) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-indigo-200 bg-white px-4 text-sm font-bold text-indigo-700 hover:bg-indigo-50"><i class="bx bx-edit text-lg"></i>Sửa</a>
                <form method="POST" action="{{ route('admin.contract-appendices.send', $appendix) }}" data-confirm="Phụ lục sẽ được gửi cho khách xác nhận và không thể sửa trực tiếp sau khi gửi." data-confirm-label="Gửi phụ lục">@csrf<button class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-bold text-white hover:bg-indigo-700"><i class="bx bx-send text-lg"></i>Gửi khách</button></form>
            @elseif($appendix->status === \App\Models\ContractAppendix::STATUS_REJECTED && ! $appendix->isRoomTransfer())
                <form method="POST" action="{{ route('admin.contract-appendices.revise', $appendix) }}">@csrf<button class="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-bold text-white hover:bg-indigo-700"><i class="bx bx-refresh text-lg"></i>Tạo bản sửa đổi</button></form>
            @endif
            </div>
        </div>
    </header>

    @if($appendix->rejection_reason)
        <div class="flex gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-950"><i class="bx bx-error-circle mt-0.5 text-xl text-rose-600"></i><div><h3 class="font-bold">Khách từ chối</h3><p class="mt-1 whitespace-pre-line text-sm">{{ $appendix->rejection_reason }}</p></div></div>
    @endif

    @if($appendix->status === \App\Models\ContractAppendix::STATUS_PENDING_SIGNATURE && $appendix->isExtension())
        <section class="rounded-xl border border-sky-200 bg-sky-50 p-4">
            <div class="flex items-center gap-2"><i class="bx bx-upload text-xl text-sky-700"></i><h3 class="font-bold text-sky-950">Hoàn tất gia hạn</h3></div>
            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.contract-appendices.complete-extension', $appendix) }}" class="mt-3 grid gap-3 rounded-lg border border-sky-200 bg-white p-4 lg:grid-cols-[1fr_auto] lg:items-end" data-confirm="Xác nhận ảnh đã thể hiện phụ lục được hai bên ký đầy đủ. Hợp đồng sẽ được gia hạn ngay sau thao tác này." data-confirm-label="Hoàn tất gia hạn">
                @csrf
                <label class="block text-sm font-semibold text-slate-700">Ảnh đã ký <span class="font-normal text-slate-400">· Tối đa 10 ảnh, 5 MB/ảnh</span><input type="file" name="signed_evidence[]" accept="image/jpeg,image/png,image/webp" multiple required class="mt-2 block w-full rounded-lg border border-slate-200 bg-white p-2 text-sm"></label>
                @error('signed_evidence')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                @error('signed_evidence.*')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                <button class="h-11 rounded-lg bg-sky-700 px-5 text-sm font-bold text-white hover:bg-sky-800">Tải lên và hoàn tất</button>
            </form>
        </section>
    @endif

    @if($appendix->status === \App\Models\ContractAppendix::STATUS_PENDING_SIGNATURE && $appendix->isRoomTransfer())
        <section class="rounded-xl border border-violet-200 bg-violet-50 p-4">
            <div class="flex items-center gap-2"><i class="bx bx-transfer-alt text-xl text-violet-700"></i><div><h3 class="font-bold text-violet-950">Xác nhận bàn giao và chuyển phòng</h3><p class="mt-1 text-sm text-violet-800">Khách đã đồng ý nội dung. Hợp đồng chỉ được cập nhật sau khi tải minh chứng phụ lục đã ký.</p></div></div>
            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.contract-appendices.complete-room-transfer', $appendix) }}" class="mt-3 grid gap-3 rounded-lg border border-violet-200 bg-white p-4 lg:grid-cols-[1fr_auto] lg:items-end" data-room-transfer-completion-form>
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Ảnh phụ lục đã ký <span class="font-normal text-slate-400">· Tối đa 10 ảnh, 5 MB/ảnh</span><input type="file" name="signed_evidence[]" accept="image/jpeg,image/png,image/webp" multiple required class="mt-2 block w-full rounded-lg border border-slate-200 bg-white p-2 text-sm" data-room-transfer-evidence></label>
                    <p class="mt-2 text-xs font-semibold text-slate-500" data-room-transfer-file-status>Chưa chọn ảnh minh chứng.</p>
                    @error('signed_evidence')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                    @error('signed_evidence.*')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                    @error('effective_date')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                    @error('appendix')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                    @error('transfer')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                    @error('new_room_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                </div>
                <button type="submit" disabled class="h-11 cursor-not-allowed rounded-lg bg-violet-700 px-5 text-sm font-bold text-white opacity-50 transition hover:bg-violet-800" data-room-transfer-complete>Xác nhận bàn giao và chuyển phòng</button>
            </form>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const form = document.querySelector('[data-room-transfer-completion-form]');
                    const input = form?.querySelector('[data-room-transfer-evidence]');
                    const button = form?.querySelector('[data-room-transfer-complete]');
                    const status = form?.querySelector('[data-room-transfer-file-status]');
                    if (!input || !button || !status) return;
                    input.addEventListener('change', () => {
                        const count = input.files?.length || 0;
                        status.textContent = count ? `Đã chọn ${count} ảnh minh chứng.` : 'Chưa chọn ảnh minh chứng.';
                        status.classList.toggle('text-emerald-700', count > 0);
                        button.disabled = count === 0;
                        button.classList.toggle('cursor-not-allowed', count === 0);
                        button.classList.toggle('opacity-50', count === 0);
                    });
                });
            </script>
        </section>
    @endif

    <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-3.5"><h3 class="font-bold text-slate-950">Nội dung phụ lục</h3><span class="text-xs font-semibold text-slate-500">Bản xem trước</span></div>
            <div class="overflow-x-auto p-4 sm:p-7">
                <article class="mx-auto min-h-[900px] max-w-[794px] bg-white px-8 py-10 shadow-md ring-1 ring-slate-200 sm:px-16 sm:py-14">
                    @include('shared.contract-appendix-document', ['appendix' => $appendix])
                </article>
            </div>
        </section>

        <aside class="space-y-4 xl:sticky xl:top-20">
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3.5"><h3 class="font-bold text-slate-950">Thông tin phụ lục</h3></div>
                <dl class="divide-y divide-slate-100 px-4 text-sm">
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Phụ lục</dt><dd class="font-semibold">Số {{ $appendix->appendix_number }}</dd></div>
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Bản sửa đổi</dt><dd class="font-semibold">{{ $appendix->revision }}</dd></div>
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Hiệu lực</dt><dd class="font-semibold">{{ $appendix->effective_from?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Người lập</dt><dd class="text-right font-semibold">{{ $appendix->creator?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Đã gửi</dt><dd class="text-right font-semibold">{{ $appendix->sent_at?->format('H:i d/m/Y') ?? 'Chưa gửi' }}</dd></div>
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Phản hồi</dt><dd class="text-right font-semibold">{{ $appendix->responded_at?->format('H:i d/m/Y') ?? 'Chưa có' }}</dd></div>
                    <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">Toàn vẹn</dt><dd class="text-right font-semibold {{ $appendix->sent_at && $appendix->hasValidContentHash() ? 'text-emerald-700' : 'text-slate-600' }}">{{ $appendix->sent_at ? ($appendix->hasValidContentHash() ? 'SHA-256 hợp lệ' : 'Không hợp lệ') : 'Chưa khóa' }}</dd></div>
                </dl>
            </section>

            @if(filled($appendix->signed_evidence_paths))
                <section class="overflow-hidden rounded-xl border border-emerald-200 bg-white shadow-sm">
                    <div class="border-b border-emerald-100 bg-emerald-50 px-4 py-3.5"><div class="flex items-center gap-2"><i class="bx bx-check-circle text-lg text-emerald-700"></i><h3 class="font-bold text-emerald-950">Minh chứng đã ký</h3></div><p class="mt-1 text-xs text-emerald-800">{{ $appendix->signed_evidence_uploaded_at?->format('H:i d/m/Y') }} · {{ $appendix->evidenceUploader?->name ?? 'Tài khoản đã ngừng hoạt động' }}</p></div>
                    <div class="grid grid-cols-2 gap-2 p-3">
                        @foreach($appendix->signed_evidence_paths as $index => $path)
                            <a href="{{ route('admin.contract-appendices.signed-evidence', [$appendix, $index]) }}" data-image-modal data-image-title="Bản cứng phụ lục {{ $appendix->code }} - Trang {{ $index + 1 }}" class="group overflow-hidden rounded-lg border border-slate-200 bg-slate-50"><img src="{{ route('admin.contract-appendices.signed-evidence', [$appendix, $index]) }}" alt="Minh chứng trang {{ $index + 1 }}" class="aspect-[4/3] w-full object-cover transition group-hover:scale-105"><span class="block px-2 py-1.5 text-center text-xs font-semibold text-slate-700">Trang {{ $index + 1 }}</span></a>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection
