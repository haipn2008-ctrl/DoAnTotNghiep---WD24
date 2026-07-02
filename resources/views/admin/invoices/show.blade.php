@extends('layouts.admin.index')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Chi tiết hóa đơn #{{ $invoice->id }}</h4>
                <div>
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">Quay lại</a>
                    <a href="{{ route('admin.invoices.print', $invoice->id) }}" class="btn btn-dark">In</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Thông tin hóa đơn</h5>
                    <p><strong>Phòng:</strong> {{ $invoice->contract->room->room_code ?? '-' }}</p>
                    <p><strong>Người thuê:</strong> {{ $invoice->contract->tenant->full_name ?? '-' }}</p>
                    <p><strong>Hợp đồng:</strong> {{ $invoice->contract->contract_code ?? '-' }}</p>
                    <p><strong>Kỳ thanh toán:</strong> {{ $invoice->month }}/{{ $invoice->year }}</p>
                    <p><strong>Trạng thái:</strong> {{ $invoice->status_label }}</p>

                    <hr>
                    <h5 class="card-title">Danh sách khoản thu</h5>
                    <table class="table table-bordered">
                        <tbody>
                            <tr><th>Tiền phòng</th><td>{{ number_format($invoice->room_fee, 0, ',', '.') }} VNĐ</td></tr>
                            <tr><th>Điện</th><td>{{ number_format($invoice->electricity_fee, 0, ',', '.') }} VNĐ</td></tr>
                            <tr><th>Nước</th><td>{{ number_format($invoice->water_fee, 0, ',', '.') }} VNĐ</td></tr>
                            <tr><th>Internet</th><td>{{ number_format($invoice->internet_fee, 0, ',', '.') }} VNĐ</td></tr>
                            <tr><th>Dịch vụ</th><td>{{ number_format($invoice->service_fee, 0, ',', '.') }} VNĐ</td></tr>
                        </tbody>
                    </table>

                    <hr>
                    <h5 class="card-title">Tổng kết</h5>
                    <p><strong>Tổng tiền:</strong> {{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</p>
                    <p><strong>Đã thanh toán:</strong> {{ number_format($invoice->paid_amount, 0, ',', '.') }} VNĐ</p>
                    <p><strong>Còn nợ:</strong> {{ number_format($invoice->balance_amount, 0, ',', '.') }} VNĐ</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ghi nhận thanh toán</h5>
                    <form method="POST" action="{{ route('admin.invoices.payments.store', $invoice->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Số tiền nhận</label>
                            <input type="number" name="amount_paid" class="form-control" min="0" step="1000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ngày thanh toán</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phương thức</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash">Tiền mặt</option>
                                <option value="bank_transfer">Chuyển khoản</option>
                                <option value="qr">QR</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mã giao dịch</label>
                            <input type="text" name="transaction_code" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="note" class="form-control"></textarea>
                        </div>
                        <button class="btn btn-success w-100">Lưu thanh toán</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
