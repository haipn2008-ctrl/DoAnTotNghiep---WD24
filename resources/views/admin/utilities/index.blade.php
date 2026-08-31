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
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.utilities.create', ['month' => $month, 'year' => $year, 'mode' => 'checkpoint']) }}" class="inline-flex items-center gap-2 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-100">
                        <i class="bx bx-map-pin text-lg"></i>Ghi mốc giữa kỳ
                    </a>
                    <a href="{{ route('admin.utilities.create', ['month' => $month, 'year' => $year]) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        <i class="bx bx-plus text-lg"></i>Nhập chỉ số
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Phòng</th>
                            <th class="px-5 py-3 text-center">Điện cũ</th>
                            <th class="px-5 py-3 text-center">Điện mới</th>
                            <th class="px-5 py-3 text-center">Ảnh điện</th>
                            <th class="px-5 py-3 text-center">Dùng điện</th>
                            <th class="px-5 py-3 text-right">Tiền điện</th>
                            <th class="px-5 py-3 text-center">Nước cũ</th>
                            <th class="px-5 py-3 text-center">Nước mới</th>
                            <th class="px-5 py-3 text-center">Ảnh nước</th>
                            <th class="px-5 py-3 text-center">Dùng nước</th>
                            <th class="px-5 py-3 text-right">Tiền nước</th>
                            <th class="px-5 py-3 text-right">Tổng</th>
                            <th class="px-5 py-3 text-center">Trạng thái</th>
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
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950">{{ $item->room->room_code ?? 'Phòng trống' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Ngày thuê: {{ $startDate }}</p>
                                </td>
                                <td class="px-5 py-4 text-center text-slate-600">{{ $item->electricity_old }}</td>
                                <td class="px-5 py-4 text-center font-semibold text-indigo-700">{{ $item->electricity_new }}</td>
                                <td class="px-5 py-4 text-center">
                                    @if ($item->meterImageExists('electricity'))
                                        <a href="{{ route('admin.utilities.image', [$item, 'electricity']) }}" data-image-modal data-image-title="Ảnh đồng hồ điện">
                                            <img src="{{ route('admin.utilities.image', [$item, 'electricity']) }}" alt="Ảnh đồng hồ điện" class="mx-auto h-14 w-14 rounded-lg object-cover ring-1 ring-slate-200">
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">Chưa có</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center font-semibold text-emerald-700">{{ $dienDung }} kWh</td>
                                <td class="px-5 py-4 text-right font-semibold text-indigo-700">{{ number_format($tienDien, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-center text-slate-600">{{ $item->water_old }}</td>
                                <td class="px-5 py-4 text-center font-semibold text-sky-700">{{ $item->water_new }}</td>
                                <td class="px-5 py-4 text-center">
                                    @if ($item->meterImageExists('water'))
                                        <a href="{{ route('admin.utilities.image', [$item, 'water']) }}" data-image-modal data-image-title="Ảnh đồng hồ nước">
                                            <img src="{{ route('admin.utilities.image', [$item, 'water']) }}" alt="Ảnh đồng hồ nước" class="mx-auto h-14 w-14 rounded-lg object-cover ring-1 ring-slate-200">
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">Chưa có</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center font-semibold text-emerald-700">{{ $nuocDung }} khối</td>
                                <td class="px-5 py-4 text-right font-semibold text-sky-700">{{ number_format($tienNuoc, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-right font-bold text-emerald-700">{{ number_format($tongDienNuoc, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-center">
                                    @if($item->isLocked())
                                        <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">Đã khóa</span>
                                    @elseif($item->isConfirmed())
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Đã xác nhận</span>
                                        <form action="{{ route('admin.utilities.reopen', $item) }}" method="POST" class="mt-2">@csrf<button class="text-xs font-semibold text-amber-700 hover:underline">Mở lại để sửa</button></form>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Bản nháp</span>
                                        <form action="{{ route('admin.utilities.confirm', $item) }}" method="POST" class="mt-2">@csrf<button class="text-xs font-semibold text-indigo-700 hover:underline">Xác nhận chỉ số</button></form>
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
                                        <details class="mt-3 text-left">
                                            <summary class="cursor-pointer text-xs font-semibold text-slate-600 hover:text-indigo-700">Lịch sử thao tác ({{ $item->histories->count() }})</summary>
                                            <ol class="mt-2 space-y-2 border-l border-slate-200 pl-3">
                                                @foreach($item->histories->sortByDesc('performed_at') as $history)
                                                    <li class="text-xs leading-5 text-slate-600">
                                                        <span class="font-semibold text-slate-800">{{ $historyLabels[$history->action] ?? $history->action }}</span>
                                                        <span class="block">{{ $history->actor?->name ?? 'Hệ thống' }} · {{ $history->performed_at?->format('H:i d/m/Y') }}</span>
                                                    </li>
                                                @endforeach
                                            </ol>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-5 py-12 text-center text-slate-500">
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
                    <p class="text-sm text-slate-500">Dùng để chia giai đoạn sử dụng; không tự tạo hóa đơn hoặc khóa kỳ.</p>
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
