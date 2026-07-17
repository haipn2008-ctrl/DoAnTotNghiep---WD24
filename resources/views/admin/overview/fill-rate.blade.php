@extends('layouts.admin.index')

@section('title', 'Tỷ lệ lấp đầy | Quản lý phòng trọ')
@section('page_title', 'Tỷ lệ lấp đầy')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-slate-500">Tổng quan</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Tỷ lệ lấp đầy phòng</h2>
        </div>

        <section class="grid gap-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-3">
            @foreach ([
                ['label' => 'Đã cho thuê', 'count' => $occupiedRooms, 'percent' => $occupiedPercent, 'color' => 'bg-emerald-500'],
                ['label' => 'Còn trống', 'count' => $availableRooms, 'percent' => $availablePercent, 'color' => 'bg-sky-500'],
                ['label' => 'Bảo trì', 'count' => $maintenanceRooms, 'percent' => $maintenancePercent, 'color' => 'bg-amber-500'],
            ] as $item)
                <div>
                    <div class="flex justify-between text-sm"><span class="font-semibold text-slate-700">{{ $item['label'] }}</span><span class="text-slate-500">{{ $item['count'] }}/{{ $totalRooms }} phòng</span></div>
                    <div class="mt-3 h-3 rounded-full bg-slate-100">
                        <div class="h-3 rounded-full {{ $item['color'] }}" style="width: {{ min($item['percent'], 100) }}%"></div>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-slate-950">{{ $item['percent'] }}%</p>
                </div>
            @endforeach
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-slate-950">Biểu đồ tỷ lệ lấp đầy</h3>
            <div id="fillRateChart" class="mt-4 min-h-[350px]"></div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        new ApexCharts(document.querySelector("#fillRateChart"), {
            chart: { type: 'pie', height: 350 },
            series: [{{ $occupiedPercent }}, {{ $availablePercent }}, {{ $maintenancePercent }}],
            labels: ['Đã cho thuê', 'Còn trống', 'Bảo trì'],
            colors: ['#059669', '#0284c7', '#d97706'],
            legend: { position: 'bottom' },
            dataLabels: { enabled: true, formatter: val => val.toFixed(1) + '%' }
        }).render();
    </script>
@endpush
