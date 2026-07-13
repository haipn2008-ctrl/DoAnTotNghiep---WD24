@extends('layouts.admin.index')

@section('title', 'Phân quyền | Quản lý phòng trọ')
@section('page_title', 'Phân quyền')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-slate-500">Hệ thống và cài đặt</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Phân quyền</h2>
            <p class="mt-2 text-sm text-slate-500">Danh sách vai trò hiện có trong hệ thống.</p>
        </div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Vai trò</th>
                            <th class="px-5 py-3">Mô tả</th>
                            <th class="px-5 py-3 text-right">Số tài khoản</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($roles as $role)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200">
                                        {{ $role->role_name }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    {{ $role->role_name === 'Admin' ? 'Toàn quyền quản trị hệ thống.' : 'Quyền truy cập người dùng/khách thuê.' }}
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-950">{{ $role->users()->count() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-12 text-center text-slate-500">Chưa có vai trò nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
