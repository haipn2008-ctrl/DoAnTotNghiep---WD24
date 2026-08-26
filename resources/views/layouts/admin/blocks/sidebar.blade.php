@php
    $navGroups = [
        [
            'label' => 'Tổng quan',
            'icon' => 'bx bx-home-alt',
            'active' => request()->routeIs('admin.overview*') || request()->routeIs('admin.reconciliation*') || request()->routeIs('admin.home'),
            'items' => [
                ['label' => 'Bảng điều khiển', 'route' => 'admin.home', 'active' => request()->routeIs('admin.home')],
                ['label' => 'Phân tích doanh thu', 'route' => 'admin.overview.revenue-chart', 'active' => request()->routeIs('admin.overview.revenue-chart')],
                ['label' => 'Đối soát thu tiền', 'route' => 'admin.reconciliation.index', 'active' => request()->routeIs('admin.reconciliation*')],
                ['label' => 'Thống kê phòng', 'route' => 'admin.overview.room-stats', 'active' => request()->routeIs('admin.overview.room-stats')],
                ['label' => 'Tỷ lệ lấp đầy', 'route' => 'admin.overview.fill-rate', 'active' => request()->routeIs('admin.overview.fill-rate')],
            ],
        ],
        [
            'label' => 'Phòng trọ',
            'icon' => 'bx bx-building-house',
            'active' => request()->routeIs('admin.rooms*'),
            'items' => [
                ['label' => 'Danh sách phòng', 'route' => 'admin.rooms.index', 'active' => request()->routeIs('admin.rooms.index')],
                ['label' => 'Thêm phòng mới', 'route' => 'admin.rooms.create', 'active' => request()->routeIs('admin.rooms.create')],
            ],
        ],
        [
            'label' => 'Khách thuê',
            'icon' => 'bx bx-user',
            'active' => request()->routeIs('admin.tenants*'),
            'items' => [
                ['label' => 'Danh sách khách thuê', 'route' => 'admin.tenants.index', 'active' => request()->routeIs('admin.tenants.index')],

            ],
        ],
        [
            'label' => 'Hợp đồng',
            'icon' => 'bx bx-file',
            'active' => request()->routeIs('admin.contracts*')
                || request()->routeIs('admin.extension-requests*')
                || request()->routeIs('admin.termination-requests*'),

            'items' => [
                [
                    'label' => 'Danh sách hợp đồng',
                    'route' => 'admin.contracts.index',
                    'active' => request()->routeIs('admin.contracts.index')
                ],
                [
                    'label' => 'Mẫu hợp đồng',
                    'route' => 'admin.contracts.template',
                    'active' => request()->routeIs('admin.contracts.template')
                ],
                [
                    'label' => 'Duyệt gia hạn',
                    'route' => 'admin.extension-requests.index',
                    'active' => request()->routeIs('admin.extension-requests*')
                ],
                [
                    'label' => 'Duyệt trả phòng',
                    'route' => 'admin.termination-requests.index',
                    'active' => request()->routeIs('admin.termination-requests*')
                ],
            ],
        ],
        [
            'label' => 'Điện nước',
            'icon' => 'bx bx-bolt-circle',
            'active' => request()->routeIs('admin.utilities*'),
            'items' => [
                ['label' => 'Nhập chỉ số', 'route' => 'admin.utilities.create', 'active' => request()->routeIs('admin.utilities.create')],
                ['label' => 'Kiểm tra chỉ số', 'route' => 'admin.utilities.index', 'active' => request()->routeIs('admin.utilities.index')],
            ],
        ],
        [
            'label' => 'Hóa đơn',
            'icon' => 'bx bx-receipt',
            'active' => request()->routeIs('admin.invoices*') || request()->routeIs('admin.debts*'),
            'items' => [
                ['label' => 'Sinh hóa đơn', 'route' => 'admin.invoices.generate', 'active' => request()->routeIs('admin.invoices.generate')],
                ['label' => 'Danh sách hóa đơn', 'route' => 'admin.invoices.index', 'active' => request()->routeIs('admin.invoices.index')],
                ['label' => 'Thanh toán', 'route' => 'admin.invoices.payments', 'active' => request()->routeIs('admin.invoices.payments')],
                ['label' => 'Công nợ', 'route' => 'admin.debts.index', 'active' => request()->routeIs('admin.debts*')],
            ],
        ],
        [
            'label' => 'Hỗ trợ',
            'icon' => 'bx bx-support',
            'active' => request()->routeIs('admin.support*'),
            'items' => [
                ['label' => 'Yêu cầu khách thuê', 'route' => 'admin.support.index', 'active' => request()->routeIs('admin.support*')],
            ],
        ],
        [
            'label' => 'Hệ thống',
            'icon' => 'bx bx-cog',
            'active' => request()->routeIs('admin.users*') || request()->routeIs('admin.roles') || request()->routeIs('admin.settings*'),
            'items' => [
                ['label' => 'Tài khoản', 'route' => 'admin.users.index', 'active' => request()->routeIs('admin.users*')],
                ['label' => 'Phân quyền', 'route' => 'admin.roles', 'active' => request()->routeIs('admin.roles')],
                ['label' => 'Phí và đơn giá', 'route' => 'admin.settings.edit', 'params' => ['type' => 'fees'], 'active' => request()->fullUrlIs(route('admin.settings.edit', ['type' => 'fees']))],
                ['label' => 'Nhà trọ và thanh toán', 'route' => 'admin.settings.edit', 'params' => ['type' => 'property-payment'], 'active' => request()->fullUrlIs(route('admin.settings.edit', ['type' => 'property-payment']))],
            ],
        ],
    ];
@endphp

<aside id="admin-sidebar"
    class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0">
    <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5">
        <a href="{{ route('admin.home') }}" class="flex items-center gap-3">
            <span
                class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-xl font-bold text-white">S</span>
            <span>
                <span class="block text-sm font-bold text-slate-900">Stay Master</span>
                <span class="block text-xs text-slate-500">Quản lý phòng trọ</span>
            </span>
        </a>

        <button id="admin-sidebar-close" type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden">
            <i class="bx bx-x text-2xl"></i>
        </button>
    </div>

    <nav class="h-[calc(100vh-4rem)] overflow-y-auto px-4 py-5">
        <p class="px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Menu chính</p>

        <div class="mt-3 space-y-2">
            @foreach ($navGroups as $group)
                <details class="group rounded-lg" {{ $group['active'] ? 'open' : '' }}>
                    <summary
                        class="flex cursor-pointer list-none items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold {{ $group['active'] ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <i class="{{ $group['icon'] }} text-xl"></i>
                        <span class="flex-1">{{ $group['label'] }}</span>
                        <i class="bx bx-chevron-down text-lg transition-transform group-open:rotate-180"></i>
                    </summary>

                    <div class="mt-1 space-y-1 pl-10">
                        @foreach ($group['items'] as $item)
                            <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                                class="block rounded-lg px-3 py-2 text-sm {{ $item['active'] ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </div>
    </nav>
</aside>
