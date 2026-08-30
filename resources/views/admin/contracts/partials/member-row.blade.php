@php
    $errorPrefix = "members.{$index}";
    $frontPreviewId = "member-{$index}-identity-front-preview";
    $backPreviewId = "member-{$index}-identity-back-preview";
@endphp

<div data-member-row class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <input type="hidden" name="members[{{ $index }}][id]" value="{{ $member['id'] ?? '' }}">

    <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
        <div>
            <p class="text-sm font-bold text-slate-900">Hồ sơ người thuê</p>
            <p class="mt-0.5 text-xs text-slate-500">Chỉ họ tên và giới tính là bắt buộc. Các thông tin còn lại có thể bổ sung sau.</p>
        </div>
        <button type="button" data-remove-member class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 hover:text-rose-800">
            <i class="bx bx-trash text-base"></i>
            Gỡ khỏi danh sách
        </button>
    </div>

    <div class="space-y-5 p-4">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Họ và tên *</label>
                <input name="members[{{ $index }}][full_name]" value="{{ $member['full_name'] ?? '' }}" required maxlength="150" class="h-10 w-full rounded-lg border px-3 text-sm outline-none transition focus:ring-4 {{ $errors->has("{$errorPrefix}.full_name") ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100' : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100' }}">
                @error("{$errorPrefix}.full_name")<p data-validation-error-for="members[{{ $index }}][full_name]" class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Ngày sinh</label>
                <input type="date" name="members[{{ $index }}][date_of_birth]" value="{{ $member['date_of_birth'] ?? '' }}" max="{{ now()->subYears(18)->toDateString() }}" class="h-10 w-full rounded-lg border px-3 text-sm outline-none transition focus:ring-4 {{ $errors->has("{$errorPrefix}.date_of_birth") ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100' : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100' }}">
                @error("{$errorPrefix}.date_of_birth")<p data-validation-error-for="members[{{ $index }}][date_of_birth]" class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Giới tính *</label>
                <select name="members[{{ $index }}][gender]" required class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Chọn giới tính</option>
                    <option value="male" @selected(($member['gender'] ?? '') === 'male')>Nam</option>
                    <option value="female" @selected(($member['gender'] ?? '') === 'female')>Nữ</option>
                    <option value="other" @selected(($member['gender'] ?? '') === 'other')>Khác</option>
                </select>
                @error("{$errorPrefix}.gender")<p data-validation-error-for="members[{{ $index }}][gender]" class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-700">CCCD</label>
                <input name="members[{{ $index }}][identity_number]" value="{{ $member['identity_number'] ?? '' }}" inputmode="numeric" minlength="12" maxlength="12" placeholder="Nhập đúng 12 chữ số" class="h-10 w-full rounded-lg border px-3 text-sm outline-none transition focus:ring-4 {{ $errors->has("{$errorPrefix}.identity_number") ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100' : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100' }}">
                @error("{$errorPrefix}.identity_number")<p data-validation-error-for="members[{{ $index }}][identity_number]" class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Số điện thoại</label>
                <input name="members[{{ $index }}][phone]" value="{{ $member['phone'] ?? '' }}" minlength="10" maxlength="15" inputmode="tel" placeholder="Nhập từ 10 đến 15 chữ số" class="h-10 w-full rounded-lg border px-3 text-sm outline-none transition focus:ring-4 {{ $errors->has("{$errorPrefix}.phone") ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-100' : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-100' }}">
                @error("{$errorPrefix}.phone")<p data-validation-error-for="members[{{ $index }}][phone]" class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Ngày cấp CCCD</label>
                <input type="date" name="members[{{ $index }}][cccd_issue_date]" value="{{ $member['cccd_issue_date'] ?? '' }}" max="{{ today()->toDateString() }}" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                @error("{$errorPrefix}.cccd_issue_date")<p data-validation-error-for="members[{{ $index }}][cccd_issue_date]" class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Nơi cấp CCCD</label>
                <input name="members[{{ $index }}][cccd_issue_place]" value="{{ $member['cccd_issue_place'] ?? '' }}" maxlength="255" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                @error("{$errorPrefix}.cccd_issue_place")<p data-validation-error-for="members[{{ $index }}][cccd_issue_place]" class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Email</label>
                <input type="email" name="members[{{ $index }}][email]" value="{{ $member['email'] ?? '' }}" maxlength="255" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                @error("{$errorPrefix}.email")<p data-validation-error-for="members[{{ $index }}][email]" class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Địa chỉ thường trú</label>
                <input name="members[{{ $index }}][address]" value="{{ $member['address'] ?? '' }}" maxlength="500" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                @error("{$errorPrefix}.address")<p data-validation-error-for="members[{{ $index }}][address]" class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="border-t border-slate-100 pt-4">
            <div class="mb-3"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Ảnh căn cước</p></div>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach([
                    ['side' => 'front', 'label' => 'Mặt trước CCCD', 'previewId' => $frontPreviewId, 'path' => $member['identity_front_path'] ?? null],
                    ['side' => 'back', 'label' => 'Mặt sau CCCD', 'previewId' => $backPreviewId, 'path' => $member['identity_back_path'] ?? null],
                ] as $identityImage)
                    @php($imageErrorKey = "{$errorPrefix}.identity_{$identityImage['side']}")
                    <div class="rounded-xl border p-3 {{ $errors->has($imageErrorKey) ? 'border-rose-500 bg-rose-50' : 'border-slate-200 bg-slate-50' }}">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <label class="text-xs font-semibold text-slate-700">{{ $identityImage['label'] }}</label>
                            @if($identityImage['path'] && !empty($member['id']))
                                <a data-image-modal data-image-title="{{ $identityImage['label'] }}" href="{{ route('admin.contract-tenants.identity-document', [$member['id'], $identityImage['side']]) }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">Xem ảnh gốc</a>
                            @endif
                        </div>
                        <div class="mb-3 flex h-32 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-white">
                            <img id="{{ $identityImage['previewId'] }}" data-identity-preview-image @if($identityImage['path'] && !empty($member['id'])) src="{{ route('admin.contract-tenants.identity-document', [$member['id'], $identityImage['side']]) }}" data-original-src="{{ route('admin.contract-tenants.identity-document', [$member['id'], $identityImage['side']]) }}" @endif alt="Xem trước {{ mb_strtolower($identityImage['label']) }}" class="{{ $identityImage['path'] && !empty($member['id']) ? '' : 'hidden' }} h-full w-full object-contain">
                            <div data-identity-preview-empty class="{{ $identityImage['path'] && !empty($member['id']) ? 'hidden' : '' }} px-4 text-center text-xs text-slate-400">
                                <i class="bx bx-image-add mb-1 block text-3xl"></i>
                                Ảnh xem trước sẽ hiện tại đây
                            </div>
                        </div>
                        <input data-identity-preview-input data-preview-target="{{ $identityImage['previewId'] }}" type="file" name="members[{{ $index }}][identity_{{ $identityImage['side'] }}]" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border bg-white text-xs file:mr-2 file:border-0 file:bg-slate-100 file:px-3 file:py-2 {{ $errors->has($imageErrorKey) ? 'border-rose-500' : 'border-slate-200' }}">
                        @error($imageErrorKey)<p data-validation-error-for="members[{{ $index }}][identity_{{ $identityImage['side'] }}]" class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
