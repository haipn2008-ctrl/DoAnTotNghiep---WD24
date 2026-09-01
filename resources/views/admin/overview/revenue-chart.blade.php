@extends('layouts.admin.index')

@section('title', 'Phân tích doanh thu | Quản lý phòng trọ')
@section('page_title', 'Phân tích doanh thu')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Tháng {{ $reportMonth }}/{{ $reportYear }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Phân tích doanh thu</h2>
            </div>

            <form method="GET" action="{{ route('admin.overview.revenue-chart') }}" class="flex flex-wrap items-center gap-2">
                <select name="month" onchange="this.form.submit()" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected((int)$reportMonth === (int)$m)>Tháng {{ $m }}</option>
                    @endfor
                </select>

                <select name="year" onchange="this.form.submit()" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none">
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected((int)$reportYear === (int)$y)>Năm {{ $y }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        {{-- KPI Cards --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Doanh thu cố định</p>
                <p class="mt-3 text-2xl font-bold text-slate-950">{{ number_format($fixedRevenue, 0, ',', '.') }}đ</p>
                <p class="mt-1 text-xs text-slate-400">Tiền phòng theo hóa đơn tháng này</p>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="text-sm text-emerald-700">Đã thu thực tế</p>
                <p class="mt-3 text-2xl font-bold text-emerald-700">{{ number_format($actualRevenue, 0, ',', '.') }}đ</p>
                <p class="mt-1 text-xs text-emerald-500">Từ hóa đơn tháng {{ $reportMonth }}/{{ $reportYear }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Tỷ lệ lấp đầy</p>
                <p class="mt-3 text-2xl font-bold text-amber-700">{{ $fillRate }}%</p>
                <p class="mt-1 text-xs text-slate-400">{{ $occupiedRooms }}/{{ $totalRooms }} phòng đang thuê</p>
            </div>
            <div class="rounded-lg border border-red-100 bg-red-50 p-5 shadow-sm">
                <p class="text-sm text-red-600">Công nợ phải thu</p>
                <p class="mt-3 text-2xl font-bold text-red-700">{{ number_format($monthlyReceivable, 0, ',', '.') }}đ</p>
                <p class="mt-1 text-xs text-red-400">Riêng hóa đơn tháng {{ $reportMonth }}/{{ $reportYear }} chưa thu</p>
                <a href="{{ route('admin.invoices.index', ['status' => 'unpaid', 'month' => $reportMonth, 'year' => $reportYear]) }}" class="mt-1 inline-block text-xs font-medium text-red-600 hover:underline">Xem →</a>
            </div>
        </div>

        {{-- Doanh thu tháng hiện tại -- bảng chi tiết rõ nhất --}}
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Doanh thu tháng {{ $reportMonth }}/{{ $reportYear }}</h3>
                <p class="mt-0.5 text-sm text-slate-500">Tất cả các khoản theo hóa đơn</p>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($breakdownItems as $item)
                    @php $pct = $totalInvoiced > 0 ? round($item['value'] / $totalInvoiced * 100, 1) : 0; @endphp
                    <div class="flex items-center gap-4 px-5 py-3">
                        <div class="flex w-28 shrink-0 items-center gap-2">
                            <span class="h-3 w-3 rounded-full {{ $item['color'] }}"></span>
                            <span class="text-sm text-slate-600">{{ $item['label'] }}</span>
                        </div>
                        <div class="flex-1">
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2 rounded-full {{ $item['color'] }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                        <span class="w-12 text-right text-xs text-slate-400">{{ $pct }}%</span>
                        <span class="w-32 text-right text-sm font-semibold text-slate-900">{{ number_format($item['value'], 0, ',', '.') }}đ</span>
                    </div>
                @endforeach
            </div>
            <div class="border-t border-slate-200 bg-slate-50 px-5 py-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-slate-700">Tổng hóa đơn phát sinh</span>
                    <span class="font-bold text-slate-950">{{ number_format($totalInvoiced, 0, ',', '.') }}đ</span>
                </div>
            </div>
            <div class="border-t border-emerald-200 bg-emerald-50 px-5 py-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-emerald-700">Đã thu (từ hóa đơn tháng {{ $reportMonth }}/{{ $reportYear }})</span>
                    <span class="font-bold text-emerald-700">{{ number_format($actualRevenue, 0, ',', '.') }}đ</span>
                </div>
            </div>
            <div class="border-t border-red-100 bg-red-50 px-5 py-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-red-600">Còn phải thu (theo hóa đơn chưa thu/thu một phần) hoặc đã được xóa nợ</span>
                    <span class="font-bold text-red-600">{{ number_format($remaining, 0, ',', '.') }}đ</span>
                </div>
            </div>
        </section>

        {{-- Chart lịch sử 6 tháng --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-slate-950">Cơ cấu doanh thu năm {{ $currentYear }}</h3>
                <p class="mt-1 text-sm text-slate-500">12 tháng theo danh mục</p>
            <div id="categoryChart" class="mt-4 min-h-[320px]"></div>
        </section>

        {{-- Bảng chi tiết tháng hiện tại --}}
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Chi tiết tháng {{ $reportMonth }}/{{ $reportYear }}</h3>
            </div>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <tbody class="divide-y divide-slate-100">
                    <tr class="bg-slate-50">
                        <td class="px-5 py-3 font-medium text-slate-700">Tiền phòng</td>
                        <td class="px-5 py-3 text-right font-semibold text-slate-950">{{ number_format($monthSummary->room_fee ?? 0, 0, ',', '.') }}đ</td>
                        <td class="px-5 py-3 text-right text-slate-400 text-xs">{{ $totalInvoiced > 0 ? round(($monthSummary->room_fee / $totalInvoiced) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-3 text-slate-600">Tiền điện</td>
                        <td class="px-5 py-3 text-right text-slate-950">{{ number_format($monthSummary->electricity_fee ?? 0, 0, ',', '.') }}đ</td>
                        <td class="px-5 py-3 text-right text-slate-400 text-xs">{{ $totalInvoiced > 0 ? round(($monthSummary->electricity_fee / $totalInvoiced) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-3 text-slate-600">Tiền nước</td>
                        <td class="px-5 py-3 text-right text-slate-950">{{ number_format($monthSummary->water_fee ?? 0, 0, ',', '.') }}đ</td>
                        <td class="px-5 py-3 text-right text-slate-400 text-xs">{{ $totalInvoiced > 0 ? round(($monthSummary->water_fee / $totalInvoiced) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-3 text-slate-600">Internet</td>
                        <td class="px-5 py-3 text-right text-slate-950">{{ number_format($monthSummary->internet_fee ?? 0, 0, ',', '.') }}đ</td>
                        <td class="px-5 py-3 text-right text-slate-400 text-xs">{{ $totalInvoiced > 0 ? round(($monthSummary->internet_fee / $totalInvoiced) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-3 text-slate-600">Dịch vụ</td>
                        <td class="px-5 py-3 text-right text-slate-950">{{ number_format($monthSummary->service_fee ?? 0, 0, ',', '.') }}đ</td>
                        <td class="px-5 py-3 text-right text-slate-400 text-xs">{{ $totalInvoiced > 0 ? round(($monthSummary->service_fee / $totalInvoiced) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr class="bg-emerald-50 font-semibold">
                        <td class="px-5 py-3 text-slate-900">Tổng hóa đơn phát sinh</td>
                        <td class="px-5 py-3 text-right text-slate-950">{{ number_format($totalInvoiced, 0, ',', '.') }}đ</td>
                        <td class="px-5 py-3 text-right text-slate-400 text-xs">100%</td>
                    </tr>
                    <tr class="bg-emerald-100">
                        <td class="px-5 py-3 font-semibold text-emerald-800">Đã thu thực tế</td>
                        <td class="px-5 py-3 text-right font-bold text-emerald-800">{{ number_format($actualRevenue, 0, ',', '.') }}đ</td>
                        <td class="px-5 py-3 text-right text-emerald-600 text-xs">{{ $totalInvoiced > 0 ? round(($actualRevenue / $totalInvoiced) * 100, 1) : 0 }}%</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    const fmt = val => Number(val).toLocaleString('vi-VN') + 'đ';

    new ApexCharts(document.querySelector("#categoryChart"), {
        chart: { type: 'bar', stacked: true, height: 320, toolbar: { show: false } },
        series: [
            { name: 'Tiền phòng',  data: @json($catRoom) },
            { name: 'Tiền điện',   data: @json($catElec) },
            { name: 'Tiền nước',   data: @json($catWater) },
            { name: 'Internet',    data: @json($catInternet) },
            { name: 'Dịch vụ',    data: @json($catService) },
        ],
        colors: ['#6366f1', '#f59e0b', '#06b6d4', '#10b981', '#8b5cf6'],
        xaxis: { categories: @json($categoryLabels) },
        yaxis: { labels: { formatter: val => Number(val/1000000).toFixed(1) + 'tr' } },
        tooltip: { y: { formatter: fmt } },
        legend: { position: 'top' },
        plotOptions: { bar: { columnWidth: '60%' } },
        dataLabels: { enabled: false }
    }).render();
</script>
@endpush
