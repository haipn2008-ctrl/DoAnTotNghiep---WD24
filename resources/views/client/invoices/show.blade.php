@extends('layouts.client.index')

@section('title', 'Chi tiết hóa đơn | Cổng khách thuê')
@section('page_title', 'Chi tiết hóa đơn')

@php
    $statuses = [
        'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'partial' => ['label' => 'Thanh toán một phần', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'written_off' => ['label' => 'Đã xóa nợ theo quyết toán', 'class' => 'bg-violet-50 text-violet-700 ring-violet-200'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-slate-100 text-slate-700 ring-slate-300'],
    ];
    $status = $statuses[$invoice->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'];
    $methodLabels = ['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản', 'qr' => 'QR'];
    $paymentStatuses = ['pending' => 'Chờ xác nhận', 'success' => 'Thành công', 'failed' => 'Từ chối'];
    $pendingDelayRequest = $invoice->paymentDelayRequests->firstWhere('status', \App\Models\InvoicePaymentDelayRequest::STATUS_PENDING);
    $rejectedDelayRequest = $invoice->paymentDelayRequests->firstWhere('status', \App\Models\InvoicePaymentDelayRequest::STATUS_REJECTED);
    $canRequestDelay = $invoice->isOverdue() && $remainingAmount > $pendingAmount && !$pendingDelayRequest && !$rejectedDelayRequest;
@endphp

@section('content')
    <div class="space-y-6">
        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><p class="font-semibold">Chưa thể gửi xác nhận</p><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
            <div class="min-w-0">
                <a href="{{ route('client.invoices.index') }}" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"><i class="bx bx-left-arrow-alt text-lg"></i>Quay lại</a>
                <div class="mt-4 flex flex-wrap items-center gap-3"><h2 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ $invoice->isSupplemental() ? 'Hóa đơn bổ sung' : ($invoice->isDeposit() ? 'Hóa đơn tiền cọc' : ($invoice->isFirstMonthRent() ? 'Hóa đơn tiền phòng tháng đầu' : 'Hóa đơn tháng '.$invoice->month.'/'.$invoice->year)) }}</h2><span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset {{ $status['class'] }}">{{ $status['label'] }}</span></div>
                <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500"><span class="font-mono font-semibold text-slate-600">{{ $invoice->invoice_code }}</span><span class="text-slate-300">•</span><span>Phòng {{ $invoice->room->room_code ?? '-' }}</span><span class="text-slate-300">•</span><span>Hạn {{ $invoice->effective_due_date?->format('d/m/Y') }}</span></p>
                @if($invoice->parentInvoice)<p class="mt-1 text-sm text-slate-500">Bổ sung cho <a href="{{ route('client.invoices.show', $invoice->parentInvoice) }}" class="font-semibold text-indigo-700">{{ $invoice->parentInvoice->invoice_code }}</a></p>@endif
            </div>
            <a href="{{ route('client.invoices.print', $invoice) }}" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"><i class="bx bx-printer text-lg"></i>In hóa đơn</a>
            </div>
        </header>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><span class="absolute inset-x-0 top-0 h-1 bg-indigo-500"></span><p class="text-sm font-medium text-slate-500">Tổng hóa đơn</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($invoice->payable_amount, 0, ',', '.') }}đ</p><p class="mt-1 text-xs text-slate-400">Sau tất cả điều chỉnh</p></div>
            <div class="relative overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5 shadow-sm"><span class="absolute inset-x-0 top-0 h-1 bg-emerald-500"></span><p class="text-sm font-medium text-emerald-700">Đã thanh toán</p><p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($paidAmount, 0, ',', '.') }}đ</p><p class="mt-1 text-xs text-emerald-600/70">Khoản tiền đã được xác nhận</p></div>
            <div class="relative overflow-hidden rounded-2xl border {{ $remainingAmount > 0 ? 'border-rose-200 bg-rose-50/40' : 'border-emerald-200 bg-emerald-50/40' }} p-5 shadow-sm"><span class="absolute inset-x-0 top-0 h-1 {{ $remainingAmount > 0 ? 'bg-rose-500' : 'bg-emerald-500' }}"></span><p class="text-sm font-medium {{ $remainingAmount > 0 ? 'text-rose-700' : 'text-emerald-700' }}">Còn phải trả</p><p class="mt-2 text-2xl font-bold {{ $remainingAmount > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format($remainingAmount, 0, ',', '.') }}đ</p><p class="mt-1 text-xs {{ $remainingAmount > 0 ? 'text-rose-600/70' : 'text-emerald-600/70' }}">{{ $remainingAmount > 0 ? 'Số tiền chưa hoàn tất' : 'Hóa đơn đã hoàn tất' }}</p></div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50/60 px-5 py-4 sm:px-6">
                <div><h3 class="font-bold text-slate-950">Chi tiết các khoản thu</h3><p class="mt-1 text-sm text-slate-500">Hạn thanh toán <strong class="text-slate-700">{{ $invoice->effective_due_date?->format('d/m/Y') }}</strong>@if($invoice->payment_extension_until) · Hạn gốc {{ $invoice->due_date?->format('d/m/Y') }}@endif</p></div>
                <span class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm ring-1 ring-slate-200">{{ $invoice->details->count() + $invoice->adjustments->count() }} khoản</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Khoản tiền</th><th class="px-5 py-3 text-center">Đã sử dụng</th><th class="px-5 py-3 text-right">Đơn giá</th><th class="px-5 py-3 text-right">Thành tiền</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($invoice->details as $detail)
                            <tr class="transition hover:bg-indigo-50/30"><td class="px-5 py-4"><p class="font-semibold text-slate-950">{{ $detail->name }}</p>@if($detail->note)<p class="mt-1 text-xs text-slate-500">{{ $detail->note }}</p>@endif</td><td class="px-5 py-4 text-center text-slate-700">{{ number_format($detail->quantity, 0, ',', '.') }} {{ $detail->unit }}</td><td class="px-5 py-4 text-right text-slate-600">{{ number_format($detail->unit_price, 0, ',', '.') }}đ</td><td class="px-5 py-4 text-right font-bold text-slate-950">{{ number_format($detail->amount, 0, ',', '.') }}đ</td></tr>
                        @endforeach
                        @foreach($invoice->adjustments as $adjustment)
                            <tr class="bg-slate-50"><td class="px-5 py-4"><p class="font-semibold">{{ $adjustment->direction === \App\Models\InvoiceAdjustment::DIRECTION_CREDIT ? 'Điều chỉnh giảm' : 'Điều chỉnh tăng' }} · {{ $adjustment->adjustment_code }}</p><p class="mt-1 text-xs text-slate-500">{{ $adjustment->reason }}</p></td><td class="px-5 py-4 text-center text-slate-500">Phiếu điều chỉnh</td><td class="px-5 py-4 text-right">—</td><td class="px-5 py-4 text-right font-semibold">{{ $adjustment->direction === \App\Models\InvoiceAdjustment::DIRECTION_CREDIT ? '-' : '+' }}{{ number_format($adjustment->amount, 0, ',', '.') }}đ</td></tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50"><tr><th colspan="3" class="px-5 py-4 text-right">Tổng sau điều chỉnh</th><th class="px-5 py-4 text-right text-lg text-emerald-700">{{ number_format($invoice->payable_amount, 0, ',', '.') }}đ</th></tr></tfoot>
                </table>
            </div>
        </section>

        @if($invoice->creditsCreated->isNotEmpty())
            <section class="overflow-hidden rounded-lg border border-emerald-200 bg-white shadow-sm">
                <div class="border-b border-emerald-100 bg-emerald-50 px-5 py-4"><h3 class="font-semibold text-emerald-900">Khoản giảm cho hóa đơn tháng sau</h3></div>
                <div class="divide-y divide-slate-100">
                    @foreach($invoice->creditsCreated as $credit)
                        <div class="flex flex-wrap justify-between gap-3 px-5 py-4 text-sm"><div><p class="font-semibold text-emerald-700">{{ $credit->credit_code }}</p><p class="mt-1 text-slate-600">{{ $credit->reason }}</p></div><div class="text-right"><p class="font-bold text-emerald-700">-{{ number_format($credit->amount, 0, ',', '.') }}đ</p><p class="mt-1 text-xs text-slate-500">Còn chờ khấu trừ {{ number_format($credit->remaining_amount, 0, ',', '.') }}đ</p></div></div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($invoice->isOverdue() && $remainingAmount > 0)
            <section class="rounded-lg border {{ $rejectedDelayRequest ? 'border-rose-300 bg-rose-50' : 'border-amber-300 bg-amber-50' }} p-5 shadow-sm">
                <h3 class="font-bold {{ $rejectedDelayRequest ? 'text-rose-900' : 'text-amber-900' }}">Hóa đơn đã quá hạn thanh toán</h3>

                @if ($pendingDelayRequest)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-white p-4 text-sm text-slate-700">
                        <p class="font-semibold text-slate-900">Lý do đang chờ duyệt</p>
                        <p class="mt-2">{{ $pendingDelayRequest->reason }}</p>
                        <p class="mt-2 text-slate-500">Dự kiến thanh toán: {{ $pendingDelayRequest->promised_payment_date->format('d/m/Y') }}</p>
                    </div>
                @elseif ($rejectedDelayRequest)
                    <div class="mt-4 rounded-lg border border-rose-200 bg-white p-4 text-sm text-rose-800">
                        <p class="font-semibold">Ban quản lý đã từ chối lý do chậm thanh toán.</p>
                        <p class="mt-2">{{ $rejectedDelayRequest->review_note }}</p>
                    </div>
                @elseif ($canRequestDelay)
                    <form method="POST" action="{{ route('client.invoices.payment-delay-request.store', $invoice) }}" class="mt-4 grid gap-4 rounded-lg border border-amber-200 bg-white p-4 sm:grid-cols-2">
                        @csrf
                        <label class="sm:col-span-2 text-sm font-semibold text-slate-700">Lý do chậm thanh toán
                            <textarea name="reason" rows="4" minlength="10" maxlength="2000" required class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 font-normal" placeholder="Trình bày cụ thể lý do chưa thể thanh toán đúng hạn">{{ old('reason') }}</textarea>
                        </label>
                        <label class="text-sm font-semibold text-slate-700">Ngày dự kiến thanh toán
                            <input type="date" name="promised_payment_date" min="{{ today()->addDay()->toDateString() }}" value="{{ old('promised_payment_date') }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 font-normal">
                        </label>
                        <div class="flex items-end"><button class="h-11 w-full rounded-lg bg-amber-600 px-5 text-sm font-semibold text-white hover:bg-amber-700">Gửi lý do cho ban quản lý</button></div>
                    </form>
                @endif
            </section>
        @endif

        @if ($invoice->paymentDelayRequests->isNotEmpty())
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Lịch sử xin chậm thanh toán</h3></div>
                <div class="divide-y divide-slate-100">
                    @foreach ($invoice->paymentDelayRequests as $delayRequest)
                        <article class="p-5 text-sm"><div class="flex flex-wrap justify-between gap-2"><p class="font-semibold text-slate-900">Dự kiến {{ $delayRequest->promised_payment_date->format('d/m/Y') }}</p><span class="font-semibold {{ $delayRequest->status === 'approved' ? 'text-emerald-700' : ($delayRequest->status === 'rejected' ? 'text-rose-700' : 'text-amber-700') }}">{{ ['pending'=>'Chờ duyệt','approved'=>'Đã chấp nhận','rejected'=>'Đã từ chối'][$delayRequest->status] ?? $delayRequest->status }}</span></div><p class="mt-2 text-slate-600">{{ $delayRequest->reason }}</p>@if($delayRequest->review_note)<p class="mt-2 rounded-lg bg-slate-50 p-3 text-slate-700">Phản hồi: {{ $delayRequest->review_note }}</p>@endif</article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($remainingAmount > 0 && $invoice->canPay())
            <section class="grid gap-5 rounded-lg border border-indigo-200 bg-white p-5 shadow-sm lg:grid-cols-[320px_1fr]">
                <div>
                    <h3 class="text-xl font-bold text-slate-950">Gửi biên lai cho ban quản lý</h3>
                    <p class="mt-1 text-sm text-slate-500">Quét mã VietQR</p>
                    @if ($bankSetting->bank_id && $bankSetting->bank_account_no && $bankSetting->bank_account_name)
                        @php($qrBase = 'https://img.vietqr.io/image/'.$bankSetting->bank_id.'-'.$bankSetting->bank_account_no.'-compact2.png')
                        <div class="mt-4 rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-center">
                            <img id="paymentQr" src="{{ $qrBase }}?amount={{ (int) $availableAmount }}&addInfo={{ urlencode($paymentContent) }}&accountName={{ urlencode($bankSetting->bank_account_name) }}" alt="Mã VietQR thanh toán hóa đơn" class="mx-auto w-56 rounded-lg bg-white">
                            <p class="mt-2 text-xs text-slate-600">{{ $bankSetting->bank_account_name }} · {{ $bankSetting->bank_account_no }}</p>
                            <p class="mt-1 text-xs font-semibold text-indigo-700">Nội dung: {{ $paymentContent }}</p>
                        </div>
                    @else
                        <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Chưa có tài khoản nhận tiền.</div>
                    @endif
                    @if ($pendingAmount > 0)
                        <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Đang chờ xác nhận: <strong>{{ number_format($pendingAmount, 0, ',', '.') }}đ</strong></div>
                    @endif
                </div>
                @if ($availableAmount > 0)
                    <form method="POST" action="{{ route('client.invoices.payments.store', $invoice) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Số tiền thanh toán</label><input id="paymentAmount" type="number" inputmode="numeric" step="1" name="amount_paid" min="1" max="{{ (int) $availableAmount }}" value="{{ old('amount_paid', (int) $availableAmount) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"><p id="paymentAmountHint" class="mt-1 text-xs text-slate-500">Tối đa {{ number_format($availableAmount, 0, ',', '.') }}đ. Chỉ nhập số nguyên dương.</p></div>
                        <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ảnh biên lai</label><input type="file" name="proof_image" accept="image/*" capture="environment" required class="block w-full rounded-lg border border-slate-200 p-2 text-sm"></div>
                        <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Ghi chú (nếu có)</label><textarea name="note" rows="2" maxlength="1000" class="w-full rounded-lg border border-slate-200 px-3 py-2">{{ old('note') }}</textarea></div>
                        <div class="sm:col-span-2"><button id="paymentSubmit" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300">Gửi xác nhận thanh toán</button></div>
                    </form>
                @else
                    <div class="flex items-center justify-center rounded-lg bg-amber-50 p-5 text-center text-sm font-medium text-amber-800">Toàn bộ số tiền còn lại đang chờ ban quản lý xác nhận.</div>
                @endif
            </section>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50/60 px-5 py-4 sm:px-6"><div><h3 class="font-bold text-slate-950">Lịch sử thanh toán</h3><p class="mt-0.5 text-xs text-slate-500">Các giao dịch đã gửi cho hóa đơn này</p></div><span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $invoice->payments->count() }} giao dịch</span></div>
            <div class="divide-y divide-slate-100">
                @forelse ($invoice->payments as $payment)
                    <div class="flex flex-col justify-between gap-3 px-5 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:px-6"><div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $payment->status === 'success' ? 'bg-emerald-100 text-emerald-700' : ($payment->status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}"><i class="bx bx-receipt text-xl"></i></span><div><p class="font-bold text-slate-950">{{ number_format($payment->amount_paid, 0, ',', '.') }}đ</p><p class="mt-0.5 text-xs text-slate-500">{{ $methodLabels[$payment->payment_method] ?? 'Không xác định' }} · {{ $payment->payment_date?->format('d/m/Y') }}{{ $payment->transaction_code ? ' · Mã '.$payment->transaction_code : '' }}</p>@if($payment->review_note)<p class="mt-2 text-xs text-rose-600">Phản hồi: {{ $payment->review_note }}</p>@endif</div></div><div class="flex items-center gap-3 pl-13 sm:pl-0">@if($payment->proofImageExists())<a href="{{ route('client.invoices.payments.proof', $payment) }}" data-image-modal data-image-title="Biên lai thanh toán {{ $invoice->invoice_code }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">Xem biên lai</a>@elseif($payment->proof_image)<span class="text-xs font-semibold text-amber-700">Ảnh không còn tồn tại</span>@endif<span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $payment->status === 'success' ? 'bg-emerald-50 text-emerald-700' : ($payment->status === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">{{ $paymentStatuses[$payment->status] ?? 'Không xác định' }}</span></div></div>
                @empty
                    <div class="px-5 py-10 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><i class="bx bx-receipt text-2xl"></i></span><p class="mt-3 text-sm font-semibold text-slate-700">Chưa có giao dịch thanh toán</p></div>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        const amountInput = document.getElementById('paymentAmount');
        const qrImage = document.getElementById('paymentQr');
        if (amountInput) {
            const baseUrl = @json($qrBase ?? null);
            const content = @json($paymentContent);
            const accountName = @json($bankSetting->bank_account_name);
            const maximum = Number(amountInput.max);
            const hint = document.getElementById('paymentAmountHint');
            const submit = document.getElementById('paymentSubmit');
            const normalHint = `Tối đa ${new Intl.NumberFormat('vi-VN').format(maximum)}đ. Chỉ nhập số nguyên dương.`;

            const validateAmount = () => {
                const amount = Number(amountInput.value);
                const valid = Number.isInteger(amount) && amount >= 1 && amount <= maximum;
                const message = amount > maximum
                    ? `Số tiền không được vượt quá ${new Intl.NumberFormat('vi-VN').format(maximum)}đ.`
                    : 'Vui lòng nhập số tiền nguyên dương hợp lệ.';
                amountInput.setCustomValidity(valid ? '' : message);
                amountInput.classList.toggle('border-rose-400', !valid);
                hint.textContent = valid ? normalHint : message;
                hint.className = `mt-1 text-xs ${valid ? 'text-slate-500' : 'font-semibold text-rose-600'}`;
                submit.disabled = !valid;

                if (valid && qrImage && baseUrl) {
                    qrImage.src = `${baseUrl}?amount=${amount}&addInfo=${encodeURIComponent(content)}&accountName=${encodeURIComponent(accountName)}`;
                }
            };

            amountInput.addEventListener('keydown', event => {
                if (['e', 'E', '+', '-', '.', ','].includes(event.key)) event.preventDefault();
            });
            amountInput.addEventListener('input', validateAmount);
            validateAmount();
        }
    </script>
@endpush
