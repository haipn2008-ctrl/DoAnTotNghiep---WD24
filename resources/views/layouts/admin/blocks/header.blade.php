<header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button id="admin-sidebar-open" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 lg:hidden">
                <i class="bx bx-menu text-2xl"></i>
            </button>

            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Quản lý lưu trú</p>
                <h1 class="truncate text-lg font-semibold text-slate-900">@yield('page_title', 'Quản lý phòng trọ')</h1>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative">
                <button id="admin-notification-button" type="button" aria-label="Mở thông báo" aria-expanded="false" class="relative flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                    <i class="bx bx-bell text-xl"></i>
                    @if(($adminNotificationCount ?? 0) > 0)
                        <span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white">
                            {{ $adminNotificationCount > 99 ? '99+' : $adminNotificationCount }}
                        </span>
                    @endif
                </button>

                <div id="admin-notification-menu" class="absolute right-0 top-12 z-40 hidden w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                        <div>
                            <p class="font-semibold text-slate-900">Thông báo quản trị</p>
                            <p class="text-xs text-slate-500">{{ $adminNotificationCount ?? 0 }} việc đang cần xử lý</p>
                        </div>
                        <a href="{{ route('admin.notifications.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Xem tất cả</a>
                    </div>

                    <div class="max-h-96 overflow-y-auto">
                        @forelse(($adminNotifications ?? collect()) as $notification)
                            <a href="{{ route('admin.contracts.show', $notification->contract) }}" class="flex gap-3 border-b border-slate-100 px-4 py-3 transition last:border-0 hover:bg-slate-50">
                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600"><i class="bx bx-error-circle text-xl"></i></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $notification->title }}</span>
                                    <span class="mt-0.5 block line-clamp-2 text-xs leading-5 text-slate-500">{{ $notification->message }}</span>
                                    <span class="mt-1 block text-[11px] text-slate-400">{{ $notification->contract?->contract_code }} · {{ $notification->detected_at?->diffForHumans() }}</span>
                                </span>
                            </a>
                        @empty
                            <div class="px-5 py-10 text-center">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"><i class="bx bx-check text-2xl"></i></span>
                                <p class="mt-3 text-sm font-semibold text-slate-900">Không có việc tồn đọng</p>
                                <p class="mt-1 text-xs text-slate-500">Các cảnh báo mới sẽ xuất hiện tại đây.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="hidden text-right sm:block">
                <p class="text-sm font-semibold text-slate-900">{{ Auth::user()->name ?? 'Quản trị viên' }}</p>
                <p class="text-xs text-slate-500">Quản trị hệ thống</p>
            </div>

            <div class="relative">
                <button id="admin-user-menu-button" type="button" class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white shadow-sm">
                    {{ mb_substr(Auth::user()->name ?? 'A', 0, 1) }}
                </button>

                <div id="admin-user-menu" class="absolute right-0 top-12 z-40 hidden w-56 rounded-lg border border-slate-200 bg-white py-2 shadow-lg">
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
    </div>
</header>
