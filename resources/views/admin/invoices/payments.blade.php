@extends('layouts.admin.index')

@section('content')
<div class="container-fluid">

    {{-- Page title --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Lịch Sử Thanh Toán</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Hóa Đơn</a></li>
                        <li class="breadcrumb-item active">Lịch Sử Thanh Toán</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Bộ lọc --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.invoices.payments') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Từ khoá</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Mã GD, phòng, người thuê..."
                           value="{{ request('keyword') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">-- Tất cả --</option>
                        <option value="success" @selected(request('status') === 'success')>Thành công</option>
                        <option value="pending" @selected(request('status') === 'pending')>Chờ xác nhận</option>
                        <option value="failed"  @selected(request('status') === 'failed')>Thất bại</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Phương thức</label>
                    <select name="method" class="form-select">
                        <option value="">-- Tất cả --</option>
                        <option value="cash"          @selected(request('method') === 'cash')>Tiền mặt</option>
                        <option value="bank_transfer" @selected(request('method') === 'bank_transfer')>Chuyển khoản</option>
                        <option value="qr"            @selected(request('method') === 'qr')>QR Code</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.invoices.payments') }}" class="btn btn-outline-secondary w-100">Xoá lọc</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Bảng kết quả --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Mã Giao Dịch</th>
                            <th>Phòng</th>
                            <th>Người Thuê</th>
                            <th>Số Tiền</th>
                            <th>Ngày Thanh Toán</th>
                            <th>Phương Thức</th>
                            <th>Trạng Thái</th>
                            <th>Người XN</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            @php
                                $methodLabel = match($payment->payment_method) {
                                    'cash'          => 'Tiền mặt',
                                    'bank_transfer' => 'Chuyển khoản',
                                    'qr'            => 'QR Code',
                                    default         => $payment->payment_method,
                                };
                                $statusClass = match($payment->status) {
                                    'success' => 'bg-success',
                                    'pending' => 'bg-warning',
                                    'failed'  => 'bg-danger',
                                    default   => 'bg-secondary',
                                };
                                $statusLabel = match($payment->status) {
                                    'success' => 'Thành công',
                                    'pending' => 'Chờ xác nhận',
                                    'failed'  => 'Thất bại',
                                    default   => $payment->status,
                                };
                            @endphp
                            <tr>
                                <td>{{ $payments->firstItem() + $loop->index }}</td>
                                <td>
                                    @if($payment->transaction_code)
                                        <code>{{ $payment->transaction_code }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $payment->invoice->room->room_code ?? '—' }}</td>
                                <td>{{ $payment->invoice->contract->tenant->full_name ?? '—' }}</td>
                                <td class="text-end fw-semibold">
                                    {{ number_format($payment->amount_paid, 0, ',', '.') }} đ
                                </td>
                                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                <td>{{ $methodLabel }}</td>
                                <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                <td>{{ $payment->confirmer->name ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.invoices.show', $payment->invoice_id) }}"
                                       class="btn btn-sm btn-outline-primary">Xem HĐ</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">Không có dữ liệu thanh toán.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">
                    Hiển thị {{ $payments->firstItem() }}–{{ $payments->lastItem() }}
                    trong tổng {{ $payments->total() }} giao dịch
                </small>
                {{ $payments->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
