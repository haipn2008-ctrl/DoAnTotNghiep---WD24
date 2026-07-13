@extends('layouts.admin.index')

@section('title', 'Danh sách khách thuê | Quản lý phòng trọ')
@section('page_title', 'Danh sách khách thuê')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Quản lý khách thuê</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Danh sách khách thuê</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.tenants.export') }}" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                    <i class="bx bx-download text-lg"></i>
                    Xuất danh sách
                </a>
                <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-plus text-lg"></i>
                    Thêm khách thuê
                </a>
            </div>
        </div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-slate-950">Tất cả khách thuê</h3>
                    <p class="text-sm text-slate-500">Hiển thị {{ $tenants->count() }} khách thuê trên trang này</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Khách thuê</th>
                            <th class="px-5 py-3">CCCD</th>
                            <th class="px-5 py-3">Số điện thoại</th>
                            <th class="px-5 py-3">Phòng thuê</th>
                            <th class="px-5 py-3">Ngày tạo</th>
                            <th class="px-5 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($tenants as $tenant)
                            @php
                                $activeContract = $tenant->contracts
                                    ->where('status', 'active')
                                    ->first();
                            @endphp

                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-700 ring-1 ring-indigo-100">
                                            {{ mb_substr($tenant->full_name ?? 'K', 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-950">{{ $tenant->full_name }}</p>
                                            <p class="text-xs text-slate-500">{{ $tenant->email ?: 'Chưa có email' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $tenant->cccd }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $tenant->phone }}</td>
                                <td class="px-5 py-4">
                                    @if ($activeContract)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Phòng {{ $activeContract->room->room_code ?? 'N/A' }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Chưa thuê
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ optional($tenant->created_at)->format('d/m/Y') }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100" title="Xem chi tiết">
                                            <i class="bx bx-show text-lg"></i>
                                        </a>
                                        <a href="{{ route('admin.tenants.edit', $tenant) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="Chỉnh sửa">
                                            <i class="bx bx-edit text-lg"></i>
                                        </a>
                                        <form action="{{ route('admin.tenants.destroy', $tenant) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa khách thuê này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100" title="Xóa">
                                                <i class="bx bx-trash text-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <i class="bx bx-user text-2xl"></i>
                                    </div>
                                    <p class="mt-3 font-semibold text-slate-900">Chưa có khách thuê nào</p>
                                    <p class="mt-1 text-sm text-slate-500">Thêm khách thuê để bắt đầu tạo hợp đồng.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex justify-end">
            {{ $tenants->withQueryString()->links() }}
        </div>
    </div>
@endsection
