<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kích hoạt tài khoản | Quản lý phòng trọ</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm font-semibold text-indigo-600">Lần đăng nhập đầu tiên</p>
            <h1 class="mt-2 text-2xl font-bold">Kích hoạt tài khoản</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">Xác nhận thông tin và tạo mật khẩu riêng. Sau bước này bạn mới có thể sử dụng cổng khách thuê.</p>
            @if ($errors->any())
                <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form method="POST" action="{{ route('account.activation.activate') }}" class="mt-6 space-y-4">
                @csrf
                <div><label class="mb-1.5 block text-sm font-semibold">Email đăng nhập</label><input value="{{ $user->email }}" disabled class="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-slate-500"></div>
                <div><label for="name" class="mb-1.5 block text-sm font-semibold">Họ và tên</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                <div><label for="phone" class="mb-1.5 block text-sm font-semibold">Số điện thoại</label><input id="phone" name="phone" value="{{ old('phone', $user->phone ?? $user->tenant?->phone) }}" required maxlength="20" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="password" class="mb-1.5 block text-sm font-semibold">Mật khẩu mới</label><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                    <div><label for="password_confirmation" class="mb-1.5 block text-sm font-semibold">Nhập lại mật khẩu</label><input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                </div>
                <label class="flex items-start gap-3 rounded-lg bg-slate-50 p-4 text-sm"><input type="checkbox" name="accept_terms" value="1" required class="mt-0.5 h-4 w-4"><span>Tôi xác nhận thông tin trên là chính xác, đồng ý với nội quy nhà trọ, điều khoản sử dụng cổng khách thuê và chính sách xử lý dữ liệu liên quan đến hợp đồng thuê.</span></label>
                <button class="h-11 w-full rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700">Kích hoạt tài khoản</button>
            </form>
            <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">@csrf<button class="text-sm font-medium text-slate-500">Đăng xuất</button></form>
        </div>
    </main>
</body>
</html>
