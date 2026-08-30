@php
    $representative = $contract->currentMembers->firstWhere('role', \App\Models\ContractTenant::ROLE_REPRESENTATIVE);
    $depositInvoice = $contract->invoices->firstWhere('invoice_type', \App\Models\Invoice::TYPE_DEPOSIT);
    $successfulPayments = $depositInvoice
        ? $depositInvoice->payments->where('status', \App\Models\Payment::STATUS_SUCCESS)->sortByDesc('payment_date')
        : collect();
    $paymentMethodLabels = [
        \App\Models\Payment::METHOD_CASH => 'Tiền mặt',
        \App\Models\Payment::METHOD_BANK_TRANSFER => 'Chuyển khoản',
        \App\Models\Payment::METHOD_QR => 'Quét mã QR',
    ];
    $adminPaymentMethods = [
        \App\Models\Payment::METHOD_CASH => 'Tiền mặt',
        \App\Models\Payment::METHOD_BANK_TRANSFER => 'Chuyển khoản',
    ];
    $invoiceStatusLabels = [
        \App\Models\Invoice::STATUS_UNPAID => 'Chưa thanh toán',
        \App\Models\Invoice::STATUS_PARTIAL => 'Đã thu một phần',
        \App\Models\Invoice::STATUS_PAID => 'Đã thanh toán',
        \App\Models\Invoice::STATUS_WRITTEN_OFF => 'Đã xử lý công nợ',
    ];
@endphp

<div class="grid overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm sm:grid-cols-2 lg:grid-cols-4">
    <div class="border-b border-slate-100 px-4 py-3 sm:border-r lg:border-b-0">
        <p class="text-xs font-medium text-slate-500">Phòng</p>
        <p class="mt-1 font-bold text-slate-950">{{ $contract->room?->room_code ?? 'Chưa xác định' }}</p>
    </div>
    <div class="border-b border-slate-100 px-4 py-3 lg:border-b-0 lg:border-r">
        <p class="text-xs font-medium text-slate-500">Người thuê đại diện</p>
        <p class="mt-1 truncate font-bold text-slate-950">{{ $representative?->full_name ?? $contract->tenant?->full_name ?? 'Chưa xác định' }}</p>
    </div>
    <div class="border-b border-slate-100 px-4 py-3 sm:border-b-0 sm:border-r">
        <p class="text-xs font-medium text-slate-500">Hạn đóng cọc</p>
        <p class="mt-1 font-bold {{ $contract->isDepositOverdue() ? 'text-rose-700' : 'text-slate-950' }}">{{ $contract->deposit_due_at?->format('d/m/Y H:i') ?? '—' }}</p>
    </div>
    <div class="px-4 py-3">
        <p class="text-xs font-medium text-slate-500">Còn phải thu</p>
        <p class="mt-1 font-bold text-orange-700">{{ number_format($depositRemaining, 0, ',', '.') }}đ</p>
    </div>
</div>

<section class="overflow-hidden rounded-lg border border-orange-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-orange-100 bg-orange-50 px-4 py-3">
        <h3 class="font-semibold text-orange-950">Thu tiền cọc</h3>
        @if($depositInvoice)
            <a href="{{ route('admin.invoices.show', $depositInvoice) }}" class="text-sm font-semibold text-orange-700 hover:text-orange-900">{{ $depositInvoice->invoice_code }} <i class="bx bx-right-arrow-alt align-middle text-lg"></i></a>
        @endif
    </div>

    @if(!$depositInvoice)
        <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-slate-500">Số tiền cần thu</p>
                <p class="mt-1 text-2xl font-bold text-slate-950">{{ number_format($contract->deposit_amount, 0, ',', '.') }}đ</p>
            </div>
            <form class="lifecycle-form" method="POST" action="{{ route('admin.contracts.deposit-invoice.issue', $contract) }}">
                @csrf
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-orange-600 px-5 text-sm font-semibold text-white hover:bg-orange-700">
                    <i class="bx bx-receipt text-lg"></i>
                    Phát hành hóa đơn cọc
                </button>
            </form>
        </div>
    @else
        <div class="grid lg:grid-cols-[minmax(0,1fr)_minmax(340px,0.9fr)]">
            <div class="border-b border-slate-100 p-4 lg:border-b-0 lg:border-r">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-slate-50 px-3 py-3">
                        <p class="text-xs text-slate-500">Tiền cọc</p>
                        <p class="mt-1 font-bold text-slate-950">{{ number_format($depositInvoice->total_amount, 0, ',', '.') }}đ</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 px-3 py-3">
                        <p class="text-xs text-emerald-700">Đã thu</p>
                        <p class="mt-1 font-bold text-emerald-800">{{ number_format($depositPaid, 0, ',', '.') }}đ</p>
                    </div>
                    <div class="col-span-2 rounded-lg bg-orange-50 px-3 py-3 sm:col-span-1">
                        <p class="text-xs text-orange-700">Còn thiếu</p>
                        <p class="mt-1 font-bold text-orange-800">{{ number_format($depositRemaining, 0, ',', '.') }}đ</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-600">
                    <span>Trạng thái: <strong class="text-slate-900">{{ $invoiceStatusLabels[$depositInvoice->status] ?? 'Chưa xác định' }}</strong></span>
                    <span>Hạn thu: <strong class="text-slate-900">{{ $depositInvoice->due_date?->format('d/m/Y') ?? '—' }}</strong></span>
                </div>

                @if($successfulPayments->isNotEmpty())
                    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                <tr><th class="px-3 py-2">Ngày thu</th><th class="px-3 py-2">Hình thức</th><th class="px-3 py-2">Minh chứng</th><th class="px-3 py-2 text-right">Số tiền</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($successfulPayments as $payment)
                                    <tr>
                                        <td class="whitespace-nowrap px-3 py-2 text-slate-600">{{ $payment->payment_date?->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $paymentMethodLabels[$payment->payment_method] ?? 'Không xác định' }}</td>
                                        <td class="px-3 py-2">
                                            @if($payment->proofImageExists())
                                                <a href="{{ route('admin.invoices.payments.proof', $payment) }}" data-image-modal data-image-title="Ảnh minh chứng thanh toán {{ $depositInvoice->invoice_code }}" class="font-semibold text-indigo-700">Xem ảnh</a>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2 text-right font-semibold text-slate-950">{{ number_format($payment->amount_paid, 0, ',', '.') }}đ</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="p-4">
                <h4 class="font-semibold text-slate-950">Ghi nhận thanh toán</h4>
                <form class="lifecycle-form mt-3 grid gap-3 sm:grid-cols-2" method="POST" enctype="multipart/form-data" action="{{ route('admin.invoices.payments.store', $depositInvoice) }}">
                    @csrf
                    <input type="hidden" name="return_to_contract" value="1">
                    <label class="block text-sm font-medium text-slate-700">
                        Số tiền
                        <input type="number" name="amount_paid" min="1" max="{{ (int) $depositRemaining }}" value="{{ old('amount_paid', (int) $depositRemaining) }}" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-100">
                    </label>
                    <label class="block text-sm font-medium text-slate-700">
                        Ngày thu
                        <input type="date" name="payment_date" max="{{ today()->toDateString() }}" value="{{ old('payment_date', today()->toDateString()) }}" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-100">
                    </label>
                    <label class="block text-sm font-medium text-slate-700">
                        Hình thức
                        <select name="payment_method" required onchange="this.form.querySelector('[name=proof_image]').required=this.value==='{{ \App\Models\Payment::METHOD_BANK_TRANSFER }}'" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-100">
                            @foreach($adminPaymentMethods as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium text-slate-700">
                        Ảnh minh chứng
                        <input type="file" name="proof_image" accept="image/jpeg,image/png,image/webp" @required(old('payment_method') === \App\Models\Payment::METHOD_BANK_TRANSFER) class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-white text-xs file:mr-2 file:border-0 file:bg-orange-50 file:px-3 file:py-2.5 file:font-semibold file:text-orange-700">
                        <span class="mt-1 block text-xs text-slate-500">Bắt buộc khi chuyển khoản · JPG, PNG, WEBP · tối đa 5 MB</span>
                    </label>
                    <button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 text-sm font-semibold text-white hover:bg-orange-700 sm:col-span-2">
                        <i class="bx bx-check-circle text-lg"></i>
                        Xác nhận đã thu
                    </button>
                </form>
            </div>
        </div>
    @endif
</section>

<div class="grid gap-4 lg:grid-cols-2">
    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="font-semibold text-slate-950">Thông tin hợp đồng</h3>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $contract->currentMembers->count() }}/{{ $contract->room?->max_people ?? 0 }} người</span>
        </div>
        <dl class="grid grid-cols-2 gap-x-5 gap-y-3 p-4 text-sm">
            <div><dt class="text-slate-500">Thời hạn thuê</dt><dd class="mt-1 font-semibold text-slate-950">{{ $contract->start_date?->format('d/m/Y') }} – {{ $contract->end_date?->format('d/m/Y') }}</dd></div>
            <div><dt class="text-slate-500">Ngày nhận phòng</dt><dd class="mt-1 font-semibold text-slate-950">{{ $contract->scheduled_move_in_date?->format('d/m/Y') ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Tiền phòng</dt><dd class="mt-1 font-semibold text-slate-950">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ/tháng</dd></div>
            <div><dt class="text-slate-500">Ngày ký</dt><dd class="mt-1 font-semibold text-slate-950">{{ $contract->signed_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
        </dl>
    </section>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 font-semibold text-slate-950">Người thuê</div>
        <div class="grid gap-2 p-4 sm:grid-cols-2">
            @foreach($contract->currentMembers as $member)
                <article class="min-w-0 rounded-lg bg-slate-50 px-3 py-2.5 text-sm">
                    <p class="truncate font-semibold text-slate-950">{{ $member->full_name }}</p>
                    @if($member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE)<p class="mt-0.5 text-xs font-medium text-indigo-700">Người thuê đại diện · Tài khoản liên hệ</p>@else<p class="mt-0.5 text-xs text-slate-500">Người thuê · Không cấp tài khoản riêng</p>@endif
                    <p class="mt-1 truncate text-xs text-slate-500">{{ $member->identity_number ?: 'Chưa có CCCD' }} · {{ $member->phone ?: 'Chưa có số điện thoại' }}</p>
                </article>
            @endforeach
        </div>
    </section>
</div>

<section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-4 py-3 font-semibold text-slate-950">Lịch sử hợp đồng</div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-4 py-2.5">Thời điểm</th><th class="px-4 py-2.5">Trạng thái</th><th class="px-4 py-2.5">Thao tác</th><th class="px-4 py-2.5">Người thực hiện</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($contract->statusHistories as $history)
                    <tr><td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $history->performed_at?->format('d/m/Y H:i') }}</td><td class="whitespace-nowrap px-4 py-3">{{ $history->from_status ? ($statusLabels[$history->from_status] ?? 'Không xác định') : 'Khởi tạo' }} → <strong>{{ $statusLabels[$history->to_status] ?? 'Không xác định' }}</strong></td><td class="px-4 py-3">{{ $actionLabels[$history->action] ?? 'Cập nhật hợp đồng' }}</td><td class="px-4 py-3 text-slate-600">{{ $history->performer?->name ?? 'Hệ thống' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Chưa có lịch sử.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<dialog id="cancel-contract-dialog" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-xl bg-white p-0 text-slate-700 shadow-2xl backdrop:bg-slate-900/50">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="font-semibold text-slate-950">Hủy hợp đồng</h3>
        <button type="button" onclick="this.closest('dialog').close()" aria-label="Đóng" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="bx bx-x text-xl"></i></button>
    </div>
    <form class="lifecycle-form p-5" method="POST" action="{{ route('admin.contracts.cancel', $contract) }}">
        @csrf
        <label for="cancel_reason" class="block text-sm font-semibold text-slate-700">Lý do hủy</label>
        <textarea id="cancel_reason" name="cancel_reason" rows="3" required maxlength="1000" placeholder="Nhập lý do" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-rose-500 focus:ring-4 focus:ring-rose-100"></textarea>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Đóng</button>
            <button type="submit" class="rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-800">Xác nhận hủy</button>
        </div>
    </form>
</dialog>
