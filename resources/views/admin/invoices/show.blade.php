@extends('layouts.admin.index')

@section('title', 'Chi tiết hóa đơn')

@section('content')
    @php
        $statusMap = [
            'unpaid' => ['text' => 'Chưa thanh toán', 'class' => 'bg-warning'],
            'partial' => ['text' => 'Thanh toán một phần', 'class' => 'bg-info'],
            'paid' => ['text' => 'Đã thanh toán', 'class' => 'bg-success'],
        ];
        $statusData = $statusMap[$invoice->status] ?? ['text' => $invoice->status, 'class' => 'bg-secondary'];
    @endphp

    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-sm-6">
                <h4 class="mb-sm-0 font-size-18">Chi tiết hóa đơn HDON{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</h4>
            </div>
            <div class="col-sm-6 text-sm-end">
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-light">
                    <i class="mdi mdi-arrow-left me-1"></i> Quay lại
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Các khoản thu</h5>
                        <span class="badge {{ $statusData['class'] }}">{{ $statusData['text'] }}</span>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Khoản thu</th>
                                        <th class="text-center">Chỉ số cũ</th>
                                        <th class="text-center">Chỉ số mới</th>
                                        <th class="text-center">Số lượng</th>
                                        <th class="text-end">Đơn giá</th>
                                        <th class="text-end">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->details as $detail)
                                        <tr>
                                            <td>
                                                <span class="fw-bold">{{ $detail->name }}</span>
                                                @if($detail->note)
                                                    <br>
                                                    <small class="text-muted">{{ $detail->note }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $detail->old_index ?? '-' }}</td>
                                            <td class="text-center">{{ $detail->new_index ?? '-' }}</td>
                                            <td class="text-center">
                                                {{ number_format($detail->quantity, 0, ',', '.') }} {{ $detail->unit }}
                                            </td>
                                            <td class="text-end">{{ number_format($detail->unit_price, 0, ',', '.') }} VND</td>
                                            <td class="text-end fw-bold">{{ number_format($detail->amount, 0, ',', '.') }} VND</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="5" class="text-end">Tổng cộng</th>
                                        <th class="text-end text-success fs-5">{{ number_format($invoice->total_amount, 0, ',', '.') }} VND</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Lịch sử thanh toán</h5>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ngày thanh toán</th>
                                        <th>Phương thức</th>
                                        <th class="text-end">Số tiền</th>
                                        <th>Ghi chú</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoice->payments as $payment)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                                            <td>{{ $payment->payment_method ?? 'cash' }}</td>
                                            <td class="text-end fw-bold">{{ number_format($payment->amount_paid, 0, ',', '.') }} VND</td>
                                            <td>{{ $payment->note ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Chưa có thanh toán.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Thông tin hóa đơn</h5>
                        <div class="mb-3">
                            <small class="text-muted d-block">Kỳ hóa đơn</small>
                            <span class="fw-bold">Tháng {{ $invoice->month }}/{{ $invoice->year }}</span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Ngày lập</small>
                            <span class="fw-bold">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Hạn thanh toán</small>
                            <span class="fw-bold">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</span>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <small class="text-muted d-block">Phòng</small>
                            <span class="fw-bold">{{ $invoice->room->room_code ?? $invoice->contract->room->room_code ?? 'N/A' }}</span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Khách thuê</small>
                            <span class="fw-bold">{{ $invoice->contract->tenant->full_name ?? 'N/A' }}</span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Hợp đồng</small>
                            <span class="fw-bold">{{ $invoice->contract->contract_code ?? 'N/A' }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tổng tiền</span>
                            <span class="fw-bold">{{ number_format($invoice->total_amount, 0, ',', '.') }} VND</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Đã thu</span>
                            <span class="fw-bold text-info">{{ number_format($paidAmount, 0, ',', '.') }} VND</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Còn lại</span>
                            <span class="fw-bold text-danger">{{ number_format($remainingAmount, 0, ',', '.') }} VND</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
