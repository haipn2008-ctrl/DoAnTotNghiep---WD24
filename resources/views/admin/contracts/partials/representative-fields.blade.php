@php
    $selectedTenantId = old('tenant_id', isset($contract) ? $contract->tenant_id : null);
    $selectedTenant = $tenants->firstWhere('id', (int) $selectedTenantId);
    $profileDefaults = $selectedTenant ? [
        'full_name' => $selectedTenant->full_name,
        'date_of_birth' => $selectedTenant->date_of_birth?->toDateString(),
        'gender' => $selectedTenant->gender,
        'cccd' => $selectedTenant->cccd,
        'phone' => $selectedTenant->phone,
        'address' => $selectedTenant->address,
    ] : [];
    $profile = old('representative', $profileDefaults);
    $representativeMember = isset($contract) && (int) $contract->tenant_id === (int) $selectedTenantId
        ? $contract->representativeMember
        : null;
@endphp

<section data-representative-profile class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
    <h4 class="font-semibold text-slate-950">Thông tin pháp lý người đại diện</h4>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div><label class="mb-1 block text-sm font-semibold">Họ và tên *</label><input data-representative-field="full_name" name="representative[full_name]" value="{{ $profile['full_name'] ?? '' }}" required maxlength="255" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm"></div>
        <div><label class="mb-1 block text-sm font-semibold">Số điện thoại *</label><input data-representative-field="phone" name="representative[phone]" value="{{ $profile['phone'] ?? '' }}" required inputmode="numeric" maxlength="15" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm"></div>
        <div><label class="mb-1 block text-sm font-semibold">Ngày sinh *</label><input data-representative-field="date_of_birth" type="date" name="representative[date_of_birth]" value="{{ $profile['date_of_birth'] ?? '' }}" max="{{ now()->subYears(18)->toDateString() }}" required class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm"></div>
        <div><label class="mb-1 block text-sm font-semibold">Giới tính</label><select data-representative-field="gender" name="representative[gender]" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm"><option value="">Chưa cập nhật</option><option value="male" @selected(($profile['gender'] ?? '') === 'male')>Nam</option><option value="female" @selected(($profile['gender'] ?? '') === 'female')>Nữ</option><option value="other" @selected(($profile['gender'] ?? '') === 'other')>Khác</option></select></div>
        <div><label class="mb-1 block text-sm font-semibold">CCCD *</label><input data-representative-field="cccd" name="representative[cccd]" value="{{ $profile['cccd'] ?? '' }}" required inputmode="numeric" minlength="12" maxlength="12" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm"></div>
        <div class="md:col-span-2"><label class="mb-1 block text-sm font-semibold">Địa chỉ thường trú</label><textarea data-representative-field="address" name="representative[address]" rows="2" maxlength="500" class="w-full rounded-lg border border-slate-200 p-3 text-sm">{{ $profile['address'] ?? '' }}</textarea></div>

        @foreach(['front' => 'Ảnh mặt trước CCCD', 'back' => 'Ảnh mặt sau CCCD'] as $side => $label)
            @php
                $memberPath = $side === 'front' ? $representativeMember?->identity_front_path : $representativeMember?->identity_back_path;
                $profilePath = $selectedTenant?->document?->hasImage($side) ? $selectedTenant->document->imagePath($side) : null;
                $imageUrl = $memberPath && $representativeMember
                    ? route('admin.contract-tenants.identity-document', [$representativeMember, $side])
                    : ($profilePath ? route('admin.tenants.identity-document', [$selectedTenant, $side]) : null);
                $previewId = 'representative-identity-'.$side.'-preview';
            @endphp
            <div data-representative-identity="{{ $side }}" class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <label class="text-sm font-semibold">{{ $label }} <span data-identity-required-mark class="{{ $imageUrl ? 'hidden' : '' }}">*</span></label>
                    <div class="flex items-center gap-2">
                        <span data-profile-image-badge class="{{ $imageUrl ? '' : 'hidden' }} rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Đã lưu</span>
                        <a data-profile-image-link data-image-modal data-image-title="{{ $label }}" href="{{ $imageUrl ?: '#' }}" class="{{ $imageUrl ? '' : 'hidden' }} text-xs font-semibold text-indigo-700">Xem ảnh</a>
                    </div>
                </div>
                <div class="mb-3 flex h-36 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50">
                    <img id="{{ $previewId }}" @if($imageUrl) src="{{ $imageUrl }}" data-original-src="{{ $imageUrl }}" @endif alt="Xem trước {{ mb_strtolower($label) }}" class="{{ $imageUrl ? '' : 'hidden' }} h-full w-full object-contain">
                    <div data-identity-preview-empty class="{{ $imageUrl ? 'hidden' : '' }} px-4 text-center text-xs text-slate-400"><i class="bx bx-image-add mb-1 block text-3xl"></i>Chưa có ảnh trong hồ sơ khách</div>
                </div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-600">{{ $imageUrl ? 'Thay ảnh' : 'Tải ảnh lên' }}</label>
                <input data-identity-preview-input data-preview-target="{{ $previewId }}" type="file" name="representative[identity_{{ $side }}]" accept="image/jpeg,image/png,image/webp" @required(! $imageUrl) class="block w-full rounded-lg border border-slate-200 bg-white text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2.5">
            </div>
        @endforeach
    </div>
</section>
