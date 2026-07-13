@extends('layouts.admin.index')

@section('title', 'Thống kê doanh thu | Quản lý phòng trọ')
@section('page_title', 'Thống kê doanh thu')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-slate-500">Tổng quan</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Thống kê tổng doanh thu</h2>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tổng doanh thu</p>
                <p class="mt-3 text-3xl font-bold text-emerald-700">{{ number_format($totalRevenue, 0, ',', '.') }}đ</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Doanh thu tháng này</p>
                <p class="mt-3 text-3xl font-bold text-indigo-700">{{ number_format($monthRevenue, 0, ',', '.') }}đ</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Doanh thu hôm nay</p>
                <p class="mt-3 text-3xl font-bold text-sky-700">{{ number_format($todayRevenue, 0, ',', '.') }}đ</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Chi tiết doanh thu</h3>
            </div>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <tbody class="divide-y divide-slate-100">
                    <tr><td class="px-5 py-4 text-slate-600">Tổng doanh thu</td><td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($totalRevenue, 0, ',', '.') }}đ</td></tr>
                    <tr><td class="px-5 py-4 text-slate-600">Doanh thu tháng này</td><td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($monthRevenue, 0, ',', '.') }}đ</td></tr>
                    <tr><td class="px-5 py-4 text-slate-600">Doanh thu hôm nay</td><td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($todayRevenue, 0, ',', '.') }}đ</td></tr>
                </tbody>
            </table>
        </section>
    </div>
@endsection
