@extends('layouts.admin.index')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">HÓA ĐƠN THUÊ PHÒNG</h4>
            <p><strong>Mã hóa đơn:</strong> #{{ $invoice->id }}</p>
            <p><strong>Phòng:</strong> {{ $invoice->contract->room->room_code ?? '-' }}</p>
            <p><strong>Người thuê:</strong> {{ $invoice->contract->tenant->full_name ?? '-' }}</p>
            <p><strong>Kỳ:</strong> {{ $invoice->month }}/{{ $invoice->year }}</p>
            <table class="table table-bordered mt-3">
                <tbody>
                    <tr><th>Tiền phòng</th><td>{{ number_format($invoice->room_fee, 0, ',', '.') }} VNĐ</td></tr>
                    <tr><th>Điện</th><td>{{ number_format($invoice->electricity_fee, 0, ',', '.') }} VNĐ</td></tr>
                    <tr><th>Nước</th><td>{{ number_format($invoice->water_fee, 0, ',', '.') }} VNĐ</td></tr>
                    <tr><th>Internet</th><td>{{ number_format($invoice->internet_fee, 0, ',', '.') }} VNĐ</td></tr>
                    <tr><th>Dịch vụ</th><td>{{ number_format($invoice->service_fee, 0, ',', '.') }} VNĐ</td></tr>
                    <tr><th>Tổng tiền</th><td>{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
