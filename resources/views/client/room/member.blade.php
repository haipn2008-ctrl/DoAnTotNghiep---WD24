@extends('layouts.client.index')

@section('title', 'Thông tin thành viên | Cổng khách thuê')
@section('page_title', 'Thành viên trong phòng')

@php
    $tenant = $member->tenant;
    $isRepresentative = $member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE;
    $identitySides = [
        ['side' => 'front', 'label' => 'Mặt trước CCCD', 'path' => $member->identity_front_path],
        ['side' => 'back', 'label' => 'Mặt sau CCCD', 'path' => $member->identity_back_path],
    ];
    $fieldClass = 'h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none';
@endphp

@section('content')
    <div class="space-y-5">
        <a href="{{ route('client.room.members.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-700 hover:text-indigo-900">
            <span aria-hidden="true">←</span> Quay lại danh sách thành viên
        </a>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-slate-950">Thông tin hồ sơ</h2>
                    <p class="mt-1 text-xs text-slate-500">Thông tin thành viên đang ở phòng {{ $room->room_code }}.</p>
                </div>
                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $member->role_label }}</span>
            </div>

            @if($errors->any())
                <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                    <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('client.room.members.update', $member) }}" enctype="multipart/form-data" class="mt-5 grid gap-4 md:grid-cols-2">
                @csrf
                @method('PUT')
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Họ và tên</label>
                    <input name="full_name" value="{{ old('full_name', $member->full_name) }}" required maxlength="255" class="{{ $fieldClass }}">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày sinh</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $member->date_of_birth?->format('Y-m-d')) }}" max="{{ now()->subYears(18)->toDateString() }}" required class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Giới tính</label>
                    <select name="gender" required class="{{ $fieldClass }}">
                        <option value="">Chọn giới tính</option>
                        <option value="male" @selected(old('gender', $tenant?->gender) === 'male')>Nam</option>
                        <option value="female" @selected(old('gender', $tenant?->gender) === 'female')>Nữ</option>
                        <option value="other" @selected(old('gender', $tenant?->gender) === 'other')>Khác</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Số CCCD</label>
                    <input name="identity_number" inputmode="numeric" value="{{ old('identity_number', $member->identity_number) }}" required minlength="12" maxlength="12" class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày cấp CCCD</label>
                    <input type="date" name="cccd_issue_date" value="{{ old('cccd_issue_date', $tenant?->cccd_issue_date?->format('Y-m-d')) }}" max="{{ today()->toDateString() }}" required class="{{ $fieldClass }}">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nơi cấp CCCD</label>
                    <input name="cccd_issue_place" value="{{ old('cccd_issue_place', $tenant?->cccd_issue_place) }}" required maxlength="255" class="{{ $fieldClass }}">
                </div>

                <div class="md:col-span-2">
                    <div class="mb-3">
                        <p class="text-sm font-semibold text-slate-700">Ảnh căn cước công dân</p>
                        <p class="mt-1 text-xs text-slate-500">Ảnh giấy tờ đã lưu trong hồ sơ hợp đồng của thành viên.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach($identitySides as $identitySide)
                            @php($imageUrl = $identitySide['path'] ? route('client.room.members.identity', [$member, $identitySide['side']]) : null)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-700">{{ $identitySide['label'] }}</p>
                                    @if($imageUrl)
                                        <a href="{{ $imageUrl }}" data-image-modal data-image-title="{{ $identitySide['label'] }} - {{ $member->full_name }}" class="text-xs font-semibold text-indigo-700">Xem ảnh</a>
                                    @endif
                                </div>
                                <div class="flex h-36 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-white">
                                    @if($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $identitySide['label'] }}" class="h-full w-full object-contain">
                                    @else
                                        <span class="px-4 text-center text-xs text-slate-400">Chưa tải ảnh</span>
                                    @endif
                                </div>
                                <input type="file" name="identity_{{ $identitySide['side'] }}" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full rounded-lg border border-slate-200 bg-white text-xs file:mr-2 file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700">
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($isRepresentative)
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Email đăng nhập</label>
                        <input type="email" name="email" value="{{ old('email', $tenant?->user?->email ?: $tenant?->email) }}" required maxlength="255" class="{{ $fieldClass }}">
                    </div>
                @endif
                <div class="{{ $isRepresentative ? '' : 'md:col-span-2' }}">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Số điện thoại</label>
                    <input name="phone" inputmode="tel" value="{{ old('phone', $member->phone) }}" required maxlength="15" class="{{ $fieldClass }}">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Địa chỉ thường trú</label>
                    <textarea name="address" required maxlength="500" rows="3" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none">{{ old('address', $member->address) }}</textarea>
                </div>

                <div class="flex justify-end md:col-span-2">
                    <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Lưu thay đổi</button>
                </div>
            </form>
        </section>
    </div>
@endsection
