@extends('layouts.admin.index')

@section('title', 'Nhập chỉ số điện nước | Quản lý phòng trọ')
@section('page_title', 'Nhập chỉ số điện nước')

@section('content')
    <div class="mx-auto max-w-[1380px] space-y-5 pb-8">
        <div class="rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 p-5 text-white shadow-lg shadow-indigo-100 lg:flex lg:items-center lg:justify-between lg:p-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-100">Quản lý điện nước</p>
                <h2 class="mt-1 text-2xl font-bold">{{ $mode === 'checkpoint' ? 'Ghi mốc giữa kỳ' : 'Chốt kỳ' }} tháng {{ $month }}/{{ $year }}</h2>
                <p class="mt-1 text-sm text-indigo-100">Nhập chỉ số, kiểm tra mức tiêu thụ và xác nhận đồng loạt theo từng phòng.</p>
            </div>

            <form action="{{ route('admin.utilities.create') }}" method="GET" class="mt-4 flex flex-wrap items-end gap-2 rounded-xl border border-white/20 bg-white/10 p-3 backdrop-blur lg:mt-0">
                <input type="hidden" name="mode" value="{{ $mode }}">
                <div>
                    <label for="month" class="mb-1 block text-xs font-semibold text-indigo-100">Tháng</label>
                    <select id="month" name="month" class="h-10 rounded-lg border border-white/30 bg-white px-3 text-sm text-slate-800">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($month === $m)>Tháng {{ $m }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="year" class="mb-1 block text-xs font-semibold text-indigo-100">Năm</label>
                    <select id="year" name="year" class="h-10 rounded-lg border border-white/30 bg-white px-3 text-sm text-slate-800">
                        @for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" @selected($year === $y)>Năm {{ $y }}</option>
                        @endfor
                    </select>
                </div>
                @if($mode === 'checkpoint')
                    <div>
                        <label for="reading_date" class="mb-1 block text-xs font-semibold text-slate-600">Ngày ghi mốc</label>
                        <input id="reading_date" type="date" name="reading_date" min="{{ \Carbon\Carbon::createFromDate($year, $month, 1)->toDateString() }}" max="{{ $recordDate }}" value="{{ $checkpointDate->toDateString() }}" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700">
                    </div>
                @else
                    <div>
                        <p class="mb-1 text-xs font-semibold text-indigo-100">Ngày đối soát</p>
                        <div class="flex h-10 items-center rounded-lg border border-white/30 bg-white/90 px-3 text-sm font-semibold text-slate-700">{{ \Carbon\Carbon::parse($recordDate)->format('d/m/Y') }}</div>
                    </div>
                @endif
                <button class="h-10 rounded-lg bg-white px-4 text-sm font-bold text-indigo-700 shadow-sm transition hover:bg-indigo-50">Xem kỳ</button>
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

        @php
            $totalRooms = count($readings);
            $remainingRooms = max(0, $totalRooms - $savedCount);
            $completionPercent = $totalRooms > 0 ? round(($savedCount / $totalRooms) * 100) : 0;
        @endphp
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $mode === 'checkpoint' ? 'Ngày ghi mốc' : 'Tiến độ chốt kỳ' }}</p>
                @if($mode === 'checkpoint')
                    <p class="mt-1 text-2xl font-bold text-sky-700">{{ $checkpointDate->format('d/m/Y') }}</p>
                @else
                        <p class="mt-1 text-2xl font-bold text-slate-950"><span id="savedCount">{{ $savedCount }}</span>/{{ $totalRooms }} phòng đã hoàn tất</p>
                @endif
                </div>
                @if($mode !== 'checkpoint')
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700">Còn {{ $remainingRooms }} phòng</span>
                        <span class="text-2xl font-bold text-indigo-600">{{ $completionPercent }}%</span>
                    </div>
                @endif
            </div>
            @if($mode !== 'checkpoint')
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500" style="width: {{ $completionPercent }}%"></div>
                </div>
            @endif
        </div>

        <form id="utilityForm" action="{{ route('admin.utilities.store') }}" method="POST" enctype="multipart/form-data" class="overflow-visible rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            @if($mode === 'checkpoint')
                <input type="hidden" name="reading_date" value="{{ $checkpointDate->toDateString() }}">
            @endif

            <div class="sticky top-0 z-30 flex flex-col gap-3 rounded-t-2xl border-b border-slate-200 bg-white/95 p-4 backdrop-blur lg:flex-row lg:items-center lg:justify-between">
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
                <a href="{{ route('admin.utilities.create', ['month' => $month, 'year' => $year, 'mode' => $mode === 'checkpoint' ? 'final' : 'checkpoint']) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-sky-200 bg-sky-50 px-4 text-sm font-semibold text-sky-700 hover:bg-sky-100">
                    <i class="bx bx-transfer-alt text-lg"></i>{{ $mode === 'checkpoint' ? 'Quay lại chốt kỳ' : 'Ghi mốc giữa kỳ' }}
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[980px] w-full table-fixed divide-y divide-slate-200 text-sm">
                    <colgroup>
                        <col class="w-[17%]">
                        <col class="w-[7%]">
                        <col class="w-[11%]">
                        <col class="w-[9%]">
                        <col class="w-[7%]">
                        <col class="w-[11%]">
                        <col class="w-[9%]">
                        <col class="w-[29%]">
                    </colgroup>
                    <thead class="sticky top-0 z-10 bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="sticky left-0 z-20 bg-slate-50 px-4 py-3">Phòng</th>
                            <th class="whitespace-nowrap px-3 py-3">Điện cũ</th>
                            <th class="px-3 py-3">Điện mới</th>
                            <th class="whitespace-nowrap px-3 py-3">Tiêu thụ</th>
                            <th class="whitespace-nowrap px-3 py-3">Nước cũ</th>
                            <th class="px-3 py-3">Nước mới</th>
                            <th class="whitespace-nowrap px-3 py-3">Tiêu thụ</th>
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
                            <tr class="utility-row transition-colors {{ $item['locked'] ? 'bg-slate-50/80' : ($state === 'pending' ? 'bg-amber-50/30 hover:bg-amber-50/60' : 'hover:bg-indigo-50/40') }}" data-room="{{ strtolower($item['room_name']) }}" data-state="{{ $state }}">
                                <td class="sticky left-0 z-[5] bg-inherit px-4 py-4">
                                    <div class="flex items-start gap-3">
                                        <input type="hidden" name="readings[{{ $index }}][room_id]" value="{{ $item['room_id'] }}">
                                        <input class="row-selector mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600" type="checkbox" name="readings[{{ $index }}][selected]" value="1" @checked(old("readings.$index.selected")) @disabled(!$item['editable']) aria-label="Chọn phòng {{ $item['room_name'] }} để lưu">
                                        <div>
                                            <p class="inline-flex rounded-lg bg-slate-900 px-2 py-1 font-bold text-white">{{ $item['room_name'] }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">Thuê từ {{ $item['start_date'] }}</p>
                                            @if ($item['last_period'])
                                            <p class="text-xs text-slate-400">Số cũ từ {{ $item['last_period'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-4 font-semibold text-slate-600"><span class="elec-old">{{ $item['electricity_old'] }}</span></td>
                                <td class="px-3 py-4">
                                    <input type="number" inputmode="numeric" min="{{ $item['electricity_old'] }}" name="readings[{{ $index }}][electricity_new]" value="{{ $electricityNew }}" class="meter-input elec-new h-11 w-full max-w-24 rounded-xl border border-slate-200 bg-white px-3 text-base font-bold text-indigo-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 read-only:bg-slate-50" placeholder="Nhập số" @readonly(!$item['editable'])>
                                </td>
                                <td class="px-3 py-4"><span class="elec-usage rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-500">—</span></td>
                                <td class="px-3 py-4 font-semibold text-slate-600"><span class="water-old">{{ $item['water_old'] }}</span></td>
                                <td class="px-3 py-4">
                                    <input type="number" inputmode="numeric" min="{{ $item['water_old'] }}" name="readings[{{ $index }}][water_new]" value="{{ $waterNew }}" class="meter-input water-new h-11 w-full max-w-24 rounded-xl border border-slate-200 bg-white px-3 text-base font-bold text-sky-700 outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-100 read-only:bg-slate-50" placeholder="Nhập số" @readonly(!$item['editable'])>
                                </td>
                                <td class="px-3 py-4"><span class="water-usage rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-500">—</span></td>
                                <td class="px-3 py-4">
                                    @if ($item['locked'])
                                        <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">Đã xuất hóa đơn</span>
                                    @elseif ($item['status'] === \App\Models\UtilityReading::STATUS_CONFIRMED)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Đã xác nhận</span>
                                        <p class="mt-2 text-xs text-slate-500">Muốn sửa, hãy mở lại tại bảng kiểm tra.</p>
                                    @else
                                        <div class="flex items-center gap-2 whitespace-nowrap">
                                            <button type="button" style="width: 126px; min-width: 126px;" class="zero-usage inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>
                                                <span>Không phát sinh</span>
                                            </button>
                                            <details class="relative">
                                                <summary style="width: 96px; min-width: 96px;" class="inline-flex h-9 cursor-pointer list-none items-center justify-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 [&::-webkit-details-marker]:hidden">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h3l1.5-2h7L17 7h3v12H4z"/><circle cx="12" cy="13" r="3"/></svg>
                                                    Thêm ảnh
                                                </summary>
                                                <div class="absolute right-0 z-40 mt-2 w-72 space-y-2 rounded-xl border border-slate-200 bg-white p-3 shadow-2xl">
                                                    <p class="mb-2 text-xs text-slate-500">Ảnh là tùy chọn, tối đa 5 MB mỗi ảnh.</p>
                                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-slate-300 p-3 transition hover:border-indigo-300 hover:bg-indigo-50">
                                                        <input type="file" name="readings[{{ $index }}][electricity_image]" accept="image/*" capture="environment" class="meter-photo sr-only">
                                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><i class="bx bx-bolt-circle text-xl"></i></span>
                                                        <span class="min-w-0"><strong class="block text-xs text-slate-700">Ảnh đồng hồ điện</strong><span class="file-name block truncate text-xs text-slate-400">Chưa chọn ảnh</span></span>
                                                    </label>
                                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-slate-300 p-3 transition hover:border-sky-300 hover:bg-sky-50">
                                                        <input type="file" name="readings[{{ $index }}][water_image]" accept="image/*" capture="environment" class="meter-photo sr-only">
                                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600"><i class="bx bx-water text-xl"></i></span>
                                                        <span class="min-w-0"><strong class="block text-xs text-slate-700">Ảnh đồng hồ nước</strong><span class="file-name block truncate text-xs text-slate-400">Chưa chọn ảnh</span></span>
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
                <div class="flex flex-col gap-3 rounded-b-2xl border-t border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">Đã chọn <strong id="selectedCount" class="text-slate-900">0</strong> phòng để lưu</p>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        @if($mode === 'checkpoint')
                            <button id="confirmButton" type="submit" name="intent" value="checkpoint" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-sky-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-slate-300" disabled>
                                <i class="bx bx-map-pin text-lg"></i>Ghi mốc giữa kỳ
                            </button>
                        @else
                            <button id="draftButton" type="submit" name="intent" value="draft" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" disabled>
                                <i class="bx bx-save text-lg"></i>Lưu nháp
                            </button>
                            <button id="confirmButton" type="submit" name="intent" value="confirm" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300" disabled title="{{ $canFinalize ? 'Xác nhận chốt kỳ' : 'Chỉ được xác nhận vào ngày cuối tháng' }}">
                                <i class="bx bx-check-circle text-lg"></i>Lưu và xác nhận
                            </button>
                        @endif
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
            const monthInput = document.getElementById('month');
            const yearInput = document.getElementById('year');
            const readingDateInput = document.getElementById('reading_date');
            const canSubmitConfirmation = @json($mode === 'checkpoint' || $canFinalize);

            const syncCheckpointDate = () => {
                if (!readingDateInput) return;
                const year = Number(yearInput.value);
                const month = Number(monthInput.value);
                const paddedMonth = String(month).padStart(2, '0');
                const lastDay = new Date(Date.UTC(year, month, 0)).getUTCDate();
                const minimum = `${year}-${paddedMonth}-01`;
                const maximum = `${year}-${paddedMonth}-${String(lastDay).padStart(2, '0')}`;
                readingDateInput.min = minimum;
                readingDateInput.max = maximum;
                if (!readingDateInput.value || readingDateInput.value < minimum || readingDateInput.value > maximum) {
                    readingDateInput.value = maximum;
                }
            };

            monthInput?.addEventListener('change', syncCheckpointDate);
            yearInput?.addEventListener('change', syncCheckpointDate);

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
                if (draftButton) draftButton.disabled = checked === 0;
                confirmButton.disabled = checked === 0 || !canSubmitConfirmation;
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

            document.querySelectorAll('.meter-photo').forEach(input => {
                input.addEventListener('change', () => {
                    const fileName = input.closest('label').querySelector('.file-name');
                    fileName.textContent = input.files.length ? input.files[0].name : 'Chưa chọn ảnh';
                    fileName.classList.toggle('text-emerald-600', input.files.length > 0);
                    updateRow(input.closest('tr'), true);
                });
            });

            document.querySelectorAll('details').forEach(details => {
                details.addEventListener('toggle', () => {
                    if (!details.open) return;
                    document.querySelectorAll('details[open]').forEach(item => {
                        if (item !== details) item.removeAttribute('open');
                    });
                });
            });

            document.querySelectorAll('.row-selector').forEach(input => input.addEventListener('change', refreshSelection));
            rows.forEach(row => updateRow(row));
            search.addEventListener('input', applyFilter);
            filter.addEventListener('change', applyFilter);
        });
    </script>
@endpush
