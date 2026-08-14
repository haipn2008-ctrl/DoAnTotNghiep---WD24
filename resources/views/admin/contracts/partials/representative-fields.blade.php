@php
    $profileDefaults = isset($contract) ? [
        'full_name' => $contract->tenant?->full_name,
        'date_of_birth' => $contract->tenant?->date_of_birth?->toDateString(),
        'gender' => $contract->tenant?->gender,
        'cccd' => $contract->tenant?->cccd,
        'phone' => $contract->tenant?->phone,
        'address' => $contract->tenant?->address,
    ] : [];
    $profile = old('representative', $profileDefaults);
    $representativeOccupant = $contract->representativeOccupant ?? null;
@endphp

<section data-representative-profile class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
    <h4 class="font-semibold text-slate-950">Thông tin pháp lý người đại diện</h4>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div><label class="mb-1 block text-sm font-semibold">Họ và tên *</label><input data-representative-field="full_name" name="representative[full_name]" value="{{ $profile['full_name'] ?? '' }}" required maxlength="255" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm"></div>
        <div><label class="mb-1 block text-sm font-semibold">Số điện thoại *</label><input data-representative-field="phone" name="representative[phone]" value="{{ $profile['phone'] ?? '' }}" required inputmode="numeric" maxlength="15" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm"></div>
        <div><label class="mb-1 block text-sm font-semibold">Ngày sinh</label><input data-representative-field="date_of_birth" type="date" name="representative[date_of_birth]" value="{{ $profile['date_of_birth'] ?? '' }}" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm"></div>
        <div><label class="mb-1 block text-sm font-semibold">Giới tính</label><select data-representative-field="gender" name="representative[gender]" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm"><option value="">Chưa cập nhật</option><option value="male" @selected(($profile['gender'] ?? '') === 'male')>Nam</option><option value="female" @selected(($profile['gender'] ?? '') === 'female')>Nữ</option><option value="other" @selected(($profile['gender'] ?? '') === 'other')>Khác</option></select></div>
        <div><label class="mb-1 block text-sm font-semibold">CCCD *</label><input data-representative-field="cccd" name="representative[cccd]" value="{{ $profile['cccd'] ?? '' }}" required inputmode="numeric" minlength="12" maxlength="12" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm"></div>
        <div class="md:col-span-2"><label class="mb-1 block text-sm font-semibold">Địa chỉ thường trú</label><textarea data-representative-field="address" name="representative[address]" rows="2" maxlength="500" class="w-full rounded-lg border border-slate-200 p-3 text-sm">{{ $profile['address'] ?? '' }}</textarea></div>
        @foreach([
            ['side' => 'front', 'label' => 'Ảnh mặt trước CCCD', 'path' => $representativeOccupant?->identity_front_path],
            ['side' => 'back', 'label' => 'Ảnh mặt sau CCCD', 'path' => $representativeOccupant?->identity_back_path],
        ] as $identityImage)
            @php
                $previewId = 'representative-identity-'.$identityImage['side'].'-preview';
                $imageUrl = $identityImage['path'] && $representativeOccupant
                    ? route('admin.contract-occupants.identity-document', [$representativeOccupant, $identityImage['side']])
                    : null;
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="mb-2 flex items-center justify-between gap-2"><label class="text-sm font-semibold">{{ $identityImage['label'] }} *</label>@if($imageUrl)<a target="_blank" href="{{ $imageUrl }}" class="text-xs font-semibold text-indigo-700">Xem ảnh gốc</a>@endif</div>
                <div class="mb-3 flex h-36 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50">
                    <img id="{{ $previewId }}" @if($imageUrl) src="{{ $imageUrl }}" data-original-src="{{ $imageUrl }}" @endif alt="Xem trước {{ mb_strtolower($identityImage['label']) }}" class="{{ $imageUrl ? '' : 'hidden' }} h-full w-full object-contain">
                    <div data-identity-preview-empty class="{{ $imageUrl ? 'hidden' : '' }} px-4 text-center text-xs text-slate-400"><i class="bx bx-image-add mb-1 block text-3xl"></i>Ảnh được chọn sẽ hiện tại đây</div>
                </div>
                <input data-identity-preview-input data-preview-target="{{ $previewId }}" type="file" name="representative[identity_{{ $identityImage['side'] }}]" accept="image/jpeg,image/png,image/webp" @required(!$representativeOccupant?->identity_front_path || !$representativeOccupant?->identity_back_path) class="block w-full rounded-lg border border-slate-200 bg-white text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2.5">
            </div>
        @endforeach
        <label class="flex items-center gap-2 rounded-lg border border-indigo-200 bg-white p-3 text-sm md:col-span-2"><input data-representative-resident type="checkbox" name="representative_is_occupant" value="1" @checked(old('representative_is_occupant', $contract->representative_is_occupant ?? false))><strong>Người đại diện cũng trực tiếp ở tại phòng</strong></label>
    </div>
</section>
