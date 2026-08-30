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
                <a data-tenant-export href="{{ route('admin.tenants.export', array_filter(['search' => $search, 'status' => $status])) }}" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                    <i class="bx bx-download text-lg"></i>
                    Xuất danh sách
                </a>
            </div>
        </div>

        <section data-tenant-filter class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.tenants.index') }}" class="grid gap-3 md:grid-cols-[minmax(240px,0.75fr)_minmax(200px,0.25fr)] md:items-end">
                <div>
                    <label for="search" class="mb-1.5 block text-sm font-semibold text-slate-700">Tìm kiếm</label>
                    <div class="relative">
                        <i class="bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                        <input id="search" data-tenant-search name="search" value="{{ $search }}" maxlength="255" autocomplete="off" placeholder="Họ tên, CCCD, số điện thoại hoặc email" class="h-11 w-full rounded-lg border border-slate-200 pl-10 pr-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    </div>
                </div>
                <div>
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Trạng thái thuê</label>
                    <select id="status" data-tenant-status name="status" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Tất cả trạng thái</option>
                        <option value="renting" @selected($status === 'renting')>Đang thuê</option>
                        <option value="moved_out" @selected($status === 'moved_out')>Đã rời phòng</option>
                        <option value="not_renting" @selected($status === 'not_renting')>Chưa thuê</option>
                        <option value="archived" @selected($status === 'archived')>Đã lưu trữ</option>
                    </select>
                </div>
            </form>
        </section>

        <div data-tenant-filter-error class="hidden rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">Không thể tải danh sách. Vui lòng thử lại.</div>
        <div data-tenant-results class="transition-opacity">
            @include('admin.tenants.partials.results', ['tenants' => $tenants])
        </div>
    </div>
@endsection
