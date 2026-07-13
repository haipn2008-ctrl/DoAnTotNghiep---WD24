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
        ? ($invoiceStatusLabels[$recentInvoice->status] ?? ['label' => $recentInvoice->status, 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'])
        : null;
@endphp

@section('content')
    <div class="space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500">Xin chào, {{ Auth::user()->name ?? 'khách thuê' }}</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-950">Theo dõi thông tin thuê phòng của bạn</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Cổng khách thuê giúp bạn xem nhanh hợp đồng, hóa đơn, chỉ số điện nước và thông báo từ ban quản lý.
                    </p>
                </div>
                <a href="#support" class="inline-flex h-11 w-fit items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                    Gửi yêu cầu hỗ trợ
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
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Phòng hiện tại</p>
                <p class="mt-3 text-2xl font-bold text-slate-950">{{ $activeContract?->room?->room_code ?? 'Chưa có' }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $activeContract ? 'Đang thuê theo hợp đồng ' . $activeContract->contract_code : 'Chưa có hợp đồng đang hiệu lực.' }}
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Hợp đồng</p>
                <p class="mt-3 text-2xl font-bold {{ $activeContract ? 'text-emerald-600' : 'text-slate-950' }}">
                    {{ $activeContract ? 'Hiệu lực' : 'Chưa có' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    @if ($activeContract)
                        Đến ngày {{ \Carbon\Carbon::parse($activeContract->end_date)->format('d/m/Y') }}
                    @else
                        Thông tin hợp đồng sẽ hiển thị khi được ban quản lý tạo.
                    @endif
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Hóa đơn gần nhất</p>
                <p class="mt-3 text-2xl font-bold text-slate-950">
                    {{ $recentInvoice ? number_format($recentInvoice->total_amount, 0, ',', '.') . 'đ' : '--' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $recentInvoice ? 'Kỳ ' . $recentInvoice->month . '/' . $recentInvoice->year : 'Chưa có hóa đơn.' }}
                </p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Yêu cầu hỗ trợ</p>
                <p class="mt-3 text-2xl font-bold text-amber-600">{{ $supportRequests }}</p>
                <p class="mt-1 text-xs text-slate-500">Chưa có yêu cầu đang xử lý.</p>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <div id="invoices" class="rounded-lg border border-slate-200 bg-white shadow-sm xl:col-span-2">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Hóa đơn và thanh toán</h3>
                    <p class="mt-1 text-sm text-slate-500">Các khoản cần thanh toán gần đây.</p>
                </div>

                @if ($recentInvoice)
                    <div class="divide-y divide-slate-100">
                        @foreach ($openInvoices->take(5) as $invoice)
                            @php($status = $invoiceStatusLabels[$invoice->status] ?? ['label' => $invoice->status, 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'])
                            <div class="flex flex-col justify-between gap-3 px-5 py-4 sm:flex-row sm:items-center">
                                <div>
                                    <p class="font-semibold text-slate-950">Hóa đơn kỳ {{ $invoice->month }}/{{ $invoice->year }}</p>
                                    <p class="mt-1 text-sm text-slate-500">Phòng {{ $invoice->room->room_code ?? 'N/A' }} · Hạn {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-slate-950">{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">{{ $status['label'] }}</span>
                                </div>
                            </div>
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
                            <p class="mt-2 text-sm text-slate-500">Sau khi ban quản lý phát hành hóa đơn, bạn có thể xem chi tiết và trạng thái thanh toán tại mục này.</p>
                        </div>
                    </div>
                @endif
            </div>

            <div id="support" class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Kênh hỗ trợ</h3>
                    <p class="mt-1 text-sm text-slate-500">Liên hệ ban quản lý khi cần xử lý vấn đề phát sinh.</p>
                </div>
                <div class="space-y-3 p-5 text-sm">
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
