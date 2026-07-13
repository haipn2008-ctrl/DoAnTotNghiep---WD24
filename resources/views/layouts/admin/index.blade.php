<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản lý phòng trọ')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap">
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css">
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-700 antialiased">
    <div class="min-h-screen lg:flex">
        <div id="admin-sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/40 lg:hidden"></div>

        @include('layouts.admin.blocks.sidebar')

        <div class="flex min-w-0 flex-1 flex-col lg:pl-72">
            @include('layouts.admin.blocks.header')

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

            @include('layouts.admin.blocks.footer')
        </div>
    </div>

    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('admin-sidebar');
            const backdrop = document.getElementById('admin-sidebar-backdrop');
            const openButton = document.getElementById('admin-sidebar-open');
            const closeButton = document.getElementById('admin-sidebar-close');
            const userMenuButton = document.getElementById('admin-user-menu-button');
            const userMenu = document.getElementById('admin-user-menu');

            const openSidebar = () => {
                sidebar?.classList.remove('-translate-x-full');
                backdrop?.classList.remove('hidden');
            };

            const closeSidebar = () => {
                sidebar?.classList.add('-translate-x-full');
                backdrop?.classList.add('hidden');
            };

            openButton?.addEventListener('click', openSidebar);
            closeButton?.addEventListener('click', closeSidebar);
            backdrop?.addEventListener('click', closeSidebar);

            userMenuButton?.addEventListener('click', (event) => {
                event.stopPropagation();
                userMenu?.classList.toggle('hidden');
            });

            document.addEventListener('click', () => {
                userMenu?.classList.add('hidden');
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
