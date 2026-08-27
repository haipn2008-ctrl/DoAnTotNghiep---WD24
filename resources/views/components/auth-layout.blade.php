@props(['title', 'eyebrow' => null, 'description' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Quản lý phòng trọ</title>
    <link rel="icon" href="data:,">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap">
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900">
    <main class="flex min-h-screen">
        <section class="hidden flex-1 bg-slate-950 px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-white">
                    <img src="{{ asset('assets/images/logo-sm.svg') }}" alt="Logo" class="h-7 w-7">
                </span>
                <span>
                    <span class="block text-lg font-bold">Quản trị nhà trọ</span>
                    <span class="text-sm text-slate-300">Quản lý phòng trọ</span>
                </span>
            </a>

            <div class="max-w-xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-300">Hệ thống vận hành</p>
                <h1 class="mt-4 text-4xl font-bold leading-tight">Quản lý tài khoản của bạn một cách an toàn.</h1>
                <p class="mt-5 text-base leading-7 text-slate-300">
                    Liên kết đặt lại mật khẩu chỉ được gửi đến email đã đăng ký và sẽ tự động hết hạn sau 60 phút.
                </p>
            </div>

            <p class="text-sm text-slate-400">Không chia sẻ liên kết đặt lại mật khẩu với bất kỳ ai.</p>
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
                        @if ($eyebrow)
                            <p class="text-sm font-medium text-indigo-600">{{ $eyebrow }}</p>
                        @endif
                        <h2 class="mt-2 text-2xl font-bold text-slate-950">{{ $title }}</h2>
                        @if ($description)
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ $description }}</p>
                        @endif
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </section>
    </main>
</body>
</html>
