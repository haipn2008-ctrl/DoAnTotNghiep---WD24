@extends('layouts.admin.index')

@section('title', 'Quản lý người dùng | Quản lý phòng trọ')
@section('page_title', 'Quản lý người dùng')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Hệ thống và cài đặt</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Danh sách người dùng</h2>
            </div>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                <i class="bx bx-plus text-lg"></i>
                Thêm tài khoản
            </a>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.users.index') }}" data-user-search-form class="grid grid-cols-2 items-end gap-3 md:grid-cols-[minmax(260px,1fr)_minmax(210px,280px)]">
                <div>
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Tìm kiếm</label>
                    <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Tên hoặc email" autocomplete="off" data-user-search-input class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                </div>
                <div>
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Trạng thái tài khoản</label>
                    <select id="status" name="status" onchange="this.form.requestSubmit()" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" @selected($status === 'pending')>Chờ kích hoạt</option>
                        <option value="active" @selected($status === 'active')>Đang hoạt động</option>
                        <option value="settling" @selected($status === 'settling')>Đang quyết toán</option>
                        <option value="former" @selected($status === 'former')>Cựu người thuê</option>
                        <option value="locked" @selected($status === 'locked')>Đã khóa</option>
                        <option value="inactive" @selected($status === 'inactive')>Ngừng sử dụng</option>
                    </select>
                </div>
            </form>
        </section>

        <div id="user-list-results" aria-live="polite">
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Người dùng</th>
                                <th class="px-5 py-3">Email</th>
                                <th class="px-5 py-3">Vai trò</th>
                                <th class="px-5 py-3">Trạng thái</th>
                                <th class="px-5 py-3">Thời gian tạo</th>
                                <th class="px-5 py-3 text-right">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-700 ring-1 ring-indigo-100">
                                            {{ mb_substr($user->name ?? 'U', 0, 1) }}
                                        </div>
                                        <p class="font-semibold text-slate-950">{{ $user->name }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $user->email }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $user->role->role_name ?? '-' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    @php
                                        $statusLabels = [
                                            'pending' => ['Chờ kích hoạt', 'bg-amber-50 text-amber-700 ring-amber-200'],
                                            'active' => ['Đang hoạt động', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
                                            'settling' => ['Đang quyết toán', 'bg-sky-50 text-sky-700 ring-sky-200'],
                                            'locked' => ['Đã khóa', 'bg-rose-50 text-rose-700 ring-rose-200'],
                                            'former' => ['Cựu người thuê', 'bg-sky-50 text-sky-700 ring-sky-200'],
                                            'inactive' => ['Ngừng sử dụng', 'bg-slate-100 text-slate-600 ring-slate-200'],
                                        ];
                                        $accountStatus = $statusLabels[$user->status] ?? ['Không xác định', 'bg-slate-100 text-slate-600 ring-slate-200'];
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $accountStatus[1] }}">{{ $accountStatus[0] }}</span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ optional($user->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="Sửa">
                                            <i class="bx bx-edit text-lg"></i>
                                        </a>
                                        @if ($user->status === \App\Models\User::STATUS_INACTIVE)
                                            <form action="{{ route('admin.users.restore', $user) }}" method="POST" data-confirm="Tài khoản sẽ được khôi phục và có thể sử dụng lại hệ thống." data-confirm-label="Khôi phục tài khoản" data-reason-input="reactivation_reason" data-reason-placeholder="Nhập lý do khôi phục tài khoản...">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="reactivation_reason">
                                                <button class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100" title="Khôi phục tài khoản">
                                                    <i class="bx bx-user-check text-lg"></i>
                                                </button>
                                            </form>
                                        @elseif (! auth()->user()->is($user))
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" data-confirm="Tài khoản sẽ ngừng hoạt động nhưng toàn bộ lịch sử vẫn được giữ lại." data-confirm-label="Ngừng sử dụng" data-reason-input="deactivation_reason" data-reason-placeholder="Nhập lý do ngừng sử dụng tài khoản...">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="deactivation_reason">
                                                <button class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100" title="Ngừng sử dụng">
                                                    <i class="bx bx-user-x text-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-slate-500">Chưa có tài khoản nào.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-6 flex justify-end">{{ $users->links() }}</div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-user-search-form]');
            const searchInput = form?.querySelector('[data-user-search-input]');

            if (!form || !searchInput) {
                return;
            }

            let debounceTimer;
            let activeRequest;

            const updateUserList = async () => {
                const url = new URL(form.action, window.location.origin);

                new FormData(form).forEach((value, key) => {
                    const normalizedValue = typeof value === 'string' ? value.trim() : value;

                    if (normalizedValue) {
                        url.searchParams.set(key, normalizedValue);
                    }
                });

                activeRequest?.abort();
                const request = new AbortController();
                activeRequest = request;

                const currentResults = document.getElementById('user-list-results');
                currentResults?.setAttribute('aria-busy', 'true');

                try {
                    const response = await fetch(url, {
                        headers: {
                            Accept: 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: request.signal,
                    });

                    if (!response.ok) {
                        throw new Error('Không thể tải danh sách người dùng.');
                    }

                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const nextResults = page.getElementById('user-list-results');

                    if (!nextResults) {
                        throw new Error('Không tìm thấy danh sách người dùng.');
                    }

                    currentResults?.replaceWith(nextResults);
                    window.history.replaceState({}, '', url);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        window.location.assign(url);
                    }
                } finally {
                    if (activeRequest === request) {
                        document.getElementById('user-list-results')?.removeAttribute('aria-busy');
                        activeRequest = undefined;
                    }
                }
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                window.clearTimeout(debounceTimer);
                updateUserList();
            });

            searchInput.addEventListener('input', () => {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(updateUserList, 250);
            });
        });
    </script>
@endpush
