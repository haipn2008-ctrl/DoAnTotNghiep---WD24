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
            <form method="GET" class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                <div>
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Tìm kiếm</label>
                    <input id="search" type="text" name="search" value="{{ $search }}" placeholder="Tên hoặc email" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                </div>
                <button class="inline-flex h-11 items-center gap-2 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">
                    <i class="bx bx-search text-lg"></i>
                    Tìm
                </button>
            </form>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Người dùng</th>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3">Vai trò</th>
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
                                <td class="px-5 py-4 text-slate-600">{{ optional($user->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="Sửa">
                                            <i class="bx bx-edit text-lg"></i>
                                        </a>
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Xác nhận xóa tài khoản này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100" title="Xóa">
                                                <i class="bx bx-trash text-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-slate-500">Chưa có tài khoản nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex justify-end">{{ $users->links() }}</div>
    </div>
@endsection
