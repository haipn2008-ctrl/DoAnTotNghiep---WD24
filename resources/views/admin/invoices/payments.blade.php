@extends('layouts.admin.index')

@section('title', 'Xác nhận thanh toán | Quản lý phòng trọ')
@section('page_title', 'Xác nhận thanh toán')

@php
    $statuses = [
        'pending' => ['label' => 'Chờ xác nhận', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'success' => ['label' => 'Thành công', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'failed' => ['label' => 'Đã từ chối', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
    ];
    $methods = ['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản', 'qr' => 'QR'];
@endphp

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div><p class="text-sm font-medium text-slate-500">Quản lý công nợ</p><h2 class="mt-1 text-2xl font-bold text-slate-950">Xác nhận thanh toán</h2><p class="mt-2 text-sm text-slate-500">Kiểm tra mã giao dịch và biên lai trước khi ghi nhận tiền.</p></div>
            <div class="flex gap-2"><a href="{{ route('admin.invoices.payments.export', request()->query()) }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Xuất CSV</a><a href="{{ route('admin.invoices.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hóa đơn</a></div>
        </div>

        @if ($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif

        <form method="GET" class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_180px_180px_auto] md:items-end">
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Tìm kiếm</label><input name="keyword" value="{{ request('keyword') }}" placeholder="Mã giao dịch, hóa đơn, phòng..." class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Phương thức</label><select name="method" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"><option value="">Tất cả</option>@foreach($methods as $value => $label)<option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Trạng thái</label><select name="status" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm"><option value="">Tất cả</option>@foreach($statuses as $value => $meta)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $meta['label'] }}</option>@endforeach</select></div>
            <button class="h-11 rounded-lg bg-slate-900 px-5 text-sm font-semibold text-white">Lọc</button>
        </form>

        <div class="space-y-4">
            @forelse($payments as $payment)
                @php($status = $statuses[$payment->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'])
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                        <div class="grid flex-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div><p class="text-xs font-semibold uppercase text-slate-500">Giao dịch</p><p class="mt-1 font-bold text-slate-950">{{ $payment->transaction_code ?? 'GD-'.$payment->id }}</p><p class="mt-1 text-xs text-slate-500">{{ $methods[$payment->payment_method] ?? 'Không xác định' }} · {{ $payment->payment_date?->format('d/m/Y') }}</p></div>
                            <div><p class="text-xs font-semibold uppercase text-slate-500">Hóa đơn</p><a href="{{ route('admin.invoices.show', $payment->invoice) }}" class="mt-1 block font-bold text-indigo-700">{{ $payment->invoice->invoice_code }}</a><p class="mt-1 text-xs text-slate-500">Phòng {{ $payment->invoice->room->room_code ?? '-' }}</p></div>
                            <div><p class="text-xs font-semibold uppercase text-slate-500">Khách thuê</p><p class="mt-1 font-bold text-slate-950">{{ $payment->invoice->contract->tenant->full_name ?? '-' }}</p><p class="mt-1 text-xs text-slate-500">{{ $payment->submitter?->email ?? 'Admin ghi nhận' }}</p></div>
                            <div><p class="text-xs font-semibold uppercase text-slate-500">Số tiền</p><p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format($payment->amount_paid, 0, ',', '.') }}đ</p><span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">{{ $status['label'] }}</span></div>
                        </div>
                        <div class="shrink-0 lg:w-28">
                            @if($payment->proofImageExists())
                                <a href="{{ route('admin.invoices.payments.proof', $payment) }}" data-image-modal data-image-title="Biên lai thanh toán {{ $payment->invoice?->invoice_code }}" class="block w-28">
                                    <img src="{{ route('admin.invoices.payments.proof', $payment) }}" alt="Biên lai" class="h-28 w-28 rounded-lg object-cover ring-1 ring-slate-200">
                                    <span class="mt-1 block text-center text-xs font-semibold text-indigo-700">Xem ảnh lớn</span>
                                </a>
                            @elseif($payment->proof_image)
                                <span class="block text-center text-xs font-semibold text-amber-700">Ảnh không còn tồn tại</span>
                            @endif
                        </div>
                    </div>

                    @if($payment->note)<p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Khách ghi chú: {{ $payment->note }}</p>@endif
                    @if($payment->review_note)<p class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">Lý do từ chối: {{ $payment->review_note }}</p>@endif
                    @if($payment->reviewed_at)<p class="mt-3 text-xs text-slate-500">Xử lý bởi {{ $payment->confirmer?->name ?? 'Quản trị viên' }} lúc {{ $payment->reviewed_at->format('H:i d/m/Y') }}.</p>@endif

                    @if($payment->status === 'pending')
                        <div class="mt-4 grid gap-3 border-t border-slate-200 pt-4 lg:grid-cols-[auto_1fr]">
                            <form method="POST" action="{{ route('admin.invoices.payments.approve', $payment) }}">@csrf<button class="h-11 w-full rounded-lg bg-emerald-600 px-5 text-sm font-semibold text-white hover:bg-emerald-700">Duyệt thanh toán</button></form>
                            <form method="POST" action="{{ route('admin.invoices.payments.reject', $payment) }}" class="flex flex-col gap-2 sm:flex-row">@csrf<input name="review_note" required maxlength="1000" placeholder="Nhập lý do từ chối..." class="h-11 min-w-0 flex-1 rounded-lg border border-slate-200 px-3 text-sm"><button class="h-11 rounded-lg border border-rose-200 bg-rose-50 px-5 text-sm font-semibold text-rose-700">Từ chối</button></form>
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-lg border border-slate-200 bg-white p-12 text-center text-slate-500">Không có thanh toán phù hợp.</div>
            @endforelse
        </div>
        @if ($payments->hasPages())<div>{{ $payments->links() }}</div>@endif
    </div>
@endsection
