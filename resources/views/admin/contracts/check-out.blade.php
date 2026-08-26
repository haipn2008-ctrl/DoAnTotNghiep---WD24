@extends('layouts.admin.index')

@section('title', 'Quy trình trả phòng | Quản lý phòng trọ')
@section('page_title', 'Quy trình trả phòng')

@php
    $beforeCheckout = in_array($contract->status, \App\Models\Contract::OPEN_OCCUPANCY_STATUSES, true);
    $completed = $contract->status === \App\Models\Contract::STATUS_COMPLETED;
    $debtResolved = ! $beforeCheckout && $openInvoices->isEmpty();
    $depositNotRequired = (float) $contract->deposit_amount <= 0
        || $contract->deposit_resolution === \App\Models\Contract::DEPOSIT_NOT_REQUIRED;
    $depositResolved = $depositNotRequired || in_array($contract->deposit_resolution, [
        \App\Models\Contract::DEPOSIT_REFUNDED,
        \App\Models\Contract::DEPOSIT_DEDUCTED,
        \App\Models\Contract::DEPOSIT_RETAINED,
    ], true);
    $step1Done = ! $beforeCheckout && $debtResolved;
    $step2Done = ! $beforeCheckout && $depositResolved;
    $step2Active = $step1Done && ! $step2Done;
    $step3Active = ! $completed && $step1Done && $step2Done;
    $refundRequested = $contract->deposit_status === \App\Models\Contract::DEPOSIT_REFUND_REQUESTED;
    $refundApproved = in_array($contract->deposit_status, [
        \App\Models\Contract::DEPOSIT_REFUND_APPROVED,
        \App\Models\Contract::DEPOSIT_REFUND_PROCESSING,
    ], true);
    $steps = [
        ['number' => 1, 'title' => 'Bàn giao & chốt quyết toán', 'description' => 'Trả phòng, lập hóa đơn và xử lý công nợ', 'done' => $step1Done || $completed, 'active' => ! $step1Done && ! $completed],
        ['number' => 2, 'title' => 'Xử lý tiền cọc', 'description' => 'Hoàn hoặc khấu trừ tiền cọc', 'done' => $step2Done || $completed, 'active' => $step2Active],
        ['number' => 3, 'title' => 'Hoàn tất hợp đồng', 'description' => 'Xác nhận hai bên hết nghĩa vụ', 'done' => $completed, 'active' => $step3Active],
    ];
@endphp

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-violet-700">{{ $contract->contract_code }} · Phòng {{ $contract->room?->room_code }}</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Kết thúc hợp đồng theo 3 bước</h2>
            <p class="mt-1 text-sm text-slate-500">Toàn bộ quá trình được xử lý trên trang này; hoàn thành bước hiện tại để mở bước tiếp theo.</p>
        </div>
        <a href="{{ route('admin.contracts.show', $contract) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"><i class="bx bx-arrow-back text-lg"></i>Chi tiết hợp đồng</a>
    </div>

    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><p class="font-bold">Chưa thể hoàn thành thao tác:</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @include('admin.contracts.partials.departure-progress', ['departureSteps' => $steps])

    @if($beforeCheckout)
        <section class="overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-sm">
            <div class="border-b border-violet-100 bg-violet-50/70 px-5 py-5 sm:px-6"><p class="text-xs font-bold uppercase tracking-wider text-violet-600">Bước 1/3</p><h3 class="mt-1 text-lg font-bold text-slate-950">Bàn giao phòng và chốt quyết toán</h3><p class="mt-1 text-sm text-slate-600">Sau khi xác nhận, hệ thống chốt chỉ số, giải phóng phòng, lập hóa đơn cuối kỳ và tự động bù tiền cọc vào công nợ.</p></div>
            <form class="lifecycle-form" method="POST" action="{{ route('admin.contracts.check-out', $contract) }}" enctype="multipart/form-data">
                @csrf
                @include('admin.contracts.partials.check-out-fields')
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6"><a href="{{ route('admin.contracts.show', $contract) }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700">Hủy thao tác</a><button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-violet-700 px-5 text-sm font-bold text-white shadow-sm hover:bg-violet-800">Xác nhận bàn giao và chốt quyết toán<i class="bx bx-right-arrow-alt text-xl"></i></button></div>
            </form>
        </section>
    @elseif(!$debtResolved)
        <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5 shadow-sm sm:p-6">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Bước 1/3 · Còn công nợ</p>
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
        </section>
    @endif

    @if($step2Active)
        <section class="rounded-2xl border border-violet-300 bg-violet-50 p-5 shadow-sm ring-2 ring-violet-100 sm:p-6">
            <p class="text-xs font-bold uppercase tracking-wider text-violet-700">Bước 2/3</p><h3 class="mt-1 text-lg font-bold text-slate-950">Xử lý tiền cọc còn lại</h3>
            @if($refundApproved)
                <p class="mt-2 text-sm text-slate-600">Đã duyệt hoàn <strong>{{ number_format((float) $contract->deposit_refund_amount, 0, ',', '.') }}đ</strong>. Admin cần chuyển khoản đúng số tiền và tải chứng từ.</p><a href="{{ route('admin.deposit-refunds.index') }}" class="mt-4 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-violet-700 px-5 text-sm font-bold text-white hover:bg-violet-800"><i class="bx bx-transfer text-xl"></i>Xác nhận chuyển tiền</a>
            @elseif($refundRequested)
                <p class="mt-2 text-sm text-slate-600">Khách đã gửi thông tin tài khoản. Admin cần kiểm tra và duyệt số tiền hoàn hoặc khấu trừ.</p><a href="{{ route('admin.deposit-refunds.index') }}" class="mt-4 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-violet-700 px-5 text-sm font-bold text-white hover:bg-violet-800"><i class="bx bx-wallet text-xl"></i>Duyệt yêu cầu hoàn cọc</a>
            @else
                <p class="mt-2 text-sm text-slate-600">Còn <strong>{{ number_format((float) $contract->deposit_refund_amount, 0, ',', '.') }}đ</strong> cần hoàn. Yêu cầu người thuê đại diện đăng nhập cổng khách thuê và gửi thông tin tài khoản nhận tiền.</p><span class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-violet-800 ring-1 ring-violet-200"><i class="bx bx-time-five text-xl"></i>Đang chờ khách gửi tài khoản</span>
            @endif
        </section>
    @endif

    @if($step3Active)
        <section class="rounded-2xl border border-emerald-300 bg-emerald-50 p-5 shadow-sm ring-2 ring-emerald-100 sm:p-6">
            <form class="lifecycle-form max-w-3xl" method="POST" action="{{ route('admin.contracts.complete-settlement', $contract) }}">
                @csrf
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Bước 3/3</p><h3 class="mt-1 text-lg font-bold text-slate-950">Xác nhận kết thúc nghĩa vụ hai bên</h3><p class="mt-1 text-sm text-slate-600">Công nợ và tiền cọc đã xử lý xong. Sau thao tác này hợp đồng không thể quay lại trạng thái trước.</p>
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
@push('scripts')<script>document.querySelectorAll('.lifecycle-form').forEach(form=>form.addEventListener('submit',()=>{const button=form.querySelector('button[type="submit"]');if(button){button.disabled=true;button.textContent='Đang xử lý…';}}));</script>@endpush
@endsection
