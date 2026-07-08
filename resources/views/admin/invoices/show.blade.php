@extends('layouts.admin.index')

@section('title', 'Chi tiết hóa đơn')

@section('content')

@php
    $statusMap = [
        'unpaid' => [
            'text' => 'Chưa thanh toán',
            'class' => 'bg-warning'
        ],
        'partial' => [
            'text' => 'Thanh toán một phần',
            'class' => 'bg-info'
        ],
        'paid' => [
            'text' => 'Đã thanh toán',
            'class' => 'bg-success'
        ],
    ];

    $statusData = $statusMap[$invoice->status]
        ?? [
            'text' => $invoice->status,
            'class' => 'bg-secondary'
        ];
@endphp

<div class="container-fluid">

    <div class="row mb-4">

        <div class="col-md-6">

            <h4 class="mb-0">
                Chi tiết hóa đơn
                HD{{ str_pad($invoice->id,5,'0',STR_PAD_LEFT) }}
            </h4>

        </div>

        <div class="col-md-6 text-end">

            <a href="{{ route('admin.invoices.index') }}"
               class="btn btn-secondary">

                <i class="mdi mdi-arrow-left"></i>

                Quay lại

            </a>

            <a href="{{ route('admin.invoices.print',$invoice) }}"
               class="btn btn-dark">

                <i class="mdi mdi-printer"></i>

                In hóa đơn

            </a>

        </div>

    </div>

    <div class="row">

        <div class="col-lg-8">

            <div class="card">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <h5 class="mb-0">
                        Danh sách khoản thu
                    </h5>

                    <span class="badge {{ $statusData['class'] }}">
                        {{ $statusData['text'] }}
                    </span>

                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle mb-0">

                            <thead class="table-light">

                                <tr>

                                    <th>Khoản thu</th>

                                    <th class="text-end">
                                        Thành tiền
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach($invoice->details as $detail)

                                    <tr>

                                        <td>
                                            <strong>{{ $detail->item_name }}</strong>
                                        </td>

                                        <td class="text-end fw-bold">
                                            {{ number_format($detail->amount, 0, ',', '.') }}
                                            VNĐ
                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                            <tfoot>

                                <tr>

                                    <th class="text-end">

                                        Tổng cộng

                                    </th>

                                    <th class="text-end text-success">

                                        {{ number_format($invoice->total_amount,0,',','.') }}

                                        VNĐ

                                    </th>

                                </tr>

                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>
                        <div class="card">

                <div class="card-header">

                    <h5 class="mb-0">

                        Lịch sử thanh toán

                    </h5>

                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover table-bordered align-middle mb-0">

                            <thead class="table-light">

                                <tr>

                                    <th>Ngày</th>

                                    <th>Phương thức</th>

                                    <th>Mã giao dịch</th>

                                    <th class="text-end">

                                        Số tiền

                                    </th>

                                    <th class="text-center">

                                        Trạng thái

                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                @forelse($invoice->payments as $payment)

                                    <tr>

                                        <td>

                                            {{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}

                                        </td>

                                        <td>

                                            @switch($payment->payment_method)

                                                @case('cash')

                                                    Tiền mặt

                                                    @break

                                                @case('bank_transfer')

                                                    Chuyển khoản

                                                    @break

                                                @case('qr')

                                                    QR Banking

                                                    @break

                                                @default

                                                    {{ $payment->payment_method }}

                                            @endswitch

                                        </td>

                                        <td>

                                            {{ $payment->transaction_code ?: '-' }}

                                        </td>

                                        <td class="text-end fw-bold text-success">

                                            {{ number_format($payment->amount_paid,0,',','.') }}

                                            VNĐ

                                        </td>

                                        <td class="text-center">

                                            @if($payment->status=='success')

                                                <span class="badge bg-success">

                                                    Thành công

                                                </span>

                                            @elseif($payment->status=='pending')

                                                <span class="badge bg-warning">

                                                    Chờ xác nhận

                                                </span>

                                            @else

                                                <span class="badge bg-danger">

                                                    Thất bại

                                                </span>

                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="5"

                                            class="text-center text-muted py-4">

                                            Chưa có lịch sử thanh toán

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-4" id="payment-form">

            <div class="card">

                <div class="card-header">

                    <h5 class="mb-0">

                        Ghi nhận thanh toán

                    </h5>

                </div>

                <div class="card-body">

                    @if($remainingAmount>0)

                    <form method="POST"

                          action="{{ route('admin.invoices.payments.store',$invoice) }}">

                        @csrf

                        <div class="mb-3">

                            <label class="form-label">

                                Số tiền thanh toán

                            </label>

                            <input

                                type="number"

                                class="form-control"

                                name="amount_paid"

                                min="1000"

                                max="{{ $remainingAmount }}"

                                value="{{ $remainingAmount }}"

                                required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">

                                Ngày thanh toán

                            </label>

                            <input

                                type="date"

                                class="form-control"

                                name="payment_date"

                                value="{{ now()->format('Y-m-d') }}"

                                required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">

                                Phương thức

                            </label>

                            <select

                                class="form-select"

                                name="payment_method">

                                <option value="cash">

                                    Tiền mặt

                                </option>

                                <option value="bank_transfer">

                                    Chuyển khoản

                                </option>

                                <option value="qr">

                                    QR Banking

                                </option>

                            </select>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">

                                Mã giao dịch

                            </label>

                            <input

                                type="text"

                                class="form-control"

                                name="transaction_code">

                        </div>

                        <div class="mb-3">

                            <label class="form-label">

                                Ghi chú

                            </label>

                            <textarea

                                class="form-control"

                                rows="3"

                                name="note"></textarea>

                        </div>

                        <button

                            class="btn btn-success w-100">

                            <i class="mdi mdi-cash-check me-1"></i>

                            Xác nhận thanh toán

                        </button>

                    </form>

                    @else

                        <div class="alert alert-success mb-0">

                            Hóa đơn đã được thanh toán đầy đủ.

                        </div>

                    @endif

                </div>

            </div>
                        <div class="card">

                <div class="card-header">

                    <h5 class="mb-0">

                        Thông tin hóa đơn

                    </h5>

                </div>

                <div class="card-body">

                    <table class="table table-borderless mb-0">

                        <tr>

                            <th width="45%">

                                Mã hóa đơn

                            </th>

                            <td>

                                HD{{ str_pad($invoice->id,5,'0',STR_PAD_LEFT) }}

                            </td>

                        </tr>

                        <tr>

                            <th>

                                Phòng

                            </th>

                            <td>

                                {{ $invoice->room->room_code
                                    ?? $invoice->contract->room->room_code
                                    ?? '-' }}

                            </td>

                        </tr>

                        <tr>

                            <th>

                                Khách thuê

                            </th>

                            <td>

                                {{ $invoice->contract->tenant->full_name
                                    ?? '-' }}

                            </td>

                        </tr>

                        <tr>

                            <th>

                                Hợp đồng

                            </th>

                            <td>

                                {{ $invoice->contract->contract_code
                                    ?? '-' }}

                            </td>

                        </tr>

                        <tr>

                            <th>

                                Kỳ hóa đơn

                            </th>

                            <td>

                                {{ $invoice->month }}/{{ $invoice->year }}

                            </td>

                        </tr>

                        <tr>

                            <th>

                                Ngày lập

                            </th>

                            <td>

                                {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}

                            </td>

                        </tr>

                        <tr>

                            <th>

                                Hạn thanh toán

                            </th>

                            <td>

                                {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}

                            </td>

                        </tr>

                    </table>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">

                        <span>

                            Tổng tiền

                        </span>

                        <strong>

                            {{ number_format($invoice->total_amount,0,',','.') }}

                            VNĐ

                        </strong>

                    </div>

                    <div class="d-flex justify-content-between mb-2">

                        <span>

                            Đã thanh toán

                        </span>

                        <strong class="text-success">

                            {{ number_format($paidAmount,0,',','.') }}

                            VNĐ

                        </strong>

                    </div>

                    <div class="d-flex justify-content-between">

                        <span>

                            Còn phải thu

                        </span>

                        <strong class="text-danger fs-5">

                            {{ number_format($remainingAmount,0,',','.') }}

                            VNĐ

                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
