@php
    $navGroups = [
        [
            'label' => 'Tổng quan',
            'icon' => 'bx bx-home-alt',
            'active' => request()->routeIs('admin.overview*') || request()->routeIs('admin.home'),
            'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.home', 'active' => request()->routeIs('admin.home')],
                ['label' => 'Biểu đồ doanh thu', 'route' => 'admin.overview.revenue-chart', 'active' => request()->routeIs('admin.overview.revenue-chart')],
                ['label' => 'Thống kê doanh thu', 'route' => 'admin.overview.revenue-stats', 'active' => request()->routeIs('admin.overview.revenue-stats')],
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
                ['label' => 'Xuất danh sách phòng', 'route' => 'admin.rooms.export', 'active' => request()->routeIs('admin.rooms.export')],
            ],
        ],
        [
            'label' => 'Khách thuê',
            'icon' => 'bx bx-user',
            'active' => request()->routeIs('admin.tenants*'),
            'items' => [
                ['label' => 'Danh sách khách thuê', 'route' => 'admin.tenants.index', 'active' => request()->routeIs('admin.tenants.index')],
                ['label' => 'Thêm khách thuê', 'route' => 'admin.tenants.create', 'active' => request()->routeIs('admin.tenants.create')],
                ['label' => 'Xuất danh sách khách thuê', 'route' => 'admin.tenants.export', 'active' => request()->routeIs('admin.tenants.export')],
            ],
        ],
        [
            'label' => 'Hợp đồng',
            'icon' => 'bx bx-file',
            'active' => request()->routeIs('admin.contracts*'),
            'items' => [
                ['label' => 'Tạo hợp đồng thuê', 'route' => 'admin.contracts.create', 'active' => request()->routeIs('admin.contracts.create')],
                ['label' => 'Danh sách hợp đồng', 'route' => 'admin.contracts.index', 'active' => request()->routeIs('admin.contracts.index')],
                ['label' => 'Gia hạn hợp đồng', 'route' => 'admin.contracts.extend.list', 'active' => request()->routeIs('admin.contracts.extend*')],
                ['label' => 'Kết thúc hợp đồng', 'route' => 'admin.contracts.end.list', 'active' => request()->routeIs('admin.contracts.end*')],
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
            'active' => request()->routeIs('admin.invoices*'),
            'items' => [
                ['label' => 'Sinh hóa đơn', 'route' => 'admin.invoices.generate', 'active' => request()->routeIs('admin.invoices.generate')],
                ['label' => 'Danh sách hóa đơn', 'route' => 'admin.invoices.index', 'active' => request()->routeIs('admin.invoices.index')],
                ['label' => 'Thanh toán', 'route' => 'admin.invoices.payments', 'active' => request()->routeIs('admin.invoices.payments')],
                ['label' => 'Xuất hóa đơn', 'route' => 'admin.invoices.export', 'active' => request()->routeIs('admin.invoices.export*')],
            ],
        ],
        [
            'label' => 'Hệ thống',
            'icon' => 'bx bx-cog',
            'active' => request()->routeIs('admin.users*') || request()->routeIs('admin.roles') || request()->routeIs('admin.settings*'),
            'items' => [
                ['label' => 'Tài khoản', 'route' => 'admin.users.index', 'active' => request()->routeIs('admin.users*')],
                ['label' => 'Phân quyền', 'route' => 'admin.roles', 'active' => request()->routeIs('admin.roles')],
                ['label' => 'Đơn giá điện', 'route' => 'admin.settings.edit', 'params' => ['type' => 'electricity'], 'active' => request()->fullUrlIs(route('admin.settings.edit', ['type' => 'electricity']))],
                ['label' => 'Đơn giá nước', 'route' => 'admin.settings.edit', 'params' => ['type' => 'water'], 'active' => request()->fullUrlIs(route('admin.settings.edit', ['type' => 'water']))],
                ['label' => 'Đơn giá internet', 'route' => 'admin.settings.edit', 'params' => ['type' => 'internet'], 'active' => request()->fullUrlIs(route('admin.settings.edit', ['type' => 'internet']))],
                ['label' => 'Đơn giá dịch vụ', 'route' => 'admin.settings.edit', 'params' => ['type' => 'service'], 'active' => request()->fullUrlIs(route('admin.settings.edit', ['type' => 'service']))],
            ],
        ],
    ];
@endphp

<aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0">
    <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5">
        <a href="{{ route('admin.home') }}" class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-xl font-bold text-white">S</span>
            <span>
                <span class="block text-sm font-bold text-slate-900">Stay Master</span>
                <span class="block text-xs text-slate-500">Quản lý phòng trọ</span>
            </span>
        </a>

<<<<<<< HEAD
                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="grid"></i>
                        <span>Quản lý Phòng</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li> <a href="{{ route('admin.rooms.index') }}" class="nav-link">
                                <span>Danh sách phòng</span>
                            </a></li>
                        <li> <a href="{{ route('admin.rooms.create') }}" class="nav-link">
                                <span>Thêm phòng mới</span>
                            </a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="users"></i>
                        <span>Khách thuê</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li> <a href="{{ route('admin.tenants.index') }}" class="nav-link">
                                <span>Danh sách khách thuê</span>
                            </a></li>
                        <li> <a href="{{ route('admin.tenants.create') }}" class="nav-link">
                                <span>Thêm khách thuê</span>
                            </a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="file-text"></i>
                        <span>Hợp đồng</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li>
                            <a href="{{ route('admin.contracts.index') }}">
                                Danh sách hợp đồng
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                Mẫu hợp đồng
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="zap"></i>
                        <span>Điện nước & Dịch vụ</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li><a href="{{ route('admin.utilities.create') }}">Nhập chỉ số điện/nước</a></li>
                        <li><a href="{{ route('admin.utilities.index') }}">Kiểm tra chỉ số</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="credit-card"></i>
                        <span>Hóa đơn & Công nợ</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li><a href="{{ route('admin.invoices.generate') }}">Sinh hóa đơn</a></li>
                        <li><a href="{{ route('admin.invoices.index') }}">Danh sách hóa đơn</a></li>
                        <li><a href="{{ route('admin.invoices.payments') }}">Ghi nhận thanh toán</a></li>
                        <li><a href="{{ route('admin.invoices.index', ['status' => 'unpaid']) }}">Theo dõi công nợ</a>
                        </li>
                        <li><a href="#">Thanh toán hóa đơn</a></li>
                        <li><a href="#">Theo dõi công nợ</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="download"></i>
                        <span>Báo cáo & Xuất file</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li><a href="{{ route('admin.rooms.export') }}">Xuất danh sách phòng</a></li>
                        <li><a href="{{ route('admin.tenants.export') }}">Xuất danh sách khách thuê</a></li>
                        <li><a href="{{ route('admin.invoices.export') }}">Xuất danh sách hóa đơn</a></li>
                        <li><a href="{{ route('admin.invoices.payments.export') }}">Xuất danh sách thanh toán</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="settings"></i>
                        <span>Hệ thống & Cài đặt</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li><a href="{{ route('admin.users.index') }}">Quản lý tài khoản Admin/User</a></li>
                        <li><a href="{{ route('admin.roles') }}">Phân quyền</a></li>
                        <li><a href="{{ route('admin.settings.edit', ['type' => 'electricity']) }}">Cập nhật đơn giá
                                điện</a></li>
                        <li><a href="{{ route('admin.settings.edit', ['type' => 'water']) }}">Cập nhật đơn giá nước</a>
                        </li>
                        <li><a href="{{ route('admin.settings.edit', ['type' => 'internet']) }}">Cập nhật đơn giá
                                internet</a></li>
                        <li><a href="{{ route('admin.settings.edit', ['type' => 'service']) }}">Cập nhật đơn giá dịch
                                vụ</a></li>
                    </ul>
                </li>
            </ul>
        </div>
=======
        <button id="admin-sidebar-close" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden">
            <i class="bx bx-x text-2xl"></i>
        </button>
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225
    </div>

    <nav class="h-[calc(100vh-4rem)] overflow-y-auto px-4 py-5">
        <p class="px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Menu chính</p>

        <div class="mt-3 space-y-2">
            @foreach ($navGroups as $group)
                <details class="group rounded-lg" {{ $group['active'] ? 'open' : '' }}>
                    <summary class="flex cursor-pointer list-none items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold {{ $group['active'] ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <i class="{{ $group['icon'] }} text-xl"></i>
                        <span class="flex-1">{{ $group['label'] }}</span>
                        <i class="bx bx-chevron-down text-lg transition-transform group-open:rotate-180"></i>
                    </summary>

                    <div class="mt-1 space-y-1 pl-10">
                        @foreach ($group['items'] as $item)
                            <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="block rounded-lg px-3 py-2 text-sm {{ $item['active'] ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </div>
    </nav>
</aside>
