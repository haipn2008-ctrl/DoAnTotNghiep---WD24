@extends('layouts.client.index')

@section('title', 'Tài khoản | Cổng khách thuê')
@section('page_title', 'Tài khoản')

@section('content')
    @php($tenant = $user->tenant)
    @php($identityDocument = $tenant?->document)
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 px-6 py-7 text-white shadow-lg shadow-indigo-200/60 sm:px-8"><div class="absolute -right-12 -top-16 h-52 w-52 rounded-full bg-white/10"></div><div class="relative flex items-center gap-4"><span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0" /></svg></span><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-indigo-100">Hồ sơ khách thuê</p><h2 class="mt-1 text-2xl font-bold sm:text-3xl">Thông tin cá nhân của tôi</h2><p class="mt-2 text-sm text-indigo-100">Quản lý thông tin định danh, liên hệ và bảo mật tài khoản.</p></div></div></section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Giấy tạm trú của tôi</h3>
            </div>

            @if($tenant?->temporaryResidences->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Mã hồ sơ</th>
                                <th class="px-5 py-3">Phòng</th>
                                <th class="px-5 py-3">Thời hạn</th>
                                <th class="px-5 py-3">Trạng thái</th>
                                <th class="px-5 py-3 text-right">Minh chứng</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($tenant->temporaryResidences as $temporaryResidence)
                                @php($statusClass = match($temporaryResidence->status) {
                                    'active' => 'bg-emerald-50 text-emerald-700',
                                    'pending' => 'bg-amber-50 text-amber-700',
                                    'expired' => 'bg-slate-100 text-slate-700',
                                    'cancelled' => 'bg-rose-50 text-rose-700',
                                    default => 'bg-slate-100 text-slate-700',
                                })
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900">{{ $temporaryResidence->reference_number ?: '#'.$temporaryResidence->id }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-700">{{ $temporaryResidence->contract?->room?->room_code ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-700">
                                        {{ $temporaryResidence->start_date?->format('d/m/Y') ?? '—' }}
                                        → {{ $temporaryResidence->end_date?->format('d/m/Y') ?? 'Không thời hạn' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $temporaryResidence->status_label }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        @if(in_array($temporaryResidence->id, $availableResidenceEvidenceIds, true))
                                            @php($isPdfEvidence = $temporaryResidence->evidence_mime_type === 'application/pdf' || strtolower(pathinfo($temporaryResidence->evidence_original_name ?: $temporaryResidence->evidence_path, PATHINFO_EXTENSION)) === 'pdf')
                                            <a href="{{ route('client.account.temporary-residences.evidence', $temporaryResidence) }}" data-image-modal data-media-type="{{ $isPdfEvidence ? 'pdf' : 'image' }}" data-image-title="Giấy tạm trú {{ $temporaryResidence->reference_number ?: '#'.$temporaryResidence->id }}" class="font-semibold text-indigo-700 hover:text-indigo-900">Xem giấy</a>
                                        @elseif($temporaryResidence->evidence_path)
                                            <span class="text-amber-700">Tệp không còn tồn tại</span>
                                        @else
                                            <span class="text-slate-400">Chưa có</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-5 py-8 text-center text-sm text-slate-500">Bạn chưa có giấy tạm trú được cập nhật trên hệ thống.</div>
            @endif
        </section>

        @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(20rem,1fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h3 class="font-semibold text-slate-950">Thông tin hồ sơ</h3>
                <form method="POST" action="{{ route('client.account.update') }}" enctype="multipart/form-data" class="mt-5 space-y-5">@csrf @method('PUT')
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Họ và tên</label><input name="name" value="{{ old('name', $tenant?->full_name ?: $user->name) }}" required maxlength="255" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày sinh</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $tenant?->date_of_birth?->format('Y-m-d')) }}" max="{{ now()->subYears(18)->toDateString() }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Giới tính</label><select name="gender" required class="h-11 w-full rounded-lg border border-slate-200 px-3"><option value="male" @selected(old('gender', $tenant?->gender) === 'male')>Nam</option><option value="female" @selected(old('gender', $tenant?->gender) === 'female')>Nữ</option><option value="other" @selected(old('gender', $tenant?->gender) === 'other')>Khác</option></select></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Số CCCD</label><input name="cccd" inputmode="numeric" value="{{ old('cccd', $tenant?->cccd) }}" required minlength="12" maxlength="12" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày cấp CCCD</label><input type="date" name="cccd_issue_date" value="{{ old('cccd_issue_date', $tenant?->cccd_issue_date?->format('Y-m-d')) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div class="md:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nơi cấp CCCD</label><input name="cccd_issue_place" value="{{ old('cccd_issue_place', $tenant?->cccd_issue_place) }}" required maxlength="255" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div class="md:col-span-2">
                            <div class="mb-3">
                                <p class="text-sm font-semibold text-slate-700">Ảnh căn cước công dân</p>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                @php($frontImageUrl = ($identityDocument?->hasImage('front') ?? false) ? route('client.account.identity-document', 'front') : null)
                                @php($backImageUrl = ($identityDocument?->hasImage('back') ?? false) ? route('client.account.identity-document', 'back') : null)
                                @foreach([
                                    ['side' => 'front', 'label' => 'Mặt trước CCCD', 'url' => $frontImageUrl],
                                    ['side' => 'back', 'label' => 'Mặt sau CCCD', 'url' => $backImageUrl],
                                ] as $identitySide)
                                    @php($side = $identitySide['side'])
                                    @php($label = $identitySide['label'])
                                    @php($imageUrl = $identitySide['url'])
                                    @php($previewId = 'account-identity-'.$side.'-preview')
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        <div class="mb-2 flex items-center justify-between gap-2">
                                            <label class="text-sm font-semibold text-slate-700">{{ $label }}</label>
                                            @if($imageUrl)<a href="{{ $imageUrl }}" data-image-modal data-image-title="{{ $label }}" class="text-xs font-semibold text-indigo-700">Xem ảnh</a>@endif
                                        </div>
                                        <div class="mb-3 flex h-36 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-white">
                                            <img id="{{ $previewId }}" @if($imageUrl) src="{{ $imageUrl }}" data-original-src="{{ $imageUrl }}" @endif alt="{{ $label }}" class="{{ $imageUrl ? '' : 'hidden' }} h-full w-full object-contain">
                                            <div data-identity-preview-empty class="{{ $imageUrl ? 'hidden' : '' }} px-4 text-center text-xs text-slate-400">Chưa tải ảnh</div>
                                        </div>
                                        <input data-identity-preview-input data-preview-target="{{ $previewId }}" type="file" name="identity_{{ $side }}" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-slate-200 bg-white text-xs file:mr-2 file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Email đăng nhập</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Số điện thoại</label><input name="phone" inputmode="tel" value="{{ old('phone', $user->phone ?: $tenant?->phone) }}" required maxlength="15" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div class="md:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Địa chỉ thường trú</label><textarea name="address" required maxlength="500" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2">{{ old('address', $tenant?->address) }}</textarea></div>
                    </div>
                    <button class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h12l2 2v14H5V4Zm3 0v6h8V4M8 20v-6h8v6" /></svg>Lưu hồ sơ</button>
                </form>
            </section>

            <section class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h3 class="font-semibold text-slate-950">Đổi mật khẩu</h3>
                <p class="mt-1 text-sm text-slate-500">Mật khẩu mới phải khác mật khẩu hiện tại.</p>
                <form method="POST" action="{{ route('client.account.password.update') }}" class="mt-5 space-y-4">@csrf @method('PUT')
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Mật khẩu hiện tại</label><input type="password" name="current_password" required autocomplete="current-password" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Mật khẩu mới</label><input type="password" name="password" required minlength="8" autocomplete="new-password" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nhập lại mật khẩu mới</label><input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <button class="h-12 w-full rounded-xl bg-slate-900 px-4 text-sm font-bold text-white hover:bg-slate-800">Đổi mật khẩu</button>
                </form>
            </section>
        </div>
    </div>
@endsection
