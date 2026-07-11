<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập | Quản lý phòng trọ</title>
    <link href="{{ asset('assets/images/favicon.ico') }}" rel="shortcut icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900">
    <main class="flex min-h-screen">
        <section class="hidden flex-1 bg-slate-950 px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-white">
                    <img src="{{ asset('assets/images/logo-sm.svg') }}" alt="Logo" class="h-7 w-7">
                </span>
                <span>
                    <span class="block text-lg font-bold">Rental Admin</span>
                    <span class="text-sm text-slate-300">Quản lý phòng trọ</span>
                </span>
            </a>

            <div class="max-w-xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-300">Hệ thống vận hành</p>
                <h1 class="mt-4 text-4xl font-bold leading-tight">Theo dõi phòng, khách thuê, hợp đồng và hóa đơn trong một nơi.</h1>
                <p class="mt-5 text-base leading-7 text-slate-300">
                    Giao diện quản trị gọn gàng giúp chủ trọ xử lý công việc hằng ngày nhanh hơn và ít sai sót hơn.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-4 text-sm">
                <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                    <p class="text-2xl font-bold">24/7</p>
                    <p class="mt-1 text-slate-300">Truy cập hệ thống</p>
                </div>
                <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                    <p class="text-2xl font-bold">1 nơi</p>
                    <p class="mt-1 text-slate-300">Quản lý dữ liệu</p>
                </div>
                <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                    <p class="text-2xl font-bold">Rõ ràng</p>
                    <p class="mt-1 text-slate-300">Báo cáo hóa đơn</p>
                </div>
            </div>
        </section>

        <section class="flex flex-1 items-center justify-center px-4 py-10 sm:px-6 lg:px-10">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center lg:hidden">
                    <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-3">
                        <img src="{{ asset('assets/images/logo-sm.svg') }}" alt="Logo" class="h-10 w-10">
                        <span class="text-lg font-bold">Quản lý phòng trọ</span>
                    </a>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div>
                        <p class="text-sm font-medium text-indigo-600">Chào mừng quay lại</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-950">Đăng nhập</h2>
                        <p class="mt-2 text-sm text-slate-500">Nhập email và mật khẩu để tiếp tục.</p>
                    </div>

                    @if ($errors->any())
                        <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            <ul class="space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                                class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Mật khẩu</label>
                            <div class="flex rounded-lg border border-slate-200 bg-white transition focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-100">
                                <input id="password" type="password" name="password" required
                                    class="h-11 min-w-0 flex-1 rounded-l-lg border-0 px-3 text-sm outline-none">
                                <button type="button" id="togglePassword" class="h-11 shrink-0 rounded-r-lg px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                                    Hiện
                                </button>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input id="remember" type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Ghi nhớ đăng nhập</span>
                        </label>

                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                            Đăng nhập
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-500">
                        Nếu bạn chưa có tài khoản, vui lòng liên hệ quản trị viên.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.getElementById('togglePassword')?.addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.textContent = isPassword ? 'Ẩn' : 'Hiện';
        });
    </script>
</body>
</html>
