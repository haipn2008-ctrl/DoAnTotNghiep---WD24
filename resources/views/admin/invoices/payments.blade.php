@extends('layouts.admin.index')

@section('content')
<div class="container-fluid">
    <div class="row">
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
                            <th>Hóa đơn</th>
                            <th>Phòng</th>
                            <th>Người thuê</th>
                            <th>Tổng tiền</th>
                            <th>Còn nợ</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>#{{ $invoice->id }}</td>
                                <td>{{ $invoice->contract->room->room_code ?? '-' }}</td>
                                <td>{{ $invoice->contract->tenant->full_name ?? '-' }}</td>
                                <td>{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</td>
                                <td>{{ number_format($invoice->balance_amount, 0, ',', '.') }} VNĐ</td>
                                <td><span class="badge bg-warning">{{ $invoice->status_label }}</span></td>
                                <td>
                                    <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-sm btn-primary">Thanh toán</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center">Không có hóa đơn cần thanh toán.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
