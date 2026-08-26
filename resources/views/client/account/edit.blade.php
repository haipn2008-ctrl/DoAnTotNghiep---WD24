@extends('layouts.client.index')

@section('title', 'Tài khoản | Cổng khách thuê')
@section('page_title', 'Tài khoản')

@section('content')
    @php($tenant = $user->tenant)
    <div class="space-y-5">
        <div>
            <p class="text-sm font-medium text-slate-500">Hồ sơ khách thuê</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Thông tin cá nhân của tôi</h2>
            <p class="mt-2 text-sm text-slate-500">Bạn có thể chủ động cập nhật thông tin. Các thay đổi sẽ được dùng cho hồ sơ và hợp đồng lập sau thời điểm cập nhật.</p>
        </div>

        @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(20rem,1fr)]">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Thông tin hồ sơ</h3>
                <form method="POST" action="{{ route('client.account.update') }}" class="mt-5 space-y-5">@csrf @method('PUT')
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Họ và tên</label><input name="name" value="{{ old('name', $tenant?->full_name ?: $user->name) }}" required maxlength="255" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày sinh</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $tenant?->date_of_birth?->format('Y-m-d')) }}" max="{{ now()->subYears(18)->toDateString() }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Giới tính</label><select name="gender" required class="h-11 w-full rounded-lg border border-slate-200 px-3"><option value="male" @selected(old('gender', $tenant?->gender) === 'male')>Nam</option><option value="female" @selected(old('gender', $tenant?->gender) === 'female')>Nữ</option><option value="other" @selected(old('gender', $tenant?->gender) === 'other')>Khác</option></select></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Số CCCD</label><input name="cccd" inputmode="numeric" value="{{ old('cccd', $tenant?->cccd) }}" required minlength="12" maxlength="12" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày cấp CCCD</label><input type="date" name="cccd_issue_date" value="{{ old('cccd_issue_date', $tenant?->cccd_issue_date?->format('Y-m-d')) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div class="md:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nơi cấp CCCD</label><input name="cccd_issue_place" value="{{ old('cccd_issue_place', $tenant?->cccd_issue_place) }}" required maxlength="255" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Email đăng nhập</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Số điện thoại</label><input name="phone" inputmode="tel" value="{{ old('phone', $user->phone ?: $tenant?->phone) }}" required maxlength="15" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                        <div class="md:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Địa chỉ thường trú</label><textarea name="address" required maxlength="500" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2">{{ old('address', $tenant?->address) }}</textarea></div>
                    </div>
                    <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Lưu hồ sơ</button>
                </form>
            </section>

            <section class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Đổi mật khẩu</h3>
                <p class="mt-1 text-sm text-slate-500">Mật khẩu mới phải khác mật khẩu hiện tại.</p>
                <form method="POST" action="{{ route('client.account.password.update') }}" class="mt-5 space-y-4">@csrf @method('PUT')
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Mật khẩu hiện tại</label><input type="password" name="current_password" required autocomplete="current-password" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Mật khẩu mới</label><input type="password" name="password" required minlength="8" autocomplete="new-password" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nhập lại mật khẩu mới</label><input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <button class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Đổi mật khẩu</button>
                </form>
            </section>
        </div>
    </div>
@endsection
