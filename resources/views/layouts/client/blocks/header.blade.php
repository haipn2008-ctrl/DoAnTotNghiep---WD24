<header class="sticky top-0 z-20 border-b border-indigo-100/80 bg-white/85 shadow-[0_1px_0_rgba(15,23,42,.03)] backdrop-blur-xl">
    <div class="flex h-[72px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-10">
        <div class="flex min-w-0 items-center gap-3">
            <button id="clientSidebarOpen" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 lg:hidden" aria-label="Mở menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
            </button>
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-[.16em] text-indigo-400">Stay Master · Cổng khách thuê</p>
                <h1 class="truncate text-lg font-bold tracking-tight text-slate-950">@yield('page_title', 'Tổng quan')</h1>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative">
                <button id="clientNotificationButton" type="button" aria-label="Mở thông báo" aria-expanded="false" class="relative flex h-11 w-11 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50/70 text-indigo-600 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-100">
                    <x-bell-icon class="h-5 w-5" />
                    @if(($clientUnreadNotificationCount ?? 0) > 0)
                        <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold text-white">{{ $clientUnreadNotificationCount > 99 ? '99+' : $clientUnreadNotificationCount }}</span>
                    @endif
                </button>

                <div id="clientNotificationMenu" class="absolute right-0 top-12 z-40 hidden w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                        <div>
                            <p class="font-semibold text-slate-900">Thông báo của tôi</p>
                            <p class="text-xs text-slate-500">{{ $clientUnreadNotificationCount ?? 0 }} thông báo chưa đọc</p>
                        </div>
                        <a href="{{ route('client.notifications.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Xem tất cả</a>
                    </div>

                    <div class="max-h-96 overflow-y-auto">
                        @forelse(($clientNotifications ?? collect()) as $notification)
                            @php($data = $notification->data)
                            <a href="{{ route('client.notifications.open', $notification->id) }}" class="flex gap-3 border-b border-slate-100 px-4 py-3 transition last:border-0 hover:bg-slate-50 {{ $notification->read_at ? '' : 'bg-indigo-50/60' }}">
                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-indigo-100 text-indigo-700' }}"><x-bell-icon class="h-5 w-5" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $data['title'] ?? 'Thông báo' }}</span>
                                    <span class="mt-0.5 block line-clamp-2 text-xs leading-5 text-slate-500">{{ $data['message'] ?? '' }}</span>
                                    <span class="mt-1 block text-[11px] text-slate-400">{{ $notification->created_at?->diffForHumans() }}@unless($notification->read_at) · Mới @endunless</span>
                                </span>
                            </a>
                        @empty
                            <div class="px-5 py-10 text-center">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><x-bell-icon class="h-6 w-6" /></span>
                                <p class="mt-3 text-sm font-semibold text-slate-900">Chưa có thông báo</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="relative">
            <button id="clientUserMenuButton" type="button" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white/90 px-2.5 py-2 text-left shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50/40">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                    {{ mb_substr(Auth::user()->name ?? 'K', 0, 1) }}
                </span>
                <span class="hidden sm:block">
                    <span class="block text-sm font-semibold text-slate-900">{{ Auth::user()->name ?? 'Khách thuê' }}</span>
                    <span class="block text-xs text-slate-500">Tài khoản khách thuê</span>
                </span>
            </button>

            <div id="clientUserMenu" class="absolute right-0 mt-2 hidden w-56 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                <a href="{{ route('client.account.edit') }}" class="block px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Thông tin cá nhân</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-3 text-left text-sm font-medium text-rose-600 hover:bg-rose-50">
                        Đăng xuất
                    </button>
                </form>
            </div>
            </div>
        </div>
    </div>
</header>
