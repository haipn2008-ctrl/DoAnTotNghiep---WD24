@extends('layouts.admin.index')

@section('title', 'Quy trình kết thúc hợp đồng | Quản lý phòng trọ')
@section('page_title', 'Quy trình kết thúc hợp đồng')

@php
    $beforeCheckout = in_array($contract->status, \App\Models\Contract::OPEN_OCCUPANCY_STATUSES, true);
    $completed = $contract->status === \App\Models\Contract::STATUS_COMPLETED;
    $departureScheduled = ! $beforeCheckout || ($contract->approvedTerminationRequest && $contract->scheduled_move_out_at);
    $debtResolved = ! $beforeCheckout && $openInvoices->isEmpty();
    $depositNotRequired = (float) $contract->deposit_amount <= 0
        || $contract->deposit_resolution === \App\Models\Contract::DEPOSIT_NOT_REQUIRED;
    $depositResolved = $depositNotRequired || in_array($contract->deposit_resolution, [
        \App\Models\Contract::DEPOSIT_REFUNDED,
        \App\Models\Contract::DEPOSIT_DEDUCTED,
        \App\Models\Contract::DEPOSIT_RETAINED,
    ], true);
    $handoverDone = ! $beforeCheckout;
    $financialsDone = $handoverDone && $debtResolved && $depositResolved;
    $step3Active = $handoverDone && $debtResolved && ! $depositResolved;
    $step4Active = ! $completed && $financialsDone;
    $refundRequested = $contract->deposit_status === \App\Models\Contract::DEPOSIT_REFUND_REQUESTED;
    $refundAwaitingReceipt = $contract->isAwaitingRefundReceiptConfirmation();
    $steps = [
        ['number' => 1, 'title' => 'Lý do & lịch bàn giao', 'description' => 'Xác nhận hình thức kết thúc và thời điểm trả phòng', 'done' => $departureScheduled, 'active' => $beforeCheckout && ! $departureScheduled],
        ['number' => 2, 'title' => 'Bàn giao phòng', 'description' => 'Chốt điện nước, tài sản và hiện trạng phòng', 'done' => $handoverDone, 'active' => $beforeCheckout && $departureScheduled],
        ['number' => 3, 'title' => 'Quyết toán & tiền cọc', 'description' => 'Thanh toán công nợ, hoàn hoặc khấu trừ tiền cọc', 'done' => $financialsDone || $completed, 'active' => $handoverDone && ! $financialsDone],
        ['number' => 4, 'title' => 'Hoàn tất hợp đồng', 'description' => 'Xác nhận hai bên đã hết nghĩa vụ', 'done' => $completed, 'active' => $step4Active],
    ];
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-violet-700">{{ $contract->contract_code }} · Phòng {{ $contract->room?->room_code }}</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Quy trình kết thúc hợp đồng</h2>
            <p class="mt-1 text-sm text-slate-500">Toàn bộ quá trình được xử lý trên trang này; hoàn thành bước hiện tại để mở bước tiếp theo.</p>
        </div>
        <a href="{{ route('admin.contracts.show', $contract) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"><i class="bx bx-arrow-back text-lg"></i>Chi tiết hợp đồng</a>
    </div>

    @include('admin.contracts.partials.departure-progress', ['departureSteps' => $steps])

    @if($beforeCheckout && ! $departureScheduled)
        <section class="overflow-hidden rounded-2xl border border-sky-200 bg-white shadow-sm">
            <div class="border-b border-sky-100 bg-sky-50/70 px-5 py-5 sm:px-6"><p class="text-xs font-bold uppercase tracking-wider text-sky-700">Bước 1/4</p><h3 class="mt-1 text-lg font-bold text-slate-950">Ghi nhận lý do và xếp lịch bàn giao</h3><p class="mt-1 text-sm text-slate-600">Nếu khách đã gửi yêu cầu trả phòng, thao tác này đồng thời duyệt yêu cầu đó. Nếu admin chủ động kết thúc, hệ thống sẽ tạo hồ sơ lịch bàn giao để lưu vết đầy đủ.</p></div>
            <form class="lifecycle-form space-y-4 p-5 sm:p-6" method="POST" action="{{ route('admin.contracts.departure-schedule', $contract) }}">
                @csrf
                <label class="block text-xs font-semibold text-slate-700">Ngày kết thúc và bàn giao được thống nhất<input type="date" name="approved_end_date" min="{{ today()->toDateString() }}" max="{{ $contract->status === \App\Models\Contract::STATUS_ACTIVE ? $contract->end_date?->toDateString() : '' }}" value="{{ old('approved_end_date', today()->toDateString()) }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal"><span class="mt-1.5 block font-normal text-slate-500">Khách có thể bàn giao bất kỳ lúc nào trong giờ hành chính 08:00–17:00 của ngày này.</span></label>
                <label class="block text-xs font-semibold text-slate-700">Lý do kết thúc<textarea name="departure_reason" rows="3" required maxlength="1000" placeholder="Ví dụ: Hai bên thống nhất kết thúc đúng hạn" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-normal">{{ old('departure_reason') }}</textarea></label>
                <label class="block text-xs font-semibold text-slate-700">Ghi chú chuẩn bị bàn giao<textarea name="admin_note" rows="2" maxlength="1000" placeholder="Ví dụ: Có mặt trước 15 phút để kiểm kê tài sản" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-normal">{{ old('admin_note') }}</textarea></label>
                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end"><a href="{{ route('admin.contracts.show', $contract) }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700">Hủy thao tác</a><button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-sky-700 px-5 text-sm font-bold text-white shadow-sm hover:bg-sky-800">Xác nhận và xếp lịch<i class="bx bx-calendar-check text-xl"></i></button></div>
            </form>
        </section>
    @elseif($beforeCheckout)
        <section class="overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-sm">
            <div class="border-b border-violet-100 bg-violet-50/70 px-5 py-5 sm:px-6"><p class="text-xs font-bold uppercase tracking-wider text-violet-600">Bước 2/4</p><h3 class="mt-1 text-lg font-bold text-slate-950">Bàn giao phòng và chốt quyết toán</h3><p class="mt-1 text-sm text-slate-600">Sau khi xác nhận, hệ thống chốt chỉ số, giải phóng phòng, lập hóa đơn cuối kỳ và tự động bù tiền cọc vào công nợ.</p></div>
            <form class="lifecycle-form" method="POST" action="{{ route('admin.contracts.check-out', $contract) }}" enctype="multipart/form-data">
                @csrf
                @include('admin.contracts.partials.check-out-fields')
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6"><a href="{{ route('admin.contracts.show', $contract) }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700">Hủy thao tác</a><button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-violet-700 px-5 text-sm font-bold text-white shadow-sm hover:bg-violet-800">Xác nhận bàn giao và chốt quyết toán<i class="bx bx-right-arrow-alt text-xl"></i></button></div>
            </form>
        </section>
    @elseif(!$debtResolved)
        <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5 shadow-sm sm:p-6">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Bước 3/4 · Còn công nợ</p>
            <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div><h3 class="text-lg font-bold text-slate-950">Khách còn phải thanh toán {{ number_format((float) $totalOutstanding, 0, ',', '.') }}đ</h3><p class="mt-1 text-sm text-slate-600">Còn {{ $openInvoices->count() }} hóa đơn chưa hoàn tất. Xác nhận thanh toán xong rồi quay lại trang này; bước tiền cọc sẽ tự mở.</p>@if((float) $contract->deposit_deduction_amount > 0)<p class="mt-2 text-sm font-semibold text-indigo-700">Hệ thống đã tự bù {{ number_format((float) $contract->deposit_deduction_amount, 0, ',', '.') }}đ tiền cọc vào công nợ.</p>@endif</div>
                <a href="{{ route('admin.invoices.index', ['keyword' => $contract->contract_code]) }}" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-amber-600 px-5 text-sm font-bold text-white hover:bg-amber-700"><i class="bx bx-receipt text-xl"></i>Xử lý hóa đơn</a>
            </div>
        </section>
    @endif

    @if(!$beforeCheckout && $contract->settlementStatement)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="font-bold text-slate-950">Kết quả quyết toán</h3><p class="mt-1 text-xs text-slate-500">Đã tính lúc {{ $contract->settlementStatement->calculated_at?->format('H:i d/m/Y') }}</p></div>@if($contract->settlementStatement->invoice)<a href="{{ route('admin.invoices.show', $contract->settlementStatement->invoice) }}" class="text-sm font-bold text-indigo-700">Xem hóa đơn cuối kỳ</a>@endif</div>
            <div class="grid gap-3 bg-slate-50 p-5 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="text-xs text-slate-500">Phí và công nợ</p><p class="mt-1 font-bold text-slate-950">{{ number_format((float) $contract->settlementStatement->final_charge_amount + (float) $contract->settlementStatement->previous_outstanding_amount, 0, ',', '.') }}đ</p></div>
                <div><p class="text-xs text-slate-500">Cọc đã bù công nợ</p><p class="mt-1 font-bold text-indigo-700">{{ number_format((float) $contract->deposit_deduction_amount, 0, ',', '.') }}đ</p></div>
                <div><p class="text-xs text-slate-500">Cọc còn hoàn khách</p><p class="mt-1 font-bold text-emerald-700">{{ number_format((float) $contract->deposit_refund_amount, 0, ',', '.') }}đ</p></div>
                <div><p class="text-xs text-slate-500">Công nợ còn lại</p><p class="mt-1 font-bold {{ $totalOutstanding > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format((float) $totalOutstanding, 0, ',', '.') }}đ</p></div>
            </div>
            @php($netAmount = (float) $contract->settlementStatement->net_amount)
            <div class="border-t border-slate-200 px-5 py-4 {{ $netAmount > 0 ? 'bg-rose-50' : ($netAmount < 0 ? 'bg-emerald-50' : 'bg-slate-50') }}">
                @if($netAmount > 0)
                    <p class="font-bold text-rose-800">Người thuê còn phải trả ban quản lý {{ number_format($netAmount, 0, ',', '.') }}đ</p>
                    <p class="mt-1 text-xs text-rose-700">Tiền cọc không đủ bù toàn bộ phí cuối kỳ, công nợ và tiền bồi thường.</p>
                @elseif($netAmount < 0)
                    <p class="font-bold text-emerald-800">Ban quản lý cần hoàn người thuê {{ number_format(abs($netAmount), 0, ',', '.') }}đ</p>
                    <p class="mt-1 text-xs text-emerald-700">Đây là phần tiền cọc còn lại sau khi đã đối trừ toàn bộ nghĩa vụ.</p>
                @else
                    <p class="font-bold text-slate-800">Hai bên đã cân bằng nghĩa vụ — không còn khoản phải trả.</p>
                @endif
            </div>
            @if($contract->settlementStatement->items->isNotEmpty())
                <details class="border-t border-slate-200 px-5 py-4">
                    <summary class="cursor-pointer text-sm font-bold text-indigo-700">Xem chi tiết các khoản quyết toán</summary>
                    <div class="mt-3 divide-y divide-slate-100 rounded-lg border border-slate-200">
                        @foreach($contract->settlementStatement->items as $item)
                            <div class="flex items-start justify-between gap-4 px-4 py-3 text-sm"><span><strong class="text-slate-900">{{ $item->name }}</strong><span class="mt-0.5 block text-xs text-slate-500">{{ $item->note }}</span></span><strong class="shrink-0 text-slate-900">{{ number_format((float) $item->amount, 0, ',', '.') }}đ</strong></div>
                        @endforeach
                    </div>
                </details>
            @endif
            <div class="border-t border-slate-200 px-5 py-4">
                <p class="text-sm font-bold text-slate-900">Biên bản hư hỏng và ảnh bàn giao</p>
                @if($contract->checkout_has_damage || filled($contract->checkout_damage_note))
                    <p class="mt-2 text-sm text-rose-700"><strong>Có hư hỏng/thất lạc:</strong> {{ $contract->checkout_damage_note }}</p>
                @else
                    <p class="mt-2 text-sm font-semibold text-emerald-700">Không ghi nhận hư hỏng hoặc thất lạc.</p>
                @endif
                @if(filled($contract->checkout_photo_paths))
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($contract->checkout_photo_paths as $index => $path)
                            <a href="{{ route('admin.contracts.checkout-photos.show', [$contract, $index]) }}" data-image-modal data-image-title="Ảnh bàn giao trả phòng {{ $index + 1 }}" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-700">Xem ảnh {{ $index + 1 }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if($step3Active)
        <section class="rounded-2xl border border-violet-300 bg-violet-50 p-5 shadow-sm ring-2 ring-violet-100 sm:p-6">
            <p class="text-xs font-bold uppercase tracking-wider text-violet-700">Bước 3/4</p><h3 class="mt-1 text-lg font-bold text-slate-950">Xử lý tiền cọc còn lại</h3>
            @if($refundAwaitingReceipt)
                <p class="mt-2 text-sm text-slate-600">Admin đã chuyển <strong>{{ number_format((float) $contract->deposit_transfer_amount, 0, ',', '.') }}đ</strong>. Đang chờ khách xác nhận nhận đủ tiền trước <strong>{{ $contract->deposit_receipt_confirmation_due_at?->format('H:i d/m/Y') }}</strong>; quá hạn hệ thống sẽ tự động xác nhận.</p><span class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-violet-800 ring-1 ring-violet-200"><i class="bx bx-time-five text-xl"></i>Chờ khách xác nhận nhận tiền</span>
            @elseif($refundRequested)
                <p class="mt-2 text-sm text-slate-600">Khách đã gửi thông tin tài khoản. Admin kiểm tra số tiền theo quyết toán, chuyển khoản và tải chứng từ trong một lần xác nhận.</p><a href="{{ route('admin.deposit-refunds.index') }}" class="mt-4 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-violet-700 px-5 text-sm font-bold text-white hover:bg-violet-800"><i class="bx bx-wallet text-xl"></i>Chuyển khoản hoàn cọc</a>
            @else
                <p class="mt-2 text-sm text-slate-600">Còn <strong>{{ number_format((float) $contract->deposit_refund_amount, 0, ',', '.') }}đ</strong> cần hoàn. Yêu cầu người thuê đại diện đăng nhập cổng khách thuê và gửi thông tin tài khoản nhận tiền.</p><span class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-violet-800 ring-1 ring-violet-200"><i class="bx bx-time-five text-xl"></i>Đang chờ khách gửi tài khoản</span>
            @endif
        </section>
    @endif

    @if($step4Active)
        <section class="rounded-2xl border border-emerald-300 bg-emerald-50 p-5 shadow-sm ring-2 ring-emerald-100 sm:p-6">
            <form class="lifecycle-form max-w-3xl" method="POST" action="{{ route('admin.contracts.complete-settlement', $contract) }}">
                @csrf
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Bước 4/4</p><h3 class="mt-1 text-lg font-bold text-slate-950">Xác nhận kết thúc nghĩa vụ hai bên</h3><p class="mt-1 text-sm text-slate-600">Công nợ và tiền cọc đã xử lý xong. Sau thao tác này hợp đồng không thể quay lại trạng thái trước.</p>
                <textarea name="settlement_note" rows="2" placeholder="Ghi chú hoàn tất (không bắt buộc)" class="mt-3 w-full rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm"></textarea>
                <label class="mt-3 flex items-start gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="confirm_complete" value="1" required class="mt-0.5 rounded border-slate-300 text-emerald-600"><span>Tôi đã kiểm tra bảng quyết toán, hóa đơn và chứng từ xử lý tiền cọc.</span></label>
                <button type="submit" class="mt-4 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-5 text-sm font-bold text-white hover:bg-emerald-800"><i class="bx bx-check-circle text-xl"></i>Hoàn tất hợp đồng</button>
            </form>
        </section>
    @endif

    @if($completed)
        <section class="rounded-2xl border border-emerald-300 bg-emerald-50 p-6 text-center shadow-sm"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-600 text-2xl text-white"><i class="bx bx-check"></i></span><h3 class="mt-3 text-lg font-bold text-emerald-950">Hợp đồng đã hoàn tất</h3><p class="mt-1 text-sm text-emerald-800">Công nợ và tiền cọc đã được xử lý; hệ thống đã ghi nhận kết thúc nghĩa vụ của hai bên.</p></section>
    @endif
</div>
@push('scripts')
<script>
document.querySelectorAll('.lifecycle-form').forEach(form => form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"]');
    if (button) {
        button.disabled = true;
        button.textContent = 'Đang xử lý…';
    }
}));

document.querySelectorAll('[data-checkout-fields]').forEach(section => {
    const choices = Array.from(section.querySelectorAll('[data-damage-choice]'));
    const damageFields = section.querySelector('[data-damage-fields]');
    const damageRequired = Array.from(section.querySelectorAll('[data-damage-required]'));
    const photos = section.querySelector('[data-checkout-photos]');
    const compensation = section.querySelector('[data-compensation-amount]');
    const assetConditions = Array.from(section.querySelectorAll('[data-asset-condition]'));
    const assetWarning = section.querySelector('[data-asset-damage-warning]');
    const actualMoveOut = section.querySelector('[data-actual-move-out]');
    const approvedSchedule = section.querySelector('[data-approved-departure-date]');
    const varianceField = section.querySelector('[data-schedule-variance-field]');
    const varianceReason = varianceField?.querySelector('[name="schedule_variance_reason"]');
    const deposit = Number(section.dataset.depositAmount || 0);
    const outstanding = Number(section.dataset.outstandingAmount || 0);
    const money = value => new Intl.NumberFormat('vi-VN').format(Math.max(0, Math.round(value))) + 'đ';

    const hasDamagedAsset = () => assetConditions.some(input => ['damaged', 'missing'].includes(input.value));
    const selectedDamage = () => section.querySelector('[data-damage-choice]:checked')?.value === '1';

    const renderPreview = () => {
        const damageAmount = selectedDamage() ? Number(compensation?.value || 0) : 0;
        const obligation = outstanding + Math.max(0, damageAmount);
        const balance = deposit - obligation;
        section.querySelector('[data-preview-deposit]').textContent = money(deposit);
        section.querySelector('[data-preview-obligation]').textContent = money(obligation);
        const result = section.querySelector('[data-preview-result]');
        const party = section.querySelector('[data-preview-party]');
        result.textContent = money(Math.abs(balance));
        result.className = 'mt-1 text-lg font-bold ' + (balance > 0 ? 'text-emerald-700' : (balance < 0 ? 'text-rose-700' : 'text-slate-700'));
        party.textContent = balance > 0
            ? 'Ban quản lý tạm hoàn người thuê'
            : (balance < 0 ? 'Người thuê tạm trả thêm' : 'Hai bên tạm cân bằng');
        party.className = 'mt-1 text-xs font-semibold ' + (balance > 0 ? 'text-emerald-700' : (balance < 0 ? 'text-rose-700' : 'text-slate-600'));
    };

    const sync = () => {
        if (hasDamagedAsset()) {
            const yes = choices.find(input => input.value === '1');
            if (yes) yes.checked = true;
            assetWarning?.classList.remove('hidden');
        } else {
            assetWarning?.classList.add('hidden');
        }
        const damaged = selectedDamage();
        damageFields?.classList.toggle('hidden', !damaged);
        damageRequired.forEach(input => {
            input.required = damaged;
            input.disabled = !damaged;
        });
        if (photos) photos.required = damaged;
        const actualDate = actualMoveOut?.value?.slice(0, 10);
        const dateChanged = Boolean(actualDate && approvedSchedule?.dataset.approvedDepartureDate
            && actualDate !== approvedSchedule.dataset.approvedDepartureDate);
        varianceField?.classList.toggle('hidden', !dateChanged);
        if (varianceReason) varianceReason.required = dateChanged;
        renderPreview();
    };

    choices.forEach(input => input.addEventListener('change', sync));
    assetConditions.forEach(input => input.addEventListener('change', sync));
    compensation?.addEventListener('input', renderPreview);
    actualMoveOut?.addEventListener('change', sync);
    sync();
});
</script>
@endpush
@endsection
