@extends('layouts.admin.home')

@section('title', 'Xuất danh sách thanh toán')

@section('content')
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Xuất danh sách thanh toán</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.invoices.payments') }}">Thanh toán</a></li>
                            <li class="breadcrumb-item active">Xuất danh sách thanh toán</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body bg-light">
                <div class="row">
                    <div class="col-md-8 align-self-center">
                        <p class="mb-0">
                            Trang xuất danh sách thanh toán chỉ dùng để tải file CSV toàn bộ thanh toán theo bộ lọc hiện tại.
                        </p>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.invoices.payments.export.download', request()->only(['status','method','keyword'])) }}" class="btn btn-success px-4 py-2">
                            <i class="fas fa-file-csv me-1"></i> Xuất file CSV
                        </a>
                        <a href="{{ route('admin.invoices.payments') }}" class="btn btn-outline-secondary px-4 py-2">
                            Quay lại ghi nhận thanh toán
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="text-white" style="background-color:#4e73df;">
                            <tr>
                                <th>STT</th>
                                <th>Mã giao dịch</th>
                                <th>Hóa đơn</th>
                                <th>Phòng</th>
                                <th>Người thuê</th>
                                <th class="text-end">Số tiền</th>
                                <th>Phương thức</th>
                                <th>Ngày thanh toán</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                @php
                                    $methodLabel = match ($payment->payment_method) {
                                        'bank_transfer' => 'Chuyển khoản',
                                        'qr' => 'QR',
                                        default => 'Tiền mặt',
                                    };

                                    $statusLabel = match ($payment->status) {
                                        'success' => 'Thành công',
                                        'pending' => 'Chờ xử lý',
                                        default => 'Thất bại',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $payment->transaction_code ?? '-' }}</td>
                                    <td>{{ $payment->invoice->invoice_code ?? '-' }}</td>
                                    <td>{{ $payment->invoice->room->room_code ?? '-' }}</td>
                                    <td>{{ $payment->invoice->contract->tenant->full_name ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($payment->amount_paid, 0, ',', '.') }} VNĐ</td>
                                    <td>{{ $methodLabel }}</td>
                                    <td>{{ $payment->payment_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td><span class="badge bg-secondary">{{ $statusLabel }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">Không có thanh toán để xuất</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex justify-content-end">
            {{ $payments->links() }}
        </div>
    </div>
@endsection
