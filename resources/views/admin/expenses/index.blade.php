@extends('layouts.admin.index')

@section('title', 'Quản lý chi phí | Quản lý phòng trọ')
@section('page_title', 'Quản lý chi phí')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Quản lý dòng tiền chi</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Danh sách phiếu chi</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.profit-loss.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-pie-chart-alt-2 text-lg text-indigo-600"></i>
                    Báo cáo Thu - Chi & Lợi nhuận
                </a>
                <a href="{{ route('admin.expenses.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-plus text-lg"></i>
                    Lập phiếu chi mới
                </a>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Tổng chi tháng này</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                        <i class="bx bx-trending-down text-xl"></i>
                    </span>
                </div>
                <p class="mt-4 text-2xl font-bold text-rose-700">{{ number_format($thisMonthExpenses, 0, ',', '.') }}đ</p>
                <p class="mt-1 text-xs text-slate-400">Tháng {{ now()->month }}/{{ now()->year }}</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Điện nước nộp NN (Tháng này)</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <i class="bx bx-bolt-circle text-xl"></i>
                    </span>
                </div>
                <p class="mt-4 text-2xl font-bold text-amber-700">{{ number_format($utilityExpensesThisMonth, 0, ',', '.') }}đ</p>
                <p class="mt-1 text-xs text-slate-400">Tiền điện + nước nộp hóa đơn tổng</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Sửa chữa & Bảo trì (Tháng này)</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                        <i class="bx bx-wrench text-xl"></i>
                    </span>
                </div>
                <p class="mt-4 text-2xl font-bold text-indigo-700">{{ number_format($maintenanceExpensesThisMonth, 0, ',', '.') }}đ</p>
                <p class="mt-1 text-xs text-slate-400">Khắc phục sự cố & hao hụt đồ dùng</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Tổng chi năm {{ now()->year }}</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-50 text-slate-600">
                        <i class="bx bx-calendar text-xl"></i>
                    </span>
                </div>
                <p class="mt-4 text-2xl font-bold text-slate-900">{{ number_format($thisYearExpenses, 0, ',', '.') }}đ</p>
                <p class="mt-1 text-xs text-slate-400">Lũy kế từ đầu năm đến nay</p>
            </div>
        </div>

        {{-- Search & Filters --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.expenses.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Tìm kiếm</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Mã, tên khoản chi, người nhận..." class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Danh mục</label>
                    <select name="category" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Tất cả danh mục</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Phòng</label>
                    <select name="room_id" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Tất cả phòng (hoặc chung)</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" @selected((string)request('room_id') === (string)$room->id)>Phòng {{ $room->room_code }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Tháng / Năm</label>
                    <div class="grid grid-cols-2 gap-2">
                        <select name="month" class="h-10 w-full rounded-lg border border-slate-200 px-2 text-sm focus:border-indigo-500 focus:outline-none">
                            <option value="">Tháng</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected((string)request('month') === (string)$m)>T{{ $m }}</option>
                            @endfor
                        </select>
                        <select name="year" class="h-10 w-full rounded-lg border border-slate-200 px-2 text-sm focus:border-indigo-500 focus:outline-none">
                            <option value="">Năm</option>
                            @for($y = now()->year; $y >= now()->year - 4; $y--)
                                <option value="{{ $y }}" @selected((string)request('year') === (string)$y)>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="h-10 flex-1 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">
                        <i class="bx bx-filter-alt mr-1"></i> Lọc
                    </button>
                    @if(request()->hasAny(['search', 'category', 'room_id', 'month', 'year']))
                        <a href="{{ route('admin.expenses.index') }}" class="flex h-10 items-center justify-center rounded-lg border border-slate-200 px-3 text-slate-600 hover:bg-slate-100" title="Xóa bộ lọc">
                            <i class="bx bx-reset text-lg"></i>
                        </a>
                    @endif
                </div>
            </form>

            @if(request()->hasAny(['search', 'category', 'room_id', 'month', 'year']))
                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500">
                    <span>Kết quả lọc: <strong>{{ $expenses->total() }}</strong> phiếu chi</span>
                    <span>Tổng tiền chi theo lọc: <strong class="text-rose-600">{{ number_format($filteredTotal, 0, ',', '.') }}đ</strong></span>
                </div>
            @endif
        </section>

        {{-- Table --}}
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3.5">Mã phiếu</th>
                            <th class="px-5 py-3.5">Khoản chi</th>
                            <th class="px-5 py-3.5">Danh mục</th>
                            <th class="px-5 py-3.5 text-right">Số tiền</th>
                            <th class="px-5 py-3.5">Ngày chi</th>
                            <th class="px-5 py-3.5">Phòng / Đối tượng</th>
                            <th class="px-5 py-3.5">Chứng từ</th>
                            <th class="px-5 py-3.5 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($expenses as $expense)
                            @php($badge = $expense->category_badge)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-5 py-4 font-mono font-medium text-indigo-600">
                                    {{ $expense->expense_code }}
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-900">{{ $expense->title }}</p>
                                    @if($expense->notes)
                                        <p class="mt-0.5 max-w-xs truncate text-xs text-slate-500" title="{{ $expense->notes }}">{{ $expense->notes }}</p>
                                    @endif
                                    @if($expense->support_request_id)
                                        <span class="mt-1 inline-flex items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-700">
                                            <i class="bx bx-wrench"></i> Sự cố #{{ $expense->support_request_id }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $badge['class'] }}">
                                        {{ $badge['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right font-bold text-rose-600">
                                    -{{ number_format($expense->amount, 0, ',', '.') }}đ
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    {{ $expense->expense_date->format('d/m/Y') }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($expense->room)
                                        <span class="font-medium text-slate-800">Phòng {{ $expense->room->room_code }}</span>
                                    @else
                                        <span class="text-xs text-slate-500">Toàn bộ nhà / Chung</span>
                                    @endif
                                    @if($expense->payer_name)
                                        <p class="text-xs text-slate-400">Đơn vị/Người nhận: {{ $expense->payer_name }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if($expense->receiptExists())
                                        <a href="{{ route('admin.expenses.receipt', $expense) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline">
                                            <i class="bx bx-image-alt text-base"></i> Xem hóa đơn
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">Không có</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.expenses.edit', $expense) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-indigo-600" title="Chỉnh sửa">
                                        <i class="bx bx-edit text-base"></i> Sửa
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="bx bx-receipt text-4xl text-slate-300"></i>
                                        <p class="mt-2 font-medium">Chưa có phiếu chi nào phù hợp.</p>
                                        <a href="{{ route('admin.expenses.create') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:underline">
                                            <i class="bx bx-plus"></i> Tạo phiếu chi đầu tiên
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($expenses->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $expenses->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection

