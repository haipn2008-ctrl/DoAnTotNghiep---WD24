<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cổng khách thuê | Quản lý phòng trọ')</title>
    <link href="{{ asset('assets/images/favicon.ico') }}" rel="shortcut icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900">
    <div class="flex min-h-screen">
        @include('layouts.client.blocks.sidebar')

        <div id="clientSidebarOverlay" class="fixed inset-0 z-30 hidden bg-slate-950/40 lg:hidden"></div>

        <div class="flex min-w-0 flex-1 flex-col lg:pl-72">
            @include('layouts.client.blocks.header')

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
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

            userMenuButton?.addEventListener('click', function () {
                userMenu?.classList.toggle('hidden');
            });

            document.addEventListener('click', function (event) {
                if (!userMenuButton?.contains(event.target) && !userMenu?.contains(event.target)) {
                    userMenu?.classList.add('hidden');
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
