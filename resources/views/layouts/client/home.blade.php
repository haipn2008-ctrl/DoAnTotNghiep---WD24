@extends('layouts.client.index')

@section('title', 'Tổng quan | Cổng khách thuê')
@section('page_title', 'Tổng quan')

@php
    $invoiceStatusLabels = [
        'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'partial' => ['label' => 'Thanh toán một phần', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
    ];

    $recentInvoiceStatus = $recentInvoice
        ? ($invoiceStatusLabels[$recentInvoice->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'])
        : null;
@endphp

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 p-6 text-white shadow-lg shadow-indigo-200/60 sm:p-8">
            <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-24 right-40 h-48 w-48 rounded-full bg-white/5"></div>
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div class="relative flex items-center gap-4">
                    <span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 sm:flex"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 11.25 12 4.5l8.25 6.75M6.5 9.5v10h11v-10M9.5 19.5v-6h5v6" /></svg></span>
                    <div>
                    <p class="text-sm font-medium text-indigo-100">Xin chào, {{ Auth::user()->name ?? 'khách thuê' }}</p>
                    <h2 class="mt-1 text-2xl font-bold sm:text-3xl">Thông tin thuê phòng</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-indigo-100">
                        Cổng khách thuê giúp bạn xem nhanh hợp đồng, hóa đơn, chỉ số điện nước và thông báo từ ban quản lý.
                    </p>
                    </div>
                </div>
                <a href="{{ auth()->user()->isActive() ? route('client.support.index') : route('client.invoices.index') }}" class="relative inline-flex h-11 w-fit items-center justify-center gap-2 rounded-xl border border-white/20 bg-white px-5 text-sm font-bold text-indigo-700 shadow-sm hover:-translate-y-0.5 hover:bg-indigo-50">
                    {{ auth()->user()->isActive() ? 'Gửi yêu cầu hỗ trợ' : 'Xem hóa đơn quyết toán' }}
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5 5 5-5 5" /></svg>
                </a>
            </div>
        </section>

        @unless ($tenant)
            <section class="rounded-lg border border-amber-200 bg-amber-50 p-5">
                <h3 class="font-semibold text-amber-950">Tài khoản chưa liên kết hồ sơ khách thuê</h3>
                <p class="mt-1 text-sm leading-6 text-amber-800">Vui lòng liên hệ ban quản lý để gắn tài khoản của bạn với hồ sơ khách thuê trong hệ thống.</p>
            </section>
        @endunless

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <a href="{{ auth()->user()->isActive() ? route('client.room.show') : route('client.contracts.index') }}" class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                <p class="text-sm font-medium text-slate-500">Phòng đang thuê</p>
                <p class="mt-3 text-2xl font-bold text-slate-950">{{ $activeContracts->isNotEmpty() ? $activeContracts->count().' phòng' : 'Chưa có' }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $activeContracts->isNotEmpty() ? $activeContracts->pluck('room.room_code')->filter()->map(fn ($code) => 'Phòng '.$code)->implode(' · ') : 'Chưa có hợp đồng đang hiệu lực.' }}
                </p>
            </a>

            <a href="{{ route('client.contracts.index') }}" class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                <p class="text-sm font-medium text-slate-500">Hợp đồng</p>
                <p class="mt-3 text-2xl font-bold {{ $activeContract ? 'text-emerald-600' : 'text-slate-950' }}">
                    {{ $activeContracts->isNotEmpty() ? $activeContracts->count().' hiệu lực' : 'Chưa có' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    @if ($activeContracts->isNotEmpty())
                        Xem chi tiết thời hạn của từng hợp đồng
                    @else
                        Thông tin hợp đồng sẽ hiển thị khi được ban quản lý tạo.
                    @endif
                </p>
            </a>

            <a href="{{ route('client.invoices.index') }}" class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                <p class="text-sm font-medium text-slate-500">Hóa đơn gần nhất</p>
                <p class="mt-3 text-2xl font-bold text-slate-950">
                    {{ $recentInvoice ? number_format($recentInvoice->payable_amount, 0, ',', '.') . 'đ' : '--' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $recentInvoice ? 'Kỳ ' . $recentInvoice->month . '/' . $recentInvoice->year : 'Chưa có hóa đơn.' }}
                </p>
            </a>

            <a href="{{ route('client.support.index') }}" class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                <p class="text-sm font-medium text-slate-500">Yêu cầu hỗ trợ</p>
                <p class="mt-3 text-2xl font-bold text-amber-600">{{ $supportRequests }}</p>
                <p class="mt-1 text-xs text-slate-500">Chưa có yêu cầu đang xử lý.</p>
            </a>
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <div id="invoices" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-slate-950">Hóa đơn và thanh toán</h3>
                    </div>
                    <a href="{{ route('client.invoices.index') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-800">Xem tất cả</a>
                </div>

                @if ($recentInvoice)
                    <div class="divide-y divide-slate-100">
                        @foreach ($openInvoices->take(5) as $invoice)
                            @php($status = $invoiceStatusLabels[$invoice->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'])
                            <a href="{{ route('client.invoices.show', $invoice) }}" class="flex flex-col justify-between gap-3 px-5 py-4 hover:bg-slate-50 sm:flex-row sm:items-center">
                                <div>
                                    <p class="font-semibold text-slate-950">Hóa đơn kỳ {{ $invoice->month }}/{{ $invoice->year }}</p>
                                    <p class="mt-1 text-sm text-slate-500">Phòng {{ $invoice->room->room_code ?? 'Không có' }} · Hạn {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-slate-950">{{ number_format($invoice->payable_amount, 0, ',', '.') }}đ</span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">{{ $status['label'] }}</span>
                                </div>
                            </a>
                        @endforeach

                        @if ($openInvoices->isEmpty())
                            <div class="px-5 py-8 text-center text-sm text-slate-500">
                                Không có hóa đơn đang chờ thanh toán.
                                @if ($recentInvoiceStatus)
                                    Hóa đơn gần nhất: <span class="font-semibold">{{ $recentInvoiceStatus['label'] }}</span>.
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-5">
                        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
                            <p class="font-semibold text-slate-950">Chưa có dữ liệu hóa đơn</p>
                        </div>
                    </div>
                @endif
            </div>

            <div id="support" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Kênh hỗ trợ</h3>
                </div>
                <div class="space-y-3 p-5 text-sm">
                    <a href="{{ auth()->user()->isActive() ? route('client.support.index') : route('client.invoices.index') }}" class="block rounded-lg bg-indigo-50 p-4 font-semibold text-indigo-700">{{ auth()->user()->isActive() ? 'Gửi và theo dõi yêu cầu hỗ trợ →' : 'Xem hóa đơn cần quyết toán →' }}</a>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="font-semibold text-slate-950">Hotline</p>
                        <p class="mt-1 text-slate-500">1900 xxxx</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="font-semibold text-slate-950">Thời gian hỗ trợ</p>
                        <p class="mt-1 text-slate-500">08:00 - 18:00 hằng ngày</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
