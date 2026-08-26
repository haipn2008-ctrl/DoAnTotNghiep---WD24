<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cổng khách thuê | Quản lý phòng trọ')</title>
    <link rel="icon" href="data:,">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap">
    @stack('styles')
</head>
<body class="min-h-screen bg-[#f6f8ff] font-sans text-slate-900 antialiased">
    <div class="flex min-h-screen">
        @include('layouts.client.blocks.sidebar')

        <div id="clientSidebarOverlay" class="fixed inset-0 z-30 hidden bg-slate-950/40 lg:hidden"></div>

        <div class="flex min-w-0 flex-1 flex-col lg:pl-80">
            @include('layouts.client.blocks.header')

            <main class="flex-1 bg-[radial-gradient(circle_at_top_right,_rgba(224,231,255,.75),_transparent_35%),linear-gradient(180deg,#f8faff_0%,#f3f6fc_100%)] px-4 py-7 sm:px-6 lg:px-10">
                <div class="mx-auto max-w-7xl">
                    @if (auth()->user()?->status === \App\Models\User::STATUS_SETTLING)
                        <div class="mb-6 flex flex-col justify-between gap-3 rounded-2xl border border-violet-200 bg-violet-50/90 px-5 py-4 text-sm text-violet-900 shadow-sm sm:flex-row sm:items-center">
                            <span><strong>Bạn đã trả phòng.</strong> Tài khoản đang được duy trì để hoàn tất quyết toán.</span>
                            @unless(request()->routeIs('client.settlement.*'))
                                <a href="{{ route('client.settlement.index') }}" class="font-semibold text-violet-700 hover:text-violet-900">Đến cổng quyết toán →</a>
                            @endunless
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                            {{ session('warning') }}
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>

            @include('layouts.client.blocks.footer')
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('clientSidebar');
            const overlay = document.getElementById('clientSidebarOverlay');
            const openButton = document.getElementById('clientSidebarOpen');
            const closeButton = document.getElementById('clientSidebarClose');
            const userMenuButton = document.getElementById('clientUserMenuButton');
            const userMenu = document.getElementById('clientUserMenu');
            const notificationButton = document.getElementById('clientNotificationButton');
            const notificationMenu = document.getElementById('clientNotificationMenu');

            function openSidebar() {
                sidebar?.classList.remove('-translate-x-full');
                overlay?.classList.remove('hidden');
            }

            function closeSidebar() {
                sidebar?.classList.add('-translate-x-full');
                overlay?.classList.add('hidden');
            }

            openButton?.addEventListener('click', openSidebar);
            closeButton?.addEventListener('click', closeSidebar);
            overlay?.addEventListener('click', closeSidebar);

            userMenuButton?.addEventListener('click', function (event) {
                event.stopPropagation();
                notificationMenu?.classList.add('hidden');
                userMenu?.classList.toggle('hidden');
            });

            notificationButton?.addEventListener('click', function (event) {
                event.stopPropagation();
                userMenu?.classList.add('hidden');
                notificationMenu?.classList.toggle('hidden');
                notificationButton.setAttribute('aria-expanded', notificationMenu?.classList.contains('hidden') ? 'false' : 'true');
            });

            notificationMenu?.addEventListener('click', event => event.stopPropagation());

            document.addEventListener('click', function () {
                userMenu?.classList.add('hidden');
                notificationMenu?.classList.add('hidden');
                notificationButton?.setAttribute('aria-expanded', 'false');
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
