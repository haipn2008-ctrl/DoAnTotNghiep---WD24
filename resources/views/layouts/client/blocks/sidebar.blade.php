@php
    $menuItems = [
        ['label' => 'Tổng quan', 'href' => route('client.home'), 'active' => request()->routeIs('client.home'), 'icon' => '⌂'],
        ['label' => 'Phòng của tôi', 'href' => '#room', 'active' => false, 'icon' => '□'],
        ['label' => 'Hợp đồng', 'href' => '#contract', 'active' => false, 'icon' => '≡'],
        ['label' => 'Điện nước', 'href' => '#utilities', 'active' => false, 'icon' => '↯'],
        ['label' => 'Hóa đơn', 'href' => '#invoices', 'active' => false, 'icon' => '₫'],
        ['label' => 'Hỗ trợ', 'href' => '#support', 'active' => false, 'icon' => '?'],
    ];
@endphp

<aside id="clientSidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">
    <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5">
        <a href="{{ route('client.home') }}" class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600">
                <img src="{{ asset('assets/images/logo-sm.svg') }}" alt="Logo" class="h-6 w-6">
            </span>
            <span>
                <span class="block text-sm font-bold text-slate-950">Cổng khách thuê</span>
                <span class="block text-xs text-slate-500">Quản lý phòng trọ</span>
            </span>
        </a>
        <button id="clientSidebarClose" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Đóng menu">
            ×
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @foreach ($menuItems as $item)
            <a href="{{ $item['href'] }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ $item['active'] ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                <span class="flex h-7 w-7 items-center justify-center rounded-md {{ $item['active'] ? 'bg-indigo-100' : 'bg-slate-100' }}">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="border-t border-slate-200 p-4">
        <div class="rounded-lg bg-slate-50 p-4">
            <p class="text-sm font-semibold text-slate-950">Cần hỗ trợ?</p>
            <p class="mt-1 text-xs leading-5 text-slate-500">Liên hệ ban quản lý để được xử lý yêu cầu về phòng, hóa đơn hoặc hợp đồng.</p>
            <p class="mt-3 text-sm font-bold text-indigo-700">1900 xxxx</p>
        </div>
    </div>
</aside>
