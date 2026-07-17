<header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button id="clientSidebarOpen" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 lg:hidden" aria-label="Mở menu">
                <span class="text-xl leading-none">☰</span>
            </button>
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase text-slate-400">Cổng khách thuê</p>
                <h1 class="truncate text-lg font-bold text-slate-950">@yield('page_title', 'Tổng quan')</h1>
            </div>
        </div>

        <div class="relative">
            <button id="clientUserMenuButton" type="button" class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-left hover:bg-slate-50">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                    {{ mb_substr(Auth::user()->name ?? 'K', 0, 1) }}
                </span>
                <span class="hidden sm:block">
                    <span class="block text-sm font-semibold text-slate-900">{{ Auth::user()->name ?? 'Khách thuê' }}</span>
                    <span class="block text-xs text-slate-500">Tài khoản khách thuê</span>
                </span>
            </button>

            <div id="clientUserMenu" class="absolute right-0 mt-2 hidden w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                <a href="#" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Thông tin cá nhân</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-3 text-left text-sm font-medium text-rose-600 hover:bg-rose-50">
                        Đăng xuất
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
