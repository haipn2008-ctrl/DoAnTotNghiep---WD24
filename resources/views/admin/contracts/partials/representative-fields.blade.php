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
        <div><label class="mb-1 block text-sm font-semibold">Ảnh mặt trước CCCD *</label><input type="file" name="representative[identity_front]" accept="image/jpeg,image/png,image/webp" @required(!isset($contract) || !$contract->representativeOccupant?->identity_front_path || !$contract->representativeOccupant?->identity_back_path) class="block w-full rounded-lg border border-slate-200 bg-white text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2.5">@if(isset($contract) && $contract->representativeOccupant?->identity_front_path)<a target="_blank" href="{{ route('admin.contract-occupants.identity-document', [$contract->representativeOccupant, 'front']) }}" class="mt-1 inline-block text-xs font-semibold text-indigo-700">Xem ảnh hiện tại</a>@endif</div>
        <div><label class="mb-1 block text-sm font-semibold">Ảnh mặt sau CCCD *</label><input type="file" name="representative[identity_back]" accept="image/jpeg,image/png,image/webp" @required(!isset($contract) || !$contract->representativeOccupant?->identity_front_path || !$contract->representativeOccupant?->identity_back_path) class="block w-full rounded-lg border border-slate-200 bg-white text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2.5">@if(isset($contract) && $contract->representativeOccupant?->identity_back_path)<a target="_blank" href="{{ route('admin.contract-occupants.identity-document', [$contract->representativeOccupant, 'back']) }}" class="mt-1 inline-block text-xs font-semibold text-indigo-700">Xem ảnh hiện tại</a>@endif</div>
        <label class="flex items-center gap-2 rounded-lg border border-indigo-200 bg-white p-3 text-sm md:col-span-2"><input data-representative-resident type="checkbox" name="representative_is_occupant" value="1" @checked(old('representative_is_occupant', $contract->representative_is_occupant ?? false))><strong>Người đại diện cũng trực tiếp ở tại phòng</strong></label>
    </div>
</section>
