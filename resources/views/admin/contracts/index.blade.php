@extends('layouts.admin.index')

@section('title', 'Danh sách hợp đồng | Quản lý phòng trọ')
@section('page_title', 'Danh sách hợp đồng')

@section('content')
    <div class="space-y-6">
        @include('admin.contracts.partials.workspace-nav')

        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <h2 class="text-2xl font-bold text-slate-950">Danh sách hợp đồng</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.contracts.extend.list') }}" class="inline-flex items-center gap-2 rounded-lg border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-700 shadow-sm hover:bg-sky-100">
                    <i class="bx bx-calendar-plus text-lg"></i>
                    Gia hạn hợp đồng
                </a>
                <a href="{{ route('admin.contracts.end.list') }}" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm hover:bg-rose-100">
                    <i class="bx bx-log-out-circle text-lg"></i>
                    Kết thúc hợp đồng
                </a>
                <a href="{{ route('admin.contracts.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-plus text-lg"></i>
                    Tạo hợp đồng mới
                </a>
            </div>
        </div>

        <section data-contract-filter class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.contracts.index') }}" class="grid gap-3 md:grid-cols-[minmax(240px,0.75fr)_minmax(220px,0.25fr)] md:items-end">
                <div>
                    <label for="keyword" class="mb-1.5 block text-sm font-semibold text-slate-700">Tìm kiếm</label>
                    <div class="relative">
                        <i class="bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                        <input id="keyword" data-contract-search type="text" name="keyword" value="{{ request('keyword') }}" maxlength="100" autocomplete="off" placeholder="Mã HĐ, người thuê, số điện thoại hoặc số phòng..." class="h-11 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    </div>
                </div>

                <div>
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Trạng thái</label>
                    <select id="status" data-contract-status name="status" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Tất cả trạng thái</option>
                        <option value="draft" @selected(request('status') === 'draft')>Bản nháp</option>
                        <option value="pending_signature" @selected(request('status') === 'pending_signature')>Chờ ký</option>
                        <option value="pending_deposit" @selected(request('status') === 'pending_deposit')>Chờ tiền cọc</option>
                        <option value="awaiting_move_in" @selected(request('status') === 'awaiting_move_in')>Chờ nhận phòng</option>
                        <option value="active" @selected(request('status') === 'active')>Đang thuê</option>
                        <option value="expired" @selected(request('status') === 'expired')>Hết hạn - chờ xử lý</option>
                        <option value="settling" @selected(request('status') === 'settling')>Đang quyết toán</option>
                        <option value="completed" @selected(request('status') === 'completed')>Hoàn tất</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Đã hủy</option>
                    </select>
                </div>
            </form>
        </section>

        <div data-contract-filter-error class="hidden rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">Không thể tải danh sách hợp đồng. Vui lòng thử lại.</div>
        <div data-contract-results class="transition-opacity">
            @include('admin.contracts.partials.results', ['contracts' => $contracts])
        </div>
    </div>
@endsection
