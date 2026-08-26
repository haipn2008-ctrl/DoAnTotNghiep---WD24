@php
    $isRentalActive = auth()->user()?->isActive();
    $isSettling = auth()->user()?->status === \App\Models\User::STATUS_SETTLING;
    $menuItems = [
        [
            'label' => 'Tổng quan',
            'href' => $isSettling ? route('client.settlement.index') : route('client.home'),
            'active' => request()->routeIs('client.home', 'client.settlement.*'),
            'icon' => '⌂'
        ],
        [
            'label' => 'Phòng của tôi',
            'href' => route('client.room.show'),
            'active' => request()->routeIs('client.room.*'),
            'icon' => '□'
        ],
    ];
    $restrictedRentalPaths = ['/client/room', '/client/utilities', '/client/support'];
    $menuItems = array_filter($menuItems, fn ($item) => $isRentalActive
        || ! in_array(parse_url($item['href'], PHP_URL_PATH), $restrictedRentalPaths, true));
@endphp


<aside id="clientSidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">

    {{-- =========================
        HEADER
    ========================== --}}
    <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5">

        <a href="{{ route('client.home') }}" class="flex items-center gap-3">

            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600">
                <img src="{{ asset('assets/images/logo-sm.svg') }}"
                     alt="Logo"
                     class="h-6 w-6">
            </span>

            <span>
                <span class="block text-sm font-bold text-slate-950">
                    Cổng khách thuê
                </span>

                <span class="block text-xs text-slate-500">
                    Quản lý phòng trọ
                </span>
            </span>

        </a>


        <button id="clientSidebarClose"
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden"
                aria-label="Đóng menu">
            ×
        </button>

    </div>


    {{-- =========================
        MENU
    ========================== --}}
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">


        {{-- TỔNG QUAN + PHÒNG CỦA TÔI --}}
        @foreach ($menuItems as $item)

            <a href="{{ $item['href'] }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition
               {{ $item['active']
                    ? 'bg-indigo-50 text-indigo-700'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">

                <span class="flex h-7 w-7 items-center justify-center rounded-md
                    {{ $item['active'] ? 'bg-indigo-100' : 'bg-slate-100' }}">

                    {{ $item['icon'] }}

                </span>

                <span>{{ $item['label'] }}</span>

            </a>

        @endforeach

        <a href="{{ route('client.notifications.index') }}"
           class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('client.notifications.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
            <span class="flex items-center gap-3">
                <span class="flex h-7 w-7 items-center justify-center rounded-md {{ request()->routeIs('client.notifications.*') ? 'bg-indigo-100' : 'bg-slate-100' }}"><i class="bx bx-bell text-lg"></i></span>
                <span>Thông báo</span>
            </span>
            @if($clientSidebarUnreadCount > 0)
                <span class="rounded-full bg-rose-500 px-2 py-0.5 text-[11px] font-bold text-white">{{ $clientSidebarUnreadCount > 99 ? '99+' : $clientSidebarUnreadCount }}</span>
            @endif
        </a>



        {{-- =====================================================
            HỢP ĐỒNG
        ====================================================== --}}
        <div>

            <button type="button"
                    id="contractMenuButton"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-950">

                <span class="flex items-center gap-3">

                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100">
                        ≡
                    </span>

                    <span>Hợp đồng</span>

                </span>


                {{-- ARROW --}}
                <svg id="contractMenuArrow"
                     class="h-4 w-4 transition-transform duration-200"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="m19 9-7 7-7-7" />

                </svg>

            </button>



            {{-- SUBMENU HỢP ĐỒNG --}}
            <div id="contractSubmenu"
                class="ml-4 mt-1 space-y-1 border-l-2 border-indigo-100 pl-2
                {{ request()->routeIs(
                    'client.contracts.*',
                    'client.deposit-refunds.*',
                    'client.extension-requests.*',
                    'client.termination-requests.*',
                    'client.requests.history'
                ) ? '' : 'hidden' }}">

                <a href="{{ route('client.contracts.index') }}"
                class="block rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('client.contracts.index', 'client.contracts.show', 'client.contracts.file') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500 hover:bg-indigo-50 hover:text-indigo-700' }}">
                    Hợp đồng của tôi
                </a>

                @if($isRentalActive)
                <a href="{{ route('client.extension-requests.index') }}"
                class="block rounded-lg px-3 py-2 text-sm font-medium transition
                {{ request()->routeIs('client.extension-requests.*')
                        ? 'bg-indigo-50 text-indigo-700'
                        : 'text-slate-500 hover:bg-indigo-50 hover:text-indigo-700' }}">
                    Yêu cầu gia hạn
                </a>

                <a href="{{ route('client.termination-requests.index') }}"
                class="block rounded-lg px-3 py-2 text-sm font-medium transition
                {{ request()->routeIs('client.termination-requests.*')
                        ? 'bg-indigo-50 text-indigo-700'
                        : 'text-slate-500 hover:bg-indigo-50 hover:text-indigo-700' }}">
                    Yêu cầu trả phòng
                </a>
                @endif

                <a href="{{ route('client.requests.history') }}"
                class="block rounded-lg px-3 py-2 text-sm font-medium transition
                {{ request()->routeIs('client.requests.history')
                        ? 'bg-indigo-50 text-indigo-700'
                        : 'text-slate-500 hover:bg-indigo-50 hover:text-indigo-700' }}">
                    Lịch sử yêu cầu
                </a>

            </div>

        </div>

        @if($clientSettlementContract)
            <a href="{{ route('client.settlement.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('client.settlement.*', 'client.deposit-refunds.*') ? 'bg-violet-50 text-violet-700' : 'text-slate-600 hover:bg-violet-50 hover:text-violet-700' }}">
                <span class="flex h-7 w-7 items-center justify-center rounded-md {{ request()->routeIs('client.settlement.*', 'client.deposit-refunds.*') ? 'bg-violet-100' : 'bg-slate-100' }}"><i class="bx bx-wallet text-lg"></i></span>
                <span>Quyết toán & hoàn cọc</span>
            </a>
        @endif



        {{-- =====================================================
            CÁC CHỨC NĂNG KHÁC - GIỮ NGUYÊN
        ====================================================== --}}

        @if($isRentalActive)
        {{-- ĐIỆN NƯỚC --}}
        <a href="{{ route('client.utilities.index') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('client.utilities.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">

            <span class="flex h-7 w-7 items-center justify-center rounded-md {{ request()->routeIs('client.utilities.*') ? 'bg-indigo-100' : 'bg-slate-100' }}">
                ↯
            </span>

            <span>Điện nước</span>

        </a>
        @endif

        @if($isRentalActive)
            <a href="{{ route('client.vehicles.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('client.vehicles.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                <span class="flex h-7 w-7 items-center justify-center rounded-md {{ request()->routeIs('client.vehicles.*') ? 'bg-indigo-100' : 'bg-slate-100' }}">⌁</span>
                <span>Phương tiện của tôi</span>
            </a>
        @endif


        {{-- HÓA ĐƠN --}}
        <a href="{{ route('client.invoices.index') }}"
        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition
        {{ request()->routeIs('client.invoices.*')
                ? 'bg-indigo-50 text-indigo-700'
                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">

            <span class="flex h-7 w-7 items-center justify-center rounded-md
                {{ request()->routeIs('client.invoices.*')
                    ? 'bg-indigo-100'
                    : 'bg-slate-100' }}">
                ₫
            </span>

            <span>Hóa đơn</span>

        </a>


        {{-- HỖ TRỢ --}}
        <div>
            <button type="button"
                    id="supportMenuButton"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('client.support.*', 'client.landlord-information') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}"
                    aria-expanded="{{ request()->routeIs('client.support.*', 'client.landlord-information') ? 'true' : 'false' }}">
                <span class="flex items-center gap-3">
                    <span class="flex h-7 w-7 items-center justify-center rounded-md {{ request()->routeIs('client.support.*', 'client.landlord-information') ? 'bg-indigo-100' : 'bg-slate-100' }}">?</span>
                    <span>Hỗ trợ</span>
                </span>
                <svg id="supportMenuArrow" class="h-4 w-4 transition-transform duration-200 {{ request()->routeIs('client.support.*', 'client.landlord-information') ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                </svg>
            </button>

            <div id="supportSubmenu"
                 class="ml-4 mt-1 space-y-1 border-l-2 border-indigo-100 pl-2 {{ request()->routeIs('client.support.*', 'client.landlord-information') ? '' : 'hidden' }}">
                @if($isRentalActive || $isSettling)
                    <a href="{{ route('client.support.index') }}"
                       class="block rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('client.support.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500 hover:bg-indigo-50 hover:text-indigo-700' }}">
                        Gửi yêu cầu hỗ trợ
                    </a>
                @endif
                <a href="{{ route('client.landlord-information') }}"
                   class="block rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('client.landlord-information') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500 hover:bg-indigo-50 hover:text-indigo-700' }}">
                    Thông tin chủ trọ
                </a>
            </div>
        </div>

        <a href="{{ route('client.account.edit') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('client.account.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
            <span class="flex h-7 w-7 items-center justify-center rounded-md {{ request()->routeIs('client.account.*') ? 'bg-indigo-100' : 'bg-slate-100' }}"><i class="bx bx-user-circle text-lg"></i></span>
            <span>Tài khoản của tôi</span>
        </a>

    </nav>



    {{-- =========================
        SUPPORT
    ========================== --}}
    <div class="border-t border-slate-200 p-4">

        <div class="rounded-lg bg-slate-50 p-4">

            <p class="text-sm font-semibold text-slate-950">
                Cần hỗ trợ?
            </p>

            <p class="mt-1 text-xs leading-5 text-slate-500">
                Liên hệ ban quản lý để được xử lý yêu cầu về phòng,
                hóa đơn hoặc hợp đồng.
            </p>

            @if(filled($clientSupportPhone))
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $clientSupportPhone) }}"
                   class="mt-3 inline-block text-sm font-bold text-indigo-700 hover:text-indigo-800">
                    {{ $clientSupportPhone }}
                </a>
            @else
                <p class="mt-3 text-sm font-semibold text-slate-500">Chưa thiết lập số điện thoại</p>
            @endif

        </div>

    </div>

</aside>



{{-- =====================================================
    SCRIPT DROPDOWN HỢP ĐỒNG
===================================================== --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const contractButton = document.getElementById('contractMenuButton');
    const contractSubmenu = document.getElementById('contractSubmenu');
    const contractArrow = document.getElementById('contractMenuArrow');
    const supportButton = document.getElementById('supportMenuButton');
    const supportSubmenu = document.getElementById('supportSubmenu');
    const supportArrow = document.getElementById('supportMenuArrow');

    if (!contractButton || !contractSubmenu) {
        return;
    }

    contractButton.addEventListener('click', function () {

        // Mở / đóng submenu
        contractSubmenu.classList.toggle('hidden');

        // Xoay mũi tên
        if (contractArrow) {
            contractArrow.classList.toggle('rotate-180');
        }

    });

    supportButton?.addEventListener('click', function () {
        const isHidden = supportSubmenu?.classList.toggle('hidden');
        supportArrow?.classList.toggle('rotate-180', !isHidden);
        supportButton.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
    });

});
</script>
