@extends('layouts.client.index')

@section('title', 'Tài khoản | Cổng khách thuê')
@section('page_title', 'Tài khoản')

@section('content')
    <div class="space-y-5">
        <div><p class="text-sm font-medium text-slate-500">Thông tin đăng nhập</p><h2 class="mt-1 text-2xl font-bold text-slate-950">Tài khoản của tôi</h2><p class="mt-2 text-sm text-slate-500">Bạn có thể cập nhật thông tin liên hệ. Dữ liệu CCCD, phòng và hợp đồng do ban quản lý phụ trách.</p></div>
        @if($errors->any())<div class="rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Thông tin cá nhân</h3>
                <form method="POST" action="{{ route('client.account.update') }}" class="mt-5 space-y-4">@csrf @method('PUT')
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Tên hiển thị</label><input name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Số điện thoại</label><input name="phone" value="{{ old('phone', $user->phone ?? $user->tenant?->phone) }}" required maxlength="20" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    @if($user->tenant)<div class="rounded-lg bg-slate-50 p-4 text-sm"><div class="flex justify-between gap-4"><span class="text-slate-500">Họ tên hồ sơ</span><strong>{{ $user->tenant->full_name }}</strong></div><div class="mt-3 flex justify-between gap-4"><span class="text-slate-500">CCCD</span><strong>{{ $user->tenant->cccd }}</strong></div></div>@endif
                    <button class="h-11 w-full rounded-lg bg-indigo-600 text-sm font-semibold text-white">Lưu thông tin</button>
                </form>
            </section>

            <section class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Đổi mật khẩu</h3>
                <form method="POST" action="{{ route('client.account.password.update') }}" class="mt-5 space-y-4">@csrf @method('PUT')
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Mật khẩu hiện tại</label><input type="password" name="current_password" required autocomplete="current-password" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Mật khẩu mới</label><input type="password" name="password" required minlength="8" autocomplete="new-password" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nhập lại mật khẩu mới</label><input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <button class="h-11 w-full rounded-lg bg-slate-900 text-sm font-semibold text-white">Đổi mật khẩu</button>
                </form>
            </section>
        </div>
    </div>
@endsection
