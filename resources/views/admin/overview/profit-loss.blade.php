@extends('layouts.admin.index')

@section('title', 'Báo cáo Thu - Chi & Lợi nhuận | Quản lý phòng trọ')
@section('page_title', 'Báo cáo Thu - Chi & Lợi nhuận')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Phân tích tài chính & Lợi nhuận ròng</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">
                    Báo cáo Thu - Chi @if($selectedMonth)Tháng {{ $selectedMonth }}/{{ $selectedYear }} @else Năm {{ $selectedYear }} @endif
                </h2>
            </div>

            {{-- Bộ lọc Thời gian --}}
            <form method="GET" action="{{ route('admin.profit-loss.index') }}" class="flex flex-wrap items-center gap-2">
                <select name="month" onchange="this.form.submit()" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none">
                    <option value="">Cả năm {{ $selectedYear }}</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected((string)$selectedMonth === (string)$m)>Tháng {{ $m }}</option>
                    @endfor
                </select>

                <select name="year" onchange="this.form.submit()" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none">
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected($selectedYear === (int)$y)>Năm {{ $y }}</option>
                    @endforeach
                </select>

                <a href="{{ route('admin.expenses.create') }}" class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-plus text-base"></i> Lập phiếu chi
                </a>
            </form>
        </div>

        {{-- 4 Thẻ KPI Tài chính --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-emerald-800">Doanh thu thực thu</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                        <i class="bx bx-trending-up text-xl"></i>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-extrabold text-emerald-700">+{{ number_format($totalRevenue, 0, ',', '.') }}đ</p>
                <p class="mt-1 text-xs text-emerald-600">Tiền khách đã thanh toán thành công</p>
            </div>

            <div class="rounded-lg border border-rose-200 bg-rose-50/70 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-rose-800">Tổng chi phí vận hành</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                        <i class="bx bx-trending-down text-xl"></i>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-extrabold text-rose-700">-{{ number_format($totalExpenses, 0, ',', '.') }}đ</p>
                <p class="mt-1 text-xs text-rose-600">Bao gồm điện nước NN, sửa chữa, định kỳ</p>
            </div>

            <div class="rounded-lg border {{ $netProfit >= 0 ? 'border-indigo-200 bg-indigo-50/70' : 'border-red-200 bg-red-50/70' }} p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold {{ $netProfit >= 0 ? 'text-indigo-800' : 'text-red-800' }}">Lợi nhuận ròng (Net Profit)</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $netProfit >= 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-red-100 text-red-700' }}">
                        <i class="bx bx-wallet text-xl"></i>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-extrabold {{ $netProfit >= 0 ? 'text-indigo-700' : 'text-red-700' }}">
                    {{ $netProfit >= 0 ? '+' : '' }}{{ number_format($netProfit, 0, ',', '.') }}đ
                </p>
                <p class="mt-1 text-xs {{ $netProfit >= 0 ? 'text-indigo-600' : 'text-red-600' }}">Thu thực tế trừ đi Chi thực tế</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Tỷ suất lợi nhuận</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                        <i class="bx bx-pie-chart-alt text-xl"></i>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-extrabold text-slate-900">{{ $profitMargin }}%</p>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-2 rounded-full {{ $profitMargin >= 50 ? 'bg-emerald-500' : ($profitMargin >= 20 ? 'bg-indigo-500' : 'bg-amber-500') }}" style="width: {{ max(0, min($profitMargin, 100)) }}%"></div>
                </div>
            </div>
        </div>

        {{-- Đối soát Điện & Nước --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col justify-between gap-2 border-b border-slate-100 pb-3 sm:flex-row sm:items-center">
                <div>
                    <h3 class="font-bold text-slate-950">Đối soát Chênh lệch Điện & Nước</h3>
                    <p class="text-xs text-slate-500">So sánh tiền thu từ khách theo hóa đơn phòng với tiền nộp nhà nước theo phiếu chi</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                {{-- Cột Điện --}}
                <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                <i class="bx bx-bolt-circle text-lg"></i>
                            </span>
                            <h4 class="font-bold text-amber-950">Tiền Điện</h4>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $elecDiff >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                            {{ $elecDiff >= 0 ? 'Dư/Lãi: +' . number_format($elecDiff, 0, ',', '.') . 'đ' : 'Hụt/Lỗ: ' . number_format($elecDiff, 0, ',', '.') . 'đ' }}
                        </span>
                    </div>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-600">Thu từ khách (Hóa đơn):</span>
                            <span class="font-semibold text-slate-900">{{ number_format($elecInvoiced, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Nộp nhà nước (EVN):</span>
                            <span class="font-semibold text-rose-600">-{{ number_format($elecPaidGov, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                </div>

                {{-- Cột Nước --}}
                <div class="rounded-lg border border-cyan-200 bg-cyan-50/50 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-cyan-100 text-cyan-600">
                                <i class="bx bx-droplet text-lg"></i>
                            </span>
                            <h4 class="font-bold text-cyan-950">Tiền Nước</h4>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $waterDiff >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                            {{ $waterDiff >= 0 ? 'Dư/Lãi: +' . number_format($waterDiff, 0, ',', '.') . 'đ' : 'Hụt/Lỗ: ' . number_format($waterDiff, 0, ',', '.') . 'đ' }}
                        </span>
                    </div>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-600">Thu từ khách (Hóa đơn):</span>
                            <span class="font-semibold text-slate-900">{{ number_format($waterInvoiced, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Nộp nhà nước (Cấp nước):</span>
                            <span class="font-semibold text-rose-600">-{{ number_format($waterPaidGov, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Charts: 12 tháng & Cơ cấu chi phí --}}
        <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-950">So sánh Thu - Chi theo 12 tháng</h3>
                        <p class="text-xs text-slate-500">Năm {{ $selectedYear }}</p>
                    </div>
                </div>
                <div id="cashFlowChart" class="mt-4 min-h-[350px]"></div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <h3 class="font-bold text-slate-950">Cơ cấu Chi phí</h3>
                    <p class="text-xs text-slate-500">Tỷ trọng các danh mục chi tiêu</p>
                </div>
                @if(count($categoryChartValues) > 0)
                    <div id="expenseStructureChart" class="mt-4 min-h-[300px]"></div>
                @else
                    <div class="flex h-64 items-center justify-center text-sm text-slate-400">
                        Chưa có dữ liệu chi phí trong kỳ này.
                    </div>
                @endif
                <div class="mt-4 space-y-2 border-t border-slate-100 pt-3">
                    @foreach($categoryBreakdown as $item)
                        @if($item['amount'] > 0)
                            <div class="flex items-center justify-between text-xs">
                                <span class="flex items-center gap-1.5 text-slate-600">
                                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $item['color'] }}"></span>
                                    {{ $item['label'] }}
                                </span>
                                <span class="font-semibold text-slate-900">{{ number_format($item['amount'], 0, ',', '.') }}đ ({{ $item['percent'] }}%)</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        </div>

        {{-- Top chi phí lớn gần đây --}}
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="font-bold text-slate-950">Các khoản chi lớn nhất</h3>
                    <p class="text-xs text-slate-500">Khoản chi tiêu đáng chú ý trong kỳ</p>
                </div>
                <a href="{{ route('admin.expenses.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">
                    Xem tất cả chi phí →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Mã phiếu</th>
                            <th class="px-5 py-3">Khoản chi</th>
                            <th class="px-5 py-3">Ngày chi</th>
                            <th class="px-5 py-3">Phòng</th>
                            <th class="px-5 py-3 text-right">Số tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topExpenses as $exp)
                            <tr>
                                <td class="px-5 py-3 font-mono font-medium text-indigo-600">{{ $exp->expense_code }}</td>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $exp->title }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $exp->expense_date->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $exp->room ? 'Phòng ' . $exp->room->room_code : 'Toàn nhà' }}</td>
                                <td class="px-5 py-3 text-right font-bold text-rose-600">-{{ number_format($exp->amount, 0, ',', '.') }}đ</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-6 text-center text-slate-400">Chưa có khoản chi nào trong kỳ.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        const formatMoney = val => Number(val).toLocaleString('vi-VN') + 'đ';

        // Biểu đồ Thu - Chi 12 tháng
        new ApexCharts(document.querySelector("#cashFlowChart"), {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false }
            },
            series: [
                {
                    name: 'Doanh thu (Thu)',
                    data: @json($monthlyRevenueData)
                },
                {
                    name: 'Chi phí (Chi)',
                    data: @json($monthlyExpenseData)
                },
                {
                    name: 'Lợi nhuận ròng',
                    type: 'line',
                    data: @json($monthlyProfitData)
                }
            ],
            colors: ['#10b981', '#f43f5e', '#6366f1'],
            stroke: {
                width: [0, 0, 3],
                curve: 'smooth'
            },
            plotOptions: {
                bar: {
                    columnWidth: '55%',
                    borderRadius: 3
                }
            },
            xaxis: {
                categories: @json($monthsLabels)
            },
            yaxis: {
                labels: {
                    formatter: val => Number(val / 1000000).toFixed(1) + 'tr'
                }
            },
            tooltip: {
                y: { formatter: formatMoney }
            },
            legend: {
                position: 'top'
            }
        }).render();

        // Biểu đồ Cơ cấu chi phí
        @if(count($categoryChartValues) > 0)
            new ApexCharts(document.querySelector("#expenseStructureChart"), {
                chart: {
                    type: 'donut',
                    height: 280
                },
                series: @json($categoryChartValues),
                labels: @json($categoryChartLabels),
                colors: @json($categoryChartColors),
                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Tổng chi',
                                    formatter: () => formatMoney({{ $totalExpenses }})
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: val => Math.round(val) + '%'
                },
                legend: {
                    show: false
                },
                tooltip: {
                    y: { formatter: formatMoney }
                }
            }).render();
        @endif
    </script>
@endpush

