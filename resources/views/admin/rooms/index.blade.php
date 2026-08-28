@extends('layouts.admin.index')

@section('title', 'Danh sách phòng | Quản lý phòng trọ')
@section('page_title', 'Danh sách phòng')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Quản lý phòng trọ</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Danh sách phòng</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a data-room-export href="{{ route('admin.rooms.export', request()->only(['room_code', 'status'])) }}" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                    <i class="bx bx-download text-lg"></i>
                    Xuất danh sách
                </a>
                <a href="{{ route('admin.rooms.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-plus text-lg"></i>
                    Thêm phòng mới
                </a>
            </div>
        </div>

        <section data-room-filter class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.rooms.index') }}" class="grid gap-3 md:grid-cols-[minmax(240px,0.75fr)_minmax(200px,0.25fr)] md:items-end">
                <div>
                    <label for="room_code" class="mb-1.5 block text-sm font-semibold text-slate-700">Mã phòng</label>
                    <div class="relative">
                        <i class="bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                        <input id="room_code" data-room-search type="text" name="room_code" value="{{ request('room_code') }}" maxlength="50" autocomplete="off" placeholder="Nhập mã phòng..." class="h-11 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    </div>
                </div>

                <div>
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Trạng thái</label>
                    <select id="status" data-room-status name="status" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Tất cả trạng thái</option>
                        <option value="available" @selected(request('status') === 'available')>Trống</option>
                        <option value="occupied" @selected(request('status') === 'occupied')>Đang thuê</option>
                        <option value="maintenance" @selected(request('status') === 'maintenance')>Bảo trì</option>
                        <option value="retired" @selected(request('status') === 'retired')>Ngừng khai thác</option>
                    </select>
                </div>
            </form>
        </section>

        <div data-room-filter-error class="hidden rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">Không thể tải danh sách phòng. Vui lòng thử lại.</div>
        <div data-room-results class="transition-opacity">
            @include('admin.rooms.partials.results', ['rooms' => $rooms])
        </div>
    </div>
@endsection
