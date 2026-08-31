@extends('layouts.admin.index')

@section('title', 'Chi tiết hóa đơn | Quản lý phòng trọ')
@section('page_title', 'Chi tiết hóa đơn')

@php
    $statusMap = [
        'unpaid' => ['text' => 'Chưa thanh toán', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
        'partial' => ['text' => 'Thanh toán một phần', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200', 'dot' => 'bg-sky-500'],
        'paid' => ['text' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'written_off' => ['text' => 'Đã xóa nợ theo quyết toán', 'class' => 'bg-violet-50 text-violet-700 ring-violet-200', 'dot' => 'bg-violet-500'],
        'cancelled' => ['text' => 'Đã hủy', 'class' => 'bg-slate-100 text-slate-700 ring-slate-300', 'dot' => 'bg-slate-500'],
    ];
    $statusData = $statusMap[$invoice->status] ?? ['text' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'];
    $paymentStatusMap = [
        \App\Models\Payment::STATUS_PENDING => ['text' => 'Chờ xác nhận', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        \App\Models\Payment::STATUS_SUCCESS => ['text' => 'Đã xác nhận', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        \App\Models\Payment::STATUS_FAILED => ['text' => 'Đã từ chối', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
    ];
@endphp

@section('content')
    <div class="space-y-6">
        @if($invoice->isCancelled())
            <div class="rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
                <p class="font-semibold">Hóa đơn đã bị hủy lúc {{ $invoice->cancelled_at?->format('d/m/Y H:i') }}.</p>
                <p class="mt-1">Lý do: {{ $invoice->cancellation_reason }}</p>
                <p class="mt-1 text-xs">Người thao tác: {{ $invoice->canceller?->name ?? 'Tài khoản đã ngừng hoạt động' }}</p>
            </div>
        @endif
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">{{ $invoice->invoice_code ?? 'HDON'.str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">{{ $invoice->isSupplemental() ? 'Chi tiết hóa đơn bổ sung' : 'Chi tiết hóa đơn' }}</h2>
                @if($invoice->parentInvoice)<p class="mt-1 text-sm text-slate-500">Bổ sung cho <a class="font-semibold text-indigo-700" href="{{ route('admin.invoices.show', $invoice->parentInvoice) }}">{{ $invoice->parentInvoice->invoice_code }}</a></p>@endif
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.invoices.print', $invoice) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-printer text-lg"></i>
                    In hóa đơn
                </a>
                <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-arrow-back text-lg"></i>
                    Quay lại
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="space-y-6">
                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h3 class="font-semibold text-slate-950">Các khoản thu</h3>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusData['class'] }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $statusData['dot'] }}"></span>
                            {{ $statusData['text'] }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Khoản thu</th>
                                    <th class="px-5 py-3 text-center">Đã sử dụng</th>
                                    <th class="px-5 py-3 text-right">Đơn giá</th>
                                    <th class="px-5 py-3 text-right">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($invoice->details as $detail)
                                    <tr>
                                        <td class="px-5 py-4">
                                            <p class="font-semibold text-slate-950">{{ $detail->name }}</p>
                                            @if ($detail->note)
                                                <p class="mt-1 text-xs text-slate-500">{{ $detail->note }}</p>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-center font-medium text-slate-700">{{ number_format($detail->quantity, 0, ',', '.') }} {{ $detail->unit }}</td>
                                        <td class="px-5 py-4 text-right text-slate-600">{{ number_format($detail->unit_price, 0, ',', '.') }}đ</td>
                                        <td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($detail->amount, 0, ',', '.') }}đ</td>
                                    </tr>
                                @endforeach
                                @foreach ($invoice->adjustments as $adjustment)
                                    <tr class="{{ $adjustment->direction === \App\Models\InvoiceAdjustment::DIRECTION_CREDIT ? 'bg-emerald-50/50' : 'bg-amber-50/50' }}">
                                        <td class="px-5 py-4">
                                            <p class="font-semibold text-slate-950">{{ $adjustment->direction === \App\Models\InvoiceAdjustment::DIRECTION_CREDIT ? 'Điều chỉnh giảm' : 'Điều chỉnh tăng' }} · {{ $adjustment->adjustment_code }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $adjustment->reason }} · {{ $adjustment->creator?->name ?? 'Tài khoản đã ngừng hoạt động' }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-center text-slate-500">Phiếu điều chỉnh</td>
                                        <td class="px-5 py-4 text-right text-slate-500">—</td>
                                        <td class="px-5 py-4 text-right font-semibold {{ $adjustment->direction === \App\Models\InvoiceAdjustment::DIRECTION_CREDIT ? 'text-emerald-700' : 'text-amber-700' }}">{{ $adjustment->direction === \App\Models\InvoiceAdjustment::DIRECTION_CREDIT ? '-' : '+' }}{{ number_format($adjustment->amount, 0, ',', '.') }}đ</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50">
                                <tr>
                                    <th colspan="3" class="px-5 py-4 text-right font-semibold text-slate-700">Tổng sau điều chỉnh</th>
                                    <th class="px-5 py-4 text-right text-lg font-bold text-emerald-700">{{ number_format($invoice->payable_amount, 0, ',', '.') }}đ</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                @if($invoice->supplementalInvoices->isNotEmpty() || $invoice->creditsCreated->isNotEmpty())
                    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Phát sinh sau hóa đơn</h3></div>
                        <div class="divide-y divide-slate-100">
                            @foreach($invoice->supplementalInvoices as $supplemental)
                                <a href="{{ route('admin.invoices.show', $supplemental) }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 hover:bg-slate-50">
                                    <div><p class="font-semibold text-indigo-700">Hóa đơn bổ sung {{ $supplemental->invoice_code }}</p><p class="mt-1 text-xs text-slate-500">Phát hành {{ $supplemental->issued_at?->format('H:i d/m/Y') }}</p></div>
                                    <div class="text-right"><p class="font-semibold text-slate-950">{{ number_format($supplemental->payable_amount, 0, ',', '.') }}đ</p><p class="mt-1 text-xs text-slate-500">{{ $supplemental->status_label }}</p></div>
                                </a>
                            @endforeach
                            @foreach($invoice->creditsCreated as $credit)
                                <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-4">
                                    <div><p class="font-semibold text-emerald-700">Khoản giảm {{ $credit->credit_code }}</p><p class="mt-1 text-sm text-slate-600">{{ $credit->reason }}</p><p class="mt-1 text-xs text-slate-500">Người tạo: {{ $credit->creator?->name ?? 'Tài khoản đã ngừng hoạt động' }}</p></div>
                                    <div class="text-right"><p class="font-semibold text-emerald-700">-{{ number_format($credit->amount, 0, ',', '.') }}đ</p><p class="mt-1 text-xs text-slate-500">Còn chờ khấu trừ {{ number_format($credit->remaining_amount, 0, ',', '.') }}đ</p></div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="font-semibold text-slate-950">Lịch sử thanh toán</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Ngày thanh toán</th>
                                    <th class="px-5 py-3">Phương thức</th>
                                    <th class="px-5 py-3 text-right">Số tiền</th>
                                    <th class="px-5 py-3">Trạng thái</th>
                                    <th class="px-5 py-3">Ghi chú</th>
                                    <th class="px-5 py-3 text-right">Xử lý</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($invoice->payments as $payment)
                                    @php($paymentStatus = $paymentStatusMap[$payment->status] ?? ['text' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'])
                                    <tr>
                                        <td class="px-5 py-4 text-slate-600">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ match ($payment->payment_method) { \App\Models\Payment::METHOD_CASH => 'Tiền mặt', \App\Models\Payment::METHOD_BANK_TRANSFER => 'Chuyển khoản', \App\Models\Payment::METHOD_QR => 'Quét mã QR', default => 'Không xác định' } }}</td>
                                        <td class="px-5 py-4 text-right font-semibold text-slate-950">{{ number_format($payment->amount_paid, 0, ',', '.') }}đ</td>
                                        <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $paymentStatus['class'] }}">{{ $paymentStatus['text'] }}</span></td>
                                        <td class="px-5 py-4 text-slate-600">{{ $payment->note ?? '-' }}</td>
                                        <td class="px-5 py-4">
                                            <div class="flex min-w-52 flex-col gap-2">
                                                @if($payment->proofImageExists())
                                                    <a href="{{ route('admin.invoices.payments.proof', $payment) }}" data-image-modal data-image-title="Biên lai thanh toán {{ $invoice->invoice_code }}" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                                        <i class="bx bx-image-alt text-base"></i>
                                                        Xem biên lai
                                                    </a>
                                                @elseif($payment->proof_image)
                                                    <span class="text-xs font-semibold text-amber-700">Ảnh biên lai không còn tồn tại</span>
                                                @endif
                                                @if($payment->isPending())
                                                    <form method="POST" action="{{ route('admin.invoices.payments.approve', $payment) }}">
                                                        @csrf
                                                        <button class="h-9 w-full rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white hover:bg-emerald-700">Duyệt thanh toán</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.invoices.payments.reject', $payment) }}" class="space-y-2">
                                                        @csrf
                                                        <input name="review_note" required maxlength="1000" placeholder="Lý do từ chối..." class="h-9 w-full rounded-lg border border-slate-200 px-2 text-xs">
                                                        <button class="h-9 w-full rounded-lg border border-rose-200 bg-rose-50 px-3 text-xs font-semibold text-rose-700 hover:bg-rose-100">Từ chối</button>
                                                    </form>
                                                @elseif($payment->review_note)
                                                    <span class="text-xs text-rose-700">{{ $payment->review_note }}</span>
                                                @elseif(!$payment->proof_image)
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-10 text-center text-slate-500">Chưa có thanh toán.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <aside class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Thông tin hóa đơn</h3>

                <div class="mt-5 space-y-4 text-sm">
                    @php($servicePeriod = \Carbon\Carbon::createFromDate($invoice->year, $invoice->month, 1)->subMonthNoOverflow())
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Loại hóa đơn</span><span class="font-semibold text-slate-950">{{ $invoice->isSupplemental() ? 'Hóa đơn bổ sung' : ($invoice->isDeposit() ? 'Tiền cọc hợp đồng' : ($invoice->isFirstMonthRent() ? 'Tiền phòng tháng đầu (dữ liệu cũ)' : 'Tiền phòng và tiện ích tháng '.$servicePeriod->month.'/'.$servicePeriod->year)) }}</span></div>
                    @if($invoice->parentInvoice)<div class="flex justify-between gap-4"><span class="text-slate-500">Hóa đơn gốc</span><a class="font-semibold text-indigo-700" href="{{ route('admin.invoices.show', $invoice->parentInvoice) }}">{{ $invoice->parentInvoice->invoice_code }}</a></div>@endif
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Ngày hóa đơn theo lịch</span><span class="font-semibold text-slate-950">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Phát hành thực tế</span><span class="text-right font-semibold text-slate-950">{{ $invoice->issued_at?->format('H:i d/m/Y') ?? $invoice->created_at?->format('H:i d/m/Y') }}<span class="block text-xs font-normal text-slate-500">{{ $invoice->issuer?->name ?? 'Không có thông tin' }}</span></span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Lần phát hành</span><span class="font-semibold text-slate-950">{{ $invoice->revision }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Hạn thanh toán</span><span class="font-semibold text-slate-950">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</span></div>
                    <div class="border-t border-slate-200 pt-4"></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Phòng</span><span class="font-semibold text-slate-950">{{ $invoice->room->room_code ?? $invoice->contract->room->room_code ?? 'Không có' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Khách thuê</span><span class="font-semibold text-slate-950">{{ $invoice->contract->tenant->full_name ?? 'Không có' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Hợp đồng</span><span class="font-semibold text-slate-950">{{ $invoice->contract->contract_code ?? 'Không có' }}</span></div>
                    <div class="border-t border-slate-200 pt-4"></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Tổng gốc</span><span class="font-semibold text-slate-950">{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Điều chỉnh</span><span class="font-semibold {{ $invoice->adjustment_amount < 0 ? 'text-emerald-700' : 'text-amber-700' }}">{{ $invoice->adjustment_amount > 0 ? '+' : '' }}{{ number_format($invoice->adjustment_amount, 0, ',', '.') }}đ</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Phải thu</span><span class="font-semibold text-slate-950">{{ number_format($invoice->payable_amount, 0, ',', '.') }}đ</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Đã thu</span><span class="font-semibold text-sky-700">{{ number_format($paidAmount, 0, ',', '.') }}đ</span></div>
                    @if($invoice->status === \App\Models\Invoice::STATUS_WRITTEN_OFF)<div class="flex justify-between gap-4"><span class="text-slate-500">Đã xóa nợ theo quyết toán</span><span class="font-semibold text-violet-700">{{ number_format(max(0, $invoice->payable_amount - $paidAmount), 0, ',', '.') }}đ</span></div>@endif
                    @if($pendingAmount > 0)<div class="flex justify-between gap-4"><span class="text-slate-500">Đang chờ duyệt</span><span class="font-semibold text-amber-700">{{ number_format($pendingAmount, 0, ',', '.') }}đ</span></div>@endif
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Còn lại</span><span class="font-semibold {{ $remainingAmount > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format($remainingAmount, 0, ',', '.') }}đ</span></div>
                    @if($invoice->overpaid_amount > 0)<div class="flex justify-between gap-4"><span class="text-slate-500">Thu thừa cần đối soát</span><span class="font-semibold text-violet-700">{{ number_format($invoice->overpaid_amount, 0, ',', '.') }}đ</span></div>@endif
                </div>

                @if ($availableAmount > 0 && $invoice->canPay())
                    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.invoices.payments.store', $invoice) }}" class="mt-6 space-y-4 border-t border-slate-200 pt-5">
                        @csrf
                        <h4 class="font-semibold text-slate-950">Ghi nhận thanh toán</h4>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Số tiền</label>
                            <input type="number" name="amount_paid" min="1" max="{{ (int) $availableAmount }}" value="{{ old('amount_paid', (int) $availableAmount) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày thanh toán</label>
                            <input type="date" name="payment_date" max="{{ today()->toDateString() }}" value="{{ old('payment_date', today()->toDateString()) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Phương thức</label>
                            <select name="payment_method" required onchange="this.form.querySelector('[name=proof_image]').required=this.value==='{{ \App\Models\Payment::METHOD_BANK_TRANSFER }}'" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                                <option value="cash" @selected(old('payment_method') === 'cash')>Tiền mặt</option>
                                <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Chuyển khoản</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Ảnh minh chứng</label>
                            <input type="file" name="proof_image" accept="image/jpeg,image/png,image/webp" @required(old('payment_method') === \App\Models\Payment::METHOD_BANK_TRANSFER) class="block w-full rounded-lg border border-slate-200 bg-white text-sm file:mr-3 file:border-0 file:bg-indigo-50 file:px-4 file:py-3 file:font-semibold file:text-indigo-700">
                            <p class="mt-1 text-xs text-slate-500">Bắt buộc khi chuyển khoản · JPG, PNG, WEBP · tối đa 5 MB</p>
                        </div>
                        <textarea name="note" rows="2" placeholder="Ghi chú" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('note') }}</textarea>
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                            <i class="bx bx-check-circle text-lg"></i>
                            Xác nhận thanh toán
                        </button>
                    </form>
                @elseif($pendingAmount > 0 && $invoice->canPay())
                    <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                        Số tiền còn lại đang được giữ cho giao dịch chờ xác nhận. Hãy duyệt hoặc từ chối giao dịch trong bảng lịch sử thanh toán phía trên.
                    </div>
                @endif

                @if($invoice->invoice_type === \App\Models\Invoice::TYPE_RENTAL && !$invoice->isCancelled() && $invoice->status !== \App\Models\Invoice::STATUS_WRITTEN_OFF)
                    <form method="POST" action="{{ route('admin.invoices.supplemental.store', $invoice) }}" class="mt-6 space-y-3 border-t border-slate-200 pt-5">
                        @csrf
                        <h4 class="font-semibold text-slate-950">Tạo hóa đơn bổ sung</h4>
                        <p class="text-xs leading-5 text-slate-500">Tạo khoản phải thu độc lập, có mã và thanh toán riêng; hóa đơn gốc không thay đổi.</p>
                        <select name="category" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm">
                            <option value="utility">Truy thu điện, nước</option>
                            <option value="service">Phí dịch vụ phát sinh</option>
                            <option value="parking">Phí gửi xe phát sinh</option>
                            <option value="damage">Bồi thường hư hỏng</option>
                            <option value="other">Chi phí phát sinh khác</option>
                        </select>
                        <input type="number" name="amount" min="1" step="1" required placeholder="Số tiền cần thu thêm" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm">
                        <textarea name="description" minlength="5" maxlength="500" rows="3" required placeholder="Nội dung phát sinh" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></textarea>
                        <button class="inline-flex w-full items-center justify-center rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">Phát hành hóa đơn bổ sung</button>
                    </form>

                    <form method="POST" action="{{ route('admin.invoices.next-invoice-credits.store', $invoice) }}" class="mt-6 space-y-3 border-t border-slate-200 pt-5">
                        @csrf
                        <h4 class="font-semibold text-slate-950">Giảm vào hóa đơn tháng sau</h4>
                        <p class="text-xs leading-5 text-slate-500">Hóa đơn này được giữ nguyên. Khoản giảm sẽ tự động xuất hiện khi phát hành hóa đơn tháng kế tiếp.</p>
                        <input type="number" name="amount" min="1" step="1" required placeholder="Số tiền được giảm" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm">
                        <textarea name="reason" minlength="10" maxlength="2000" rows="3" required placeholder="Lý do giảm (ít nhất 10 ký tự)" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></textarea>
                        <button class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Ghi nhận khoản giảm kỳ sau</button>
                    </form>
                @endif

                @if($invoice->status === \App\Models\Invoice::STATUS_UNPAID && $invoice->payments->isEmpty() && $invoice->adjustments->isEmpty())
                    <form method="POST" action="{{ route('admin.invoices.cancel', $invoice) }}" class="mt-6 space-y-3 border-t border-slate-200 pt-5">
                        @csrf
                        <h4 class="font-semibold text-rose-700">Hủy hóa đơn</h4>
                        <textarea name="cancellation_reason" minlength="10" maxlength="2000" rows="3" required placeholder="Lý do hủy (ít nhất 10 ký tự)" class="w-full rounded-lg border border-rose-200 px-3 py-2 text-sm"></textarea>
                        <button class="inline-flex w-full items-center justify-center rounded-lg border border-rose-300 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Hủy và giữ lịch sử</button>
                    </form>
                @endif
            </aside>
        </div>
    </div>
@endsection
