@extends('layouts.admin.index')

@section('title', 'Nhập chỉ số điện nước | Quản lý phòng trọ')
@section('page_title', 'Nhập chỉ số điện nước')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Ghi đồng hồ theo từng phòng, lưu đến đâu chắc đến đó</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Kỳ tháng {{ $month }}/{{ $year }}</h2>
            </div>

            <form action="{{ route('admin.utilities.create') }}" method="GET" class="flex items-end gap-2">
                <div>
                    <label for="month" class="mb-1 block text-xs font-semibold text-slate-600">Tháng</label>
                    <select id="month" name="month" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($month === $m)>Tháng {{ $m }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="year" class="mb-1 block text-xs font-semibold text-slate-600">Năm</label>
                    <select id="year" name="year" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm">
                        @for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" @selected($year === $y)>Năm {{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div><label for="record_date_filter" class="mb-1 block text-xs font-semibold text-slate-600">Ngày chốt</label><input id="record_date_filter" type="date" name="record_date" value="{{ $recordDate }}" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm"></div>
                <button class="h-10 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white">Xem kỳ</button>
            </form>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Chưa thể lưu chỉ số</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Tiến độ</p>
                <p class="mt-1 text-2xl font-bold text-slate-950"><span id="savedCount">{{ $savedCount }}</span>/{{ count($readings) }} phòng</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4 sm:col-span-2">
                <p class="text-sm font-semibold text-slate-800">Cách nhập nhanh</p>
                <p class="mt-1 text-sm text-slate-500">Nhập điện mới → Enter → nước mới → Enter để sang phòng kế tiếp. Bạn có thể chỉ nhập vài phòng rồi lưu, không cần hoàn thành tất cả cùng lúc.</p>
            </div>
        </div>

        <form id="utilityForm" action="{{ route('admin.utilities.store') }}" method="POST" enctype="multipart/form-data" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="record_date" value="{{ $recordDate }}">

            <div class="flex flex-col gap-3 border-b border-slate-200 p-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-1 flex-col gap-2 sm:flex-row">
                    <label class="relative max-w-md flex-1">
                        <i class="bx bx-search absolute left-3 top-2.5 text-xl text-slate-400"></i>
                        <input id="roomSearch" type="search" placeholder="Tìm mã phòng..." class="h-10 w-full rounded-lg border border-slate-200 pl-10 pr-3 text-sm outline-none focus:border-indigo-500">
                    </label>
                    <select id="statusFilter" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm">
                        <option value="all">Tất cả phòng</option>
                        <option value="pending">Chưa nhập</option>
                        <option value="draft">Bản nháp</option>
                        <option value="confirmed">Đã xác nhận</option>
                        <option value="locked">Đã xuất hóa đơn</option>
                    </select>
                </div>
                <a href="{{ route('admin.utilities.index', ['month' => $month, 'year' => $year]) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i class="bx bx-list-check text-lg"></i>Xem bảng đã chốt
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[980px] w-full divide-y divide-slate-200 text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="sticky left-0 z-20 bg-slate-50 px-4 py-3">Phòng</th>
                            <th class="px-3 py-3">Điện cũ</th>
                            <th class="px-3 py-3">Điện mới</th>
                            <th class="px-3 py-3">Tiêu thụ</th>
                            <th class="px-3 py-3">Nước cũ</th>
                            <th class="px-3 py-3">Nước mới</th>
                            <th class="px-3 py-3">Tiêu thụ</th>
                            <th class="px-3 py-3">Ảnh / trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($readings as $index => $item)
                            @php
                                $electricityNew = old("readings.$index.electricity_new", $item['electricity_new']);
                                $waterNew = old("readings.$index.water_new", $item['water_new']);
                                $state = $item['locked'] ? 'locked' : ($item['status'] ?? 'pending');
                            @endphp
                            <tr class="utility-row {{ $item['locked'] ? 'bg-slate-50' : 'hover:bg-indigo-50/30' }}" data-room="{{ strtolower($item['room_name']) }}" data-state="{{ $state }}">
                                <td class="sticky left-0 z-[5] bg-inherit px-4 py-4">
                                    <div class="flex items-start gap-3">
                                        <input type="hidden" name="readings[{{ $index }}][room_id]" value="{{ $item['room_id'] }}">
                                        <input class="row-selector mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600" type="checkbox" name="readings[{{ $index }}][selected]" value="1" @checked(old("readings.$index.selected")) @disabled(!$item['editable']) aria-label="Chọn phòng {{ $item['room_name'] }} để lưu">
                                        <div>
                                            <p class="font-bold text-slate-950">{{ $item['room_name'] }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">Thuê từ {{ $item['start_date'] }}</p>
                                            @if ($item['last_period'])
                                                <p class="text-xs text-slate-400">Số cũ từ {{ $item['last_period'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-4 font-semibold text-slate-600"><span class="elec-old">{{ $item['electricity_old'] }}</span></td>
                                <td class="px-3 py-4">
                                    <input type="number" inputmode="numeric" min="{{ $item['electricity_old'] }}" name="readings[{{ $index }}][electricity_new]" value="{{ $electricityNew }}" class="meter-input elec-new h-11 w-32 rounded-lg border border-slate-200 px-3 text-lg font-bold text-indigo-700 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" placeholder="Nhập số" @readonly(!$item['editable'])>
                                </td>
                                <td class="px-3 py-4"><span class="elec-usage rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-500">—</span></td>
                                <td class="px-3 py-4 font-semibold text-slate-600"><span class="water-old">{{ $item['water_old'] }}</span></td>
                                <td class="px-3 py-4">
                                    <input type="number" inputmode="numeric" min="{{ $item['water_old'] }}" name="readings[{{ $index }}][water_new]" value="{{ $waterNew }}" class="meter-input water-new h-11 w-32 rounded-lg border border-slate-200 px-3 text-lg font-bold text-sky-700 outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100" placeholder="Nhập số" @readonly(!$item['editable'])>
                                </td>
                                <td class="px-3 py-4"><span class="water-usage rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-500">—</span></td>
                                <td class="px-3 py-4">
                                    @if ($item['locked'])
                                        <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">Đã xuất hóa đơn</span>
                                    @elseif ($item['status'] === \App\Models\UtilityReading::STATUS_CONFIRMED)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Đã xác nhận</span>
                                        <p class="mt-2 text-xs text-slate-500">Muốn sửa, hãy mở lại tại bảng kiểm tra.</p>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="zero-usage rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Không phát sinh</button>
                                            <details class="relative">
                                                <summary class="cursor-pointer list-none rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-600">Chụp ảnh</summary>
                                                <div class="absolute right-0 z-30 mt-2 w-72 space-y-3 rounded-lg border border-slate-200 bg-white p-3 shadow-xl">
                                                    <label class="block text-xs font-semibold text-slate-700">Ảnh đồng hồ điện
                                                        <input type="file" name="readings[{{ $index }}][electricity_image]" accept="image/*" capture="environment" class="mt-1 block w-full text-xs">
                                                    </label>
                                                    <label class="block text-xs font-semibold text-slate-700">Ảnh đồng hồ nước
                                                        <input type="file" name="readings[{{ $index }}][water_image]" accept="image/*" capture="environment" class="mt-1 block w-full text-xs">
                                                    </label>
                                                </div>
                                            </details>
                                        </div>
                                        <p class="mt-2 text-xs {{ $item['saved'] ? 'text-amber-600' : 'text-slate-500' }}">{{ $item['saved'] ? 'Bản nháp — có thể sửa' : 'Chưa nhập' }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-12 text-center text-slate-500">Không có phòng đang thuê trong kỳ này.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (count($readings) > 0)
                <div class="sticky bottom-0 flex flex-col gap-3 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500"><strong id="selectedCount" class="text-slate-900">0</strong> phòng sẽ được lưu. Phòng chưa chọn sẽ được giữ nguyên.</p>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button id="draftButton" type="submit" name="intent" value="draft" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" disabled>
                            <i class="bx bx-save text-lg"></i>Lưu nháp
                        </button>
                        <button id="confirmButton" type="submit" name="intent" value="confirm" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300" disabled>
                            <i class="bx bx-check-circle text-lg"></i>Lưu và xác nhận
                        </button>
                    </div>
                </div>
            @endif
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rows = [...document.querySelectorAll('.utility-row')];
            const inputs = [...document.querySelectorAll('.meter-input:not([readonly])')];
            const search = document.getElementById('roomSearch');
            const filter = document.getElementById('statusFilter');
            const selectedCount = document.getElementById('selectedCount');
            const draftButton = document.getElementById('draftButton');
            const confirmButton = document.getElementById('confirmButton');

            const updateRow = (row, autoSelect = false) => {
                const selector = row.querySelector('.row-selector');
                const pairs = [
                    ['.elec-old', '.elec-new', '.elec-usage', 'kWh'],
                    ['.water-old', '.water-new', '.water-usage', 'm³'],
                ];
                let complete = true;
                let valid = true;

                pairs.forEach(([oldSelector, newSelector, usageSelector, unit]) => {
                    const oldValue = Number(row.querySelector(oldSelector).textContent);
                    const input = row.querySelector(newSelector);
                    const output = row.querySelector(usageSelector);
                    if (!input.value) {
                        output.textContent = '—';
                        output.className = output.className.replace(/bg-\S+|text-\S+/g, '').trim() + ' bg-slate-100 text-slate-500';
                        complete = false;
                        return;
                    }
                    const usage = Number(input.value) - oldValue;
                    const ok = usage >= 0;
                    valid = valid && ok;
                    output.textContent = ok ? `+${usage} ${unit}` : 'Nhỏ hơn số cũ';
                    output.className = output.className.replace(/bg-\S+|text-\S+/g, '').trim() + (ok ? ' bg-emerald-50 text-emerald-700' : ' bg-rose-50 text-rose-700');
                    input.classList.toggle('border-rose-400', !ok);
                });

                if (selector && autoSelect) selector.checked = complete && valid;
                refreshSelection();
            };

            const refreshSelection = () => {
                const checked = document.querySelectorAll('.row-selector:checked').length;
                selectedCount.textContent = checked;
                draftButton.disabled = checked === 0;
                confirmButton.disabled = checked === 0;
            };

            const applyFilter = () => {
                const keyword = search.value.trim().toLowerCase();
                rows.forEach(row => {
                    const matchesRoom = row.dataset.room.includes(keyword);
                    const matchesState = filter.value === 'all' || row.dataset.state === filter.value;
                    row.classList.toggle('hidden', !(matchesRoom && matchesState));
                });
            };

            inputs.forEach((input, index) => {
                input.addEventListener('focus', () => input.select());
                input.addEventListener('input', () => updateRow(input.closest('tr'), true));
                input.addEventListener('keydown', event => {
                    if (event.key !== 'Enter') return;
                    event.preventDefault();
                    const next = inputs[index + 1];
                    if (next) {
                        next.focus();
                        next.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    } else {
                        confirmButton.focus();
                    }
                });
            });

            document.querySelectorAll('.zero-usage').forEach(button => {
                button.addEventListener('click', () => {
                    const row = button.closest('tr');
                    row.querySelector('.elec-new').value = row.querySelector('.elec-old').textContent.trim();
                    row.querySelector('.water-new').value = row.querySelector('.water-old').textContent.trim();
                    updateRow(row, true);
                });
            });

            document.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', () => {
                    if (input.files.length) input.closest('tr').querySelector('.row-selector').checked = true;
                    refreshSelection();
                });
            });

            document.querySelectorAll('.row-selector').forEach(input => input.addEventListener('change', refreshSelection));
            rows.forEach(row => updateRow(row));
            search.addEventListener('input', applyFilter);
            filter.addEventListener('change', applyFilter);
        });
    </script>
@endpush
