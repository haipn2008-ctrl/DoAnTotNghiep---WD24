@extends('layouts.admin.index')

@section('title', 'Chi tiết giấy tạm trú')
@section('page_title', 'Chi tiết giấy tạm trú')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div><a href="{{ route('admin.temporary_residences.index') }}" class="text-sm font-semibold text-indigo-700">← Danh sách giấy tạm trú</a><h2 class="mt-2 text-2xl font-bold text-slate-950">{{ $temporaryResidence->contractTenant?->full_name ?? $temporaryResidence->tenant?->full_name }}</h2></div>
        @if($temporaryResidence->status !== 'cancelled' && ! $temporaryResidence->signed_at)
            <a href="{{ route('admin.temporary_residences.edit', $temporaryResidence) }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white">Cập nhật giấy</a>
        @endif
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4"><h3 class="font-bold text-slate-950">Thông tin giấy tạm trú</h3><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $temporaryResidence->status_label }}</span></div>
        <dl class="grid gap-0 sm:grid-cols-2 lg:grid-cols-3">
            @foreach([
                'CCCD' => $temporaryResidence->contractTenant?->identity_number ?? $temporaryResidence->tenant?->cccd ?? '—',
                'Phòng' => $temporaryResidence->room?->room_code ?? $temporaryResidence->contract?->room?->room_code ?? '—',
                'Hợp đồng' => $temporaryResidence->contract?->contract_code ?? '—',
                'Hiệu lực từ' => $temporaryResidence->start_date?->format('d/m/Y') ?? '—',
                'Hiệu lực đến' => $temporaryResidence->end_date?->format('d/m/Y') ?? 'Không thời hạn',
                'Mã hồ sơ' => $temporaryResidence->reference_number ?: 'Chưa cập nhật',
            ] as $label => $value)
                <div class="border-b border-slate-100 p-5"><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 font-semibold text-slate-950">{{ $value }}</dd></div>
            @endforeach
        </dl>
        <div class="grid gap-4 p-5 sm:grid-cols-2"><div><p class="text-xs text-slate-500">Người cập nhật/xác minh</p><p class="mt-1 font-semibold text-slate-950">{{ $temporaryResidence->verifiedBy?->name ?? 'Dữ liệu cũ' }}</p><p class="text-xs text-slate-500">{{ $temporaryResidence->verified_at?->format('H:i d/m/Y') ?? 'Chưa ghi nhận' }}</p></div><div><p class="text-xs text-slate-500">Ghi chú</p><p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $temporaryResidence->note ?: 'Không có' }}</p></div></div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="font-bold text-slate-950">Minh chứng</h3>
        @if($temporaryResidence->evidenceExists())
            <p class="mt-1 text-sm text-slate-500">{{ $temporaryResidence->evidence_original_name }}</p>
            @if(! $temporaryResidence->evidenceIsPdf())
                <a href="{{ route('admin.temporary_residences.evidence', $temporaryResidence) }}" data-image-modal data-media-type="image" data-image-title="Minh chứng giấy tạm trú {{ $temporaryResidence->reference_number ?: '#'.$temporaryResidence->id }}"><img src="{{ route('admin.temporary_residences.evidence', $temporaryResidence) }}" alt="Minh chứng giấy tạm trú" class="mt-4 max-h-[560px] rounded-lg border border-slate-200 object-contain"></a>
            @else
                <a href="{{ route('admin.temporary_residences.evidence', $temporaryResidence) }}" data-image-modal data-media-type="pdf" data-image-title="Minh chứng giấy tạm trú {{ $temporaryResidence->reference_number ?: '#'.$temporaryResidence->id }}" class="mt-4 inline-flex rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700">Xem PDF minh chứng</a>
            @endif
        @elseif($temporaryResidence->evidence_path)
            <p class="mt-3 rounded-lg bg-amber-50 p-4 text-sm font-semibold text-amber-800">Tệp minh chứng không còn tồn tại trong hệ thống.</p>
        @else
            <p class="mt-3 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">Hồ sơ cũ chưa có tệp minh chứng.</p>
        @endif
        @if($temporaryResidence->status !== 'cancelled')
            <form id="evidence-upload" method="POST" action="{{ route('admin.temporary_residences.evidence.update', $temporaryResidence) }}" enctype="multipart/form-data" class="mt-5 rounded-xl border border-indigo-200 bg-indigo-50 p-4">@csrf @method('PATCH')
                <label class="block text-sm font-semibold text-indigo-950">{{ $temporaryResidence->evidence_path ? 'Thay tệp minh chứng' : 'Bổ sung tệp minh chứng' }}
                    <input type="file" name="evidence" required accept="image/jpeg,image/png,image/webp,application/pdf" class="mt-2 block w-full rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm font-normal file:mr-4 file:rounded-md file:border-0 file:bg-indigo-100 file:px-3 file:py-2 file:font-semibold file:text-indigo-700">
                </label>
                <p class="mt-1 text-xs text-indigo-700">JPG, PNG, WEBP hoặc PDF; tối đa 5 MB. Có thể cập nhật ngay cả với hồ sơ nội bộ đã ký.</p>
                <button class="mt-3 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white">Lưu minh chứng</button>
            </form>
        @endif
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-bold text-slate-950">Lịch sử giấy tạm trú của người thuê</h3><p class="mt-1 text-xs text-slate-500">Các lần cập nhật trước được giữ lại để đối chiếu.</p></div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-sm"><thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Hiệu lực</th><th class="px-5 py-3">Mã hồ sơ</th><th class="px-5 py-3">Trạng thái</th><th class="px-5 py-3">Minh chứng</th><th class="px-5 py-3 text-right">Chi tiết</th></tr></thead><tbody class="divide-y divide-slate-100">
            @foreach($residenceHistory as $history)
                <tr class="{{ $history->is($temporaryResidence) ? 'bg-indigo-50/50' : '' }}"><td class="whitespace-nowrap px-5 py-3">{{ $history->start_date?->format('d/m/Y') }} → {{ $history->end_date?->format('d/m/Y') ?? 'Không thời hạn' }}</td><td class="px-5 py-3">{{ $history->reference_number ?: '—' }}</td><td class="px-5 py-3 font-semibold">{{ $history->status_label }}</td><td class="px-5 py-3">{{ $history->evidence_path ? 'Đã có' : 'Chưa có' }}</td><td class="px-5 py-3 text-right">@if(! $history->is($temporaryResidence))<a href="{{ route('admin.temporary_residences.show', $history) }}" class="font-semibold text-indigo-700">Xem</a>@else<span class="text-xs text-slate-400">Đang xem</span>@endif</td></tr>
            @endforeach
        </tbody></table></div>
    </section>

    @if($temporaryResidence->status === 'cancelled')
        <section class="rounded-xl border border-rose-200 bg-rose-50 p-5 text-rose-900"><h3 class="font-bold">Giấy đã bị hủy</h3><p class="mt-2 text-sm">{{ $temporaryResidence->cancellation_reason }}</p><p class="mt-1 text-xs">{{ $temporaryResidence->cancelledBy?->name }} · {{ $temporaryResidence->cancelled_at?->format('H:i d/m/Y') }}</p></section>
    @elseif(! $temporaryResidence->signed_at)
        <form method="POST" action="{{ route('admin.temporary_residences.cancel', $temporaryResidence) }}" class="rounded-xl border border-rose-200 bg-white p-5">@csrf @method('PATCH')<label class="text-sm font-semibold text-rose-900">Lý do hủy giấy tạm trú<textarea name="cancellation_reason" required minlength="10" maxlength="2000" rows="2" class="mt-2 w-full rounded-lg border border-rose-200 px-3 py-2 font-normal" placeholder="Nhập ít nhất 10 ký tự"></textarea></label><button class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-700">Hủy giấy tạm trú</button></form>
    @endif
</div>
@endsection
