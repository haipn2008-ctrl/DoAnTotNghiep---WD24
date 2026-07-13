@extends('layouts.admin.index')

@section('title', 'Thống kê phòng | Quản lý phòng trọ')
@section('page_title', 'Thống kê phòng')

@php
    $occupiedPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
    $availablePercent = $totalRooms > 0 ? round(($availableRooms / $totalRooms) * 100, 1) : 0;
    $maintenancePercent = $totalRooms > 0 ? round(($maintenanceRooms / $totalRooms) * 100, 1) : 0;
@endphp

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-slate-500">Tổng quan</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Thống kê số phòng</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Tổng số phòng</p><p class="mt-3 text-3xl font-bold text-slate-950">{{ $totalRooms }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Phòng đã cho thuê</p><p class="mt-3 text-3xl font-bold text-emerald-700">{{ $occupiedRooms }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Phòng còn trống</p><p class="mt-3 text-3xl font-bold text-sky-700">{{ $availableRooms }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Phòng bảo trì</p><p class="mt-3 text-3xl font-bold text-amber-700">{{ $maintenanceRooms }}</p></div>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-slate-950">Biểu đồ trạng thái phòng</h3>
            <div id="roomStatusChart" class="mt-4 min-h-[350px]"></div>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Chi tiết trạng thái phòng</h3></div>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <tbody class="divide-y divide-slate-100">
                    <tr><td class="px-5 py-4 text-slate-600">Đã cho thuê</td><td class="px-5 py-4 text-right">{{ $occupiedRooms }}</td><td class="px-5 py-4 text-right font-semibold">{{ $occupiedPercent }}%</td></tr>
                    <tr><td class="px-5 py-4 text-slate-600">Còn trống</td><td class="px-5 py-4 text-right">{{ $availableRooms }}</td><td class="px-5 py-4 text-right font-semibold">{{ $availablePercent }}%</td></tr>
                    <tr><td class="px-5 py-4 text-slate-600">Bảo trì</td><td class="px-5 py-4 text-right">{{ $maintenanceRooms }}</td><td class="px-5 py-4 text-right font-semibold">{{ $maintenancePercent }}%</td></tr>
                </tbody>
            </table>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        new ApexCharts(document.querySelector("#roomStatusChart"), {
            chart: { type: 'donut', height: 350 },
            series: [{{ $occupiedRooms }}, {{ $availableRooms }}, {{ $maintenanceRooms }}],
            labels: ['Đã cho thuê', 'Còn trống', 'Bảo trì'],
            colors: ['#059669', '#0284c7', '#d97706'],
            legend: { position: 'bottom' },
            dataLabels: { enabled: true, formatter: val => Math.round(val) + '%' }
        }).render();
    </script>
@endpush
