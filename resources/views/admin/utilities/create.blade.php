@extends('layouts.admin.index')

@section('title', 'Nhập chỉ số điện nước | Quản lý phòng trọ')
@section('page_title', 'Nhập chỉ số điện nước')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Điện nước và dịch vụ</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Nhập chỉ số điện/nước</h2>
            </div>

            <form action="{{ route('admin.utilities.create') }}" method="GET" class="flex flex-wrap items-end gap-2">
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

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Vui lòng kiểm tra lại thông tin.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.utilities.store') }}" method="POST" enctype="multipart/form-data" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">

            <div class="flex flex-col justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center">
                <div>
                    <h3 class="font-semibold text-slate-950">Danh sách phòng</h3>
                    <p class="text-sm text-slate-500">Kỳ chốt tháng {{ $month }}/{{ $year }}</p>
                </div>
                <a href="{{ route('admin.utilities.index', ['month' => $month, 'year' => $year]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i class="bx bx-list-check text-lg"></i>
                    Kiểm tra chỉ số
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Phòng</th>
                            <th class="px-5 py-3 text-center">Điện cũ</th>
                            <th class="px-5 py-3 text-center">Điện mới</th>
                            <th class="px-5 py-3">Ảnh điện</th>
                            <th class="px-5 py-3 text-center">Nước cũ</th>
                            <th class="px-5 py-3 text-center">Nước mới</th>
                            <th class="px-5 py-3">Ảnh nước</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($readings as $index => $item)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950">{{ $item['room_name'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Ngày thuê: {{ $item['start_date'] }}</p>
                                    <input type="hidden" name="readings[{{ $index }}][room_id]" value="{{ $item['room_id'] }}">
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <input type="number" class="elec-old w-24 rounded-lg border border-transparent bg-slate-50 px-3 py-2 text-center font-semibold text-slate-700" name="readings[{{ $index }}][electricity_old]" value="{{ $item['electricity_old'] }}" readonly tabindex="-1">
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <input type="number" class="calc-input elec-new w-32 rounded-lg border border-slate-200 px-3 py-2 text-center font-semibold text-indigo-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" name="readings[{{ $index }}][electricity_new]" min="{{ $item['electricity_old'] }}" placeholder="Nhập số..." required>
                                    <small class="elec-usage mt-1 block h-5 text-xs"></small>
                                </td>
                                <td class="px-5 py-4">
                                    <input type="file" class="block w-48 rounded-lg border border-slate-200 text-xs text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-slate-700" name="readings[{{ $index }}][electricity_image]" accept="image/jpeg,image/png,image/webp">
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <input type="number" class="water-old w-24 rounded-lg border border-transparent bg-slate-50 px-3 py-2 text-center font-semibold text-slate-700" name="readings[{{ $index }}][water_old]" value="{{ $item['water_old'] }}" readonly tabindex="-1">
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <input type="number" class="calc-input water-new w-32 rounded-lg border border-slate-200 px-3 py-2 text-center font-semibold text-sky-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" name="readings[{{ $index }}][water_new]" min="{{ $item['water_old'] }}" placeholder="Nhập số..." required>
                                    <small class="water-usage mt-1 block h-5 text-xs"></small>
                                </td>
                                <td class="px-5 py-4">
                                    <input type="file" class="block w-48 rounded-lg border border-slate-200 text-xs text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-slate-700" name="readings[{{ $index }}][water_image]" accept="image/jpeg,image/png,image/webp">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">Không có phòng đang thuê cần nhập chỉ số trong kỳ này.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (count($readings) > 0)
                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <a href="{{ route('admin.utilities.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        <i class="bx bx-save text-lg"></i>
                        Lưu tất cả chỉ số
                    </button>
                </div>
            @endif
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.calc-input');

            inputs.forEach((input, index) => {
                input.addEventListener('focus', function() {
                    this.select();
                });

                input.addEventListener('input', function() {
                    const row = this.closest('tr');
                    const isElec = this.classList.contains('elec-new');
                    const oldVal = parseFloat(row.querySelector(isElec ? '.elec-old' : '.water-old').value) || 0;
                    const newVal = parseFloat(this.value);
                    const usageDisplay = row.querySelector(isElec ? '.elec-usage' : '.water-usage');

                    this.classList.remove('border-rose-400', 'ring-rose-100', 'border-emerald-400', 'ring-emerald-100');

                    if (isNaN(newVal)) {
                        usageDisplay.textContent = '';
                        return;
                    }

                    const usage = newVal - oldVal;
                    if (usage < 0) {
                        this.classList.add('border-rose-400', 'ring-rose-100');
                        usageDisplay.innerHTML = '<span class="text-rose-600">Nhỏ hơn số cũ</span>';
                    } else {
                        this.classList.add('border-emerald-400', 'ring-emerald-100');
                        usageDisplay.innerHTML = `<span class="font-semibold text-emerald-700">Dùng: ${usage}</span>`;
                    }
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const nextInput = inputs[index + 1];
                        if (nextInput) {
                            nextInput.focus();
                        } else {
                            document.querySelector('button[type="submit"]')?.focus();
                        }
                    }
                });
            });
        });
    </script>
@endpush
