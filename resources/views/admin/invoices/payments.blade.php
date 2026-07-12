@extends('layouts.admin.index')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Ghi nhận thanh toán</h4>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Mã giao dịch</th>
                            <th>Hóa đơn</th>
                            <th>Phòng</th>
                            <th>Người thuê</th>
                            <th>Số tiền</th>
                            <th>Phương thức</th>
                            <th>Ngày thanh toán</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->transaction_code ?? '-' }}</td>
                                <td>{{ $payment->invoice->invoice_code ?? '-' }}</td>
                                <td>{{ $payment->invoice->room->room_code ?? '-' }}</td>
                                <td>{{ $payment->invoice->contract->tenant->full_name ?? '-' }}</td>
                                <td>{{ number_format($payment->amount_paid, 0, ',', '.') }} VNĐ</td>
                                <td>{{ $payment->payment_method }}</td>
                                <td>{{ $payment->payment_date?->format('d/m/Y') ?? '-' }}</td>
                                <td><span class="badge bg-success">{{ $payment->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center">Không có thanh toán nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $payments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
