@extends('layouts.admin.index')

@section('title', 'Kiểm tra chỉ số điện nước | Quản lý phòng trọ')
@section('page_title', 'Kiểm tra chỉ số điện nước')

@section('content')
    <div class="space-y-6">
        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Chưa thể thay đổi trạng thái chỉ số.</p>
                <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Điện nước và dịch vụ</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Kiểm tra chỉ số điện/nước</h2>
            </div>

            <form action="{{ route('admin.utilities.index') }}" method="GET" class="flex flex-wrap items-end gap-2">
                <div>
                    <label for="month" class="mb-1.5 block text-sm font-semibold text-slate-700">Tháng</label>
                    <select id="month" name="month" onchange="this.form.submit()" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($month == $m)>Tháng {{ $m }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="year" class="mb-1.5 block text-sm font-semibold text-slate-700">Năm</label>
                    <select id="year" name="year" onchange="this.form.submit()" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        @for ($y = date('Y'); $y >= date('Y') - 2; $y--)
                            <option value="{{ $y }}" @selected($year == $y)>Năm {{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tổng điện tiêu thụ</p>
                <p class="mt-3 text-3xl font-bold text-indigo-700">{{ number_format($totalElectricity) }} <span class="text-base text-slate-500">kWh</span></p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tổng nước tiêu thụ</p>
                <p class="mt-3 text-3xl font-bold text-sky-700">{{ number_format($totalWater) }} <span class="text-base text-slate-500">khối</span></p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tiến độ chốt số</p>
                <p class="mt-3 text-3xl font-bold {{ $roomsRead < $totalRooms ? 'text-amber-700' : 'text-emerald-700' }}">{{ $roomsRead }} / {{ $totalRooms }} <span class="text-base text-slate-500">phòng</span></p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tiền điện</p>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($setting->electric_price, 0, ',', '.') }}đ/kWh</p>
                <p class="mt-3 text-2xl font-bold text-indigo-700">{{ number_format($totalElectricityFee, 0, ',', '.') }}đ</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tiền nước</p>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($setting->water_price, 0, ',', '.') }}đ/khối</p>
                <p class="mt-3 text-2xl font-bold text-sky-700">{{ number_format($totalWaterFee, 0, ',', '.') }}đ</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tổng tiền điện nước</p>
                <p class="mt-3 text-2xl font-bold text-emerald-700">{{ number_format($totalUtilityFee, 0, ',', '.') }}đ</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center">
                <div>
                    <h3 class="font-semibold text-slate-950">Chi tiết các phòng đã nhập</h3>
                    <p class="text-sm text-slate-500">Tháng {{ $month }}/{{ $year }}</p>
                </div>
                <div class="grid w-full gap-2 sm:w-auto sm:grid-cols-2">
                    <a href="{{ route('admin.utilities.create', ['month' => $month, 'year' => $year, 'mode' => 'checkpoint']) }}" class="group inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-sky-200 bg-white px-4 text-sm font-semibold text-sky-700 shadow-sm transition hover:border-sky-300 hover:bg-sky-50">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-sky-100 transition group-hover:bg-sky-200"><i class="bx bx-map-pin text-lg"></i></span>
                        Ghi mốc giữa kỳ
                    </a>
                    <a href="{{ route('admin.utilities.create', ['month' => $month, 'year' => $year]) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 hover:shadow-md">
                        <i class="bx bx-plus-circle text-xl"></i>Nhập chỉ số
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] table-fixed divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="w-[14%] px-4 py-3">Phòng</th>
                            <th class="w-[23%] px-3 py-3">Điện</th>
                            <th class="w-[23%] px-3 py-3">Nước</th>
                            <th class="w-[14%] px-3 py-3 text-right">Tổng tiền</th>
                            <th class="w-[15%] px-3 py-3 text-center">Trạng thái</th>
                            <th class="w-[11%] px-3 py-3 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($readings as $item)
                            @php
                                $dienDung = $item->electricity_new - $item->electricity_old;
                                $nuocDung = $item->water_new - $item->water_old;
                                $tienDien = $dienDung * $setting->electric_price;
                                $tienNuoc = $nuocDung * $setting->water_price;
                                $tongDienNuoc = $tienDien + $tienNuoc;
                                $activeContract = $item->room ? $item->room->contracts->first() : null;
                                $startDate = $activeContract && $activeContract->start_date
                                    ? \Carbon\Carbon::parse($activeContract->start_date)->format('d/m/Y')
                                    : 'Không có';
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-4 align-top">
                                    <p class="font-semibold text-slate-950">{{ $item->room->room_code ?? 'Phòng trống' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Ngày thuê: {{ $startDate }}</p>
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <div class="rounded-lg border border-indigo-100 bg-indigo-50/40 p-3">
                                        <div class="flex items-center justify-between gap-2 text-xs text-slate-500">
                                            <span>{{ $item->electricity_old }}</span><i class="bx bx-right-arrow-alt text-base text-slate-400"></i>
                                            <span class="font-bold text-indigo-700">{{ $item->electricity_new }}</span>
                                        </div>
                                        <div class="mt-2 flex items-end justify-between gap-2">
                                            <span class="font-semibold text-emerald-700">{{ $dienDung }} kWh</span>
                                            <span class="text-xs font-semibold text-indigo-700">{{ number_format($tienDien, 0, ',', '.') }}đ</span>
                                        </div>
                                        @if ($item->meterImageExists('electricity'))
                                            <a href="{{ route('admin.utilities.image', [$item, 'electricity']) }}" data-image-modal data-image-title="Ảnh đồng hồ điện" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800"><i class="bx bx-image-alt"></i>Xem ảnh điện</a>
                                        @else
                                            <span class="mt-2 inline-flex items-center gap-1 text-xs text-slate-400"><i class="bx bx-image"></i>Chưa có ảnh</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <div class="rounded-lg border border-sky-100 bg-sky-50/40 p-3">
                                        <div class="flex items-center justify-between gap-2 text-xs text-slate-500">
                                            <span>{{ $item->water_old }}</span><i class="bx bx-right-arrow-alt text-base text-slate-400"></i>
                                            <span class="font-bold text-sky-700">{{ $item->water_new }}</span>
                                        </div>
                                        <div class="mt-2 flex items-end justify-between gap-2">
                                            <span class="font-semibold text-emerald-700">{{ $nuocDung }} khối</span>
                                            <span class="text-xs font-semibold text-sky-700">{{ number_format($tienNuoc, 0, ',', '.') }}đ</span>
                                        </div>
                                        @if ($item->meterImageExists('water'))
                                            <a href="{{ route('admin.utilities.image', [$item, 'water']) }}" data-image-modal data-image-title="Ảnh đồng hồ nước" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-sky-600 hover:text-sky-800"><i class="bx bx-image-alt"></i>Xem ảnh nước</a>
                                        @else
                                            <span class="mt-2 inline-flex items-center gap-1 text-xs text-slate-400"><i class="bx bx-image"></i>Chưa có ảnh</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-right align-top">
                                    <p class="font-bold text-emerald-700">{{ number_format($tongDienNuoc, 0, ',', '.') }}đ</p>
                                    <p class="mt-1 text-[11px] leading-4 text-slate-400">Điện + nước</p>
                                </td>
                                <td class="px-3 py-4 text-center align-top">
                                    @if($item->isLocked())
                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200"><span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>Đã khóa</span>
                                    @elseif($item->isConfirmed())
                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Đã xác nhận</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Bản nháp</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <div class="relative flex items-center justify-center gap-2">
                                        @if($item->isConfirmed())
                                            <form action="{{ route('admin.utilities.reopen', $item) }}" method="POST">@csrf
                                                <button data-keep-action-label aria-label="Mở lại để sửa" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 p-0 text-amber-700 transition hover:border-amber-300 hover:bg-amber-100 hover:text-amber-900" title="Mở lại để sửa">
                                                    <i class="bx bx-edit-alt text-lg"></i>
                                                </button>
                                            </form>
                                        @elseif(!$item->isLocked())
                                            <form action="{{ route('admin.utilities.confirm', $item) }}" method="POST">@csrf
                                                <button data-keep-action-label aria-label="Xác nhận chỉ số" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 p-0 text-white shadow-sm transition hover:bg-indigo-700 hover:shadow-md" title="Xác nhận chỉ số">
                                                    <i class="bx bx-check-circle text-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @if($item->histories->isNotEmpty())
                                        @php
                                            $historyLabels = [
                                                'draft_created' => 'Tạo bản nháp',
                                                'draft_updated' => 'Cập nhật bản nháp',
                                                'created_and_confirmed' => 'Nhập và xác nhận',
                                                'confirmed' => 'Xác nhận chỉ số',
                                                'reopened' => 'Mở lại để sửa',
                                                'checkpoint_recorded' => 'Ghi mốc giữa kỳ',
                                            ];
                                        @endphp
                                        <button type="button" data-keep-action-label data-utility-history-open="utility-history-{{ $item->id }}" title="Lịch sử thao tác" aria-label="Lịch sử thao tác ({{ $item->histories->count() }})" class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white p-0 text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                                <i class="bx bx-history text-lg"></i>
                                                <span class="absolute -right-1.5 -top-1.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-indigo-600 px-1 text-[9px] font-bold leading-none text-white ring-2 ring-white">{{ $item->histories->count() }}</span>
                                        </button>
                                        <div id="utility-history-{{ $item->id }}" data-utility-history-modal class="fixed inset-0 z-[80] hidden items-center justify-center p-4">
                                            <button type="button" data-utility-history-close class="absolute inset-0 bg-slate-950/45 backdrop-blur-[1px]" aria-label="Đóng lịch sử"></button>
                                            <div class="relative z-10 w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white text-left shadow-2xl">
                                                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                                                    <div>
                                                        <h4 class="font-bold text-slate-950">Lịch sử thao tác</h4>
                                                        <p class="mt-0.5 text-xs text-slate-500">Phòng {{ $item->room->room_code ?? 'trống' }} · Tháng {{ $month }}/{{ $year }}</p>
                                                    </div>
                                                    <button type="button" data-utility-history-close class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" aria-label="Đóng"><i class="bx bx-x text-2xl"></i></button>
                                                </div>
                                                <ol class="max-h-80 divide-y divide-slate-100 overflow-y-auto px-5">
                                                    @foreach($item->histories->sortByDesc('performed_at') as $history)
                                                        <li class="flex gap-3 py-3.5 text-sm">
                                                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600"><i class="bx bx-history"></i></span>
                                                            <div class="min-w-0">
                                                                <p class="font-semibold text-slate-800">{{ $historyLabels[$history->action] ?? $history->action }}</p>
                                                                <p class="mt-1 text-xs text-slate-500">{{ $history->actor?->name ?? 'Hệ thống' }} · {{ $history->performed_at?->format('H:i d/m/Y') }}</p>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ol>
                                            </div>
                                        </div>
                                    @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                    Chưa có dữ liệu chốt số cho tháng {{ $month }}/{{ $year }}.
                                    <div class="mt-3">
                                        <a href="{{ route('admin.utilities.create', ['month' => $month, 'year' => $year]) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                            <i class="bx bx-plus text-lg"></i>
                                            Nhập số ngay
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-sky-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-3 border-b border-sky-100 bg-sky-50/60 px-5 py-4 sm:flex-row sm:items-center">
                <div>
                    <h3 class="font-semibold text-slate-950">Mốc đối soát giữa kỳ</h3>
                </div>
                <span class="inline-flex w-fit rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-sky-700 ring-1 ring-sky-200">{{ $checkpoints->count() }} mốc</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Ngày ghi mốc</th>
                            <th class="px-5 py-3">Phòng</th>
                            <th class="px-5 py-3 text-center">Điện</th>
                            <th class="px-5 py-3 text-center">Nước</th>
                            <th class="px-5 py-3">Người thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($checkpoints as $checkpoint)
                            @php($checkpointHistory = $checkpoint->histories->last())
                            <tr>
                                <td class="px-5 py-4 font-semibold text-slate-950">{{ $checkpoint->record_date?->format('d/m/Y') }}</td>
                                <td class="px-5 py-4"><span class="font-semibold text-slate-950">{{ $checkpoint->room?->room_code ?? '—' }}</span><span class="mt-1 block text-xs text-slate-500">{{ $checkpoint->contract?->contract_code ?? '—' }}</span></td>
                                <td class="px-5 py-4 text-center"><span class="font-semibold text-indigo-700">{{ $checkpoint->electricity_new }}</span><span class="block text-xs text-slate-500">+{{ $checkpoint->electricity_usage }} kWh từ mốc trước</span></td>
                                <td class="px-5 py-4 text-center"><span class="font-semibold text-sky-700">{{ $checkpoint->water_new }}</span><span class="block text-xs text-slate-500">+{{ $checkpoint->water_usage }} m³ từ mốc trước</span></td>
                                <td class="px-5 py-4"><span class="font-medium text-slate-800">{{ $checkpointHistory?->actor?->name ?? 'Hệ thống' }}</span><span class="mt-1 block text-xs text-slate-500">{{ $checkpointHistory?->performed_at?->format('H:i d/m/Y') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Chưa có mốc giữa kỳ trong tháng {{ $month }}/{{ $year }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
