<header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button id="admin-sidebar-open" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 lg:hidden">
                <i class="bx bx-menu text-2xl"></i>
            </button>

            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Stay Master</p>
                <h1 class="truncate text-lg font-semibold text-slate-900">@yield('page_title', 'Quản lý phòng trọ')</h1>
            </div>
        </div>

        <div class="relative flex items-center gap-3">
            <div class="hidden text-right sm:block">
                <p class="text-sm font-semibold text-slate-900">{{ Auth::user()->name ?? 'Admin' }}</p>
                <p class="text-xs text-slate-500">Quản trị hệ thống</p>
            </div>

            <button id="admin-user-menu-button" type="button" class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white shadow-sm">
                {{ mb_substr(Auth::user()->name ?? 'A', 0, 1) }}
            </button>

            <div id="admin-user-menu" class="absolute right-0 top-12 hidden w-56 rounded-lg border border-slate-200 bg-white py-2 shadow-lg">
                <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                    <i class="bx bx-user text-lg"></i>
                    Tài khoản
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">
                        <i class="bx bx-log-out text-lg"></i>
                        Đăng xuất
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
