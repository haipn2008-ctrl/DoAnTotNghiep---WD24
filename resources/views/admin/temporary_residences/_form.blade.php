@php($temporaryResidence = $temporaryResidence ?? null)
@php($editing = $temporaryResidence !== null)

@if($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
        <p class="font-bold">Vui lòng kiểm tra lại thông tin:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@if($editing)
    <div class="grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-3">
        <div><p class="text-xs text-slate-500">Người thuê</p><p class="mt-1 font-bold text-slate-950">{{ $temporaryResidence->contractTenant?->full_name ?? $temporaryResidence->tenant?->full_name }}</p></div>
        <div><p class="text-xs text-slate-500">CCCD</p><p class="mt-1 font-semibold text-slate-950">{{ $temporaryResidence->contractTenant?->identity_number ?? $temporaryResidence->tenant?->cccd ?? '—' }}</p></div>
        <div><p class="text-xs text-slate-500">Phòng</p><p class="mt-1 font-semibold text-slate-950">{{ $temporaryResidence->room?->room_code ?? $temporaryResidence->contract?->room?->room_code ?? '—' }}</p></div>
    </div>
@else
    <label class="block text-sm font-semibold text-slate-700">Người thuê đang ở <span class="text-rose-600">*</span>
        <select name="contract_tenant_id" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 font-normal outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            <option value="">Chọn người thuê</option>
            @foreach($members as $member)
                <option value="{{ $member->id }}" @selected((string) old('contract_tenant_id', request('member')) === (string) $member->id)>
                    {{ $member->full_name }} · CCCD {{ $member->identity_number }} · Phòng {{ $member->contract?->room?->room_code }}
                </option>
            @endforeach
        </select>
    </label>
    @if($members->isEmpty())
        <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Tất cả người đang thuê đã có giấy tạm trú còn hiệu lực, hoặc hiện chưa có người nào được xác nhận đang ở.</p>
    @endif
@endif

<div class="grid gap-4 sm:grid-cols-2">
    <label class="text-sm font-semibold text-slate-700">Có hiệu lực từ <span class="text-rose-600">*</span>
        <input type="date" name="start_date" required value="{{ old('start_date', $temporaryResidence?->start_date?->toDateString() ?? today()->toDateString()) }}" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 font-normal outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
    </label>
    <label class="text-sm font-semibold text-slate-700">Có hiệu lực đến
        <input type="date" name="end_date" value="{{ old('end_date', $temporaryResidence?->end_date?->toDateString()) }}" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 font-normal outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
    </label>
</div>

<label class="block text-sm font-semibold text-slate-700">Mã hồ sơ/Mã biên nhận
    <input name="reference_number" maxlength="100" value="{{ old('reference_number', $temporaryResidence?->reference_number) }}" placeholder="Nhập nếu có" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 font-normal outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
</label>

<label class="block text-sm font-semibold text-slate-700">Ảnh hoặc PDF minh chứng @unless($editing)<span class="text-rose-600">*</span>@endunless
    <input type="file" name="evidence" accept="image/jpeg,image/png,image/webp,application/pdf" @required(! $editing) class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-normal file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700">
    <span class="mt-1 block text-xs font-normal text-slate-500">JPG, PNG, WEBP hoặc PDF; tối đa 5 MB. Tệp được lưu riêng tư.</span>
    @if($editing && $temporaryResidence->evidenceExists())
        <a href="{{ route('admin.temporary_residences.evidence', $temporaryResidence) }}" data-image-modal data-media-type="{{ $temporaryResidence->evidenceIsPdf() ? 'pdf' : 'image' }}" data-image-title="Minh chứng giấy tạm trú {{ $temporaryResidence->reference_number ?: '#'.$temporaryResidence->id }}" class="mt-2 inline-flex text-sm font-semibold text-indigo-700">Xem minh chứng hiện tại</a>
    @elseif($editing && $temporaryResidence->evidence_path)
        <span class="mt-2 block text-sm font-semibold text-amber-700">Tệp minh chứng hiện tại không còn tồn tại.</span>
    @endif
</label>

<label class="block text-sm font-semibold text-slate-700">Ghi chú
    <textarea name="note" rows="4" maxlength="1000" placeholder="Thông tin cần lưu ý khi kiểm tra giấy tạm trú" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 font-normal outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('note', $temporaryResidence?->note) }}</textarea>
</label>

<div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-5">
    <a href="{{ route('admin.temporary_residences.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Quay lại</a>
    <button @disabled(! $editing && $members->isEmpty()) class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
        {{ $editing ? 'Lưu cập nhật' : 'Cập nhật giấy tạm trú' }}
    </button>
</div>
