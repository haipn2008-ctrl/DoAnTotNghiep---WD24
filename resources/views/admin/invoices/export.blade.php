@extends('layouts.admin.home')

@section('title', 'Xuất danh sách hóa đơn')

@section('content')
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Xuất danh sách hóa đơn</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Hóa đơn</a></li>
                            <li class="breadcrumb-item active">Xuất danh sách hóa đơn</li>
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
                            Trang xuất danh sách hóa đơn chỉ dùng để tải file CSV toàn bộ hóa đơn theo bộ lọc hiện tại.
                        </p>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.invoices.export.download', request()->only(['month','year','status','keyword'])) }}" class="btn btn-success px-4 py-2">
                            <i class="fas fa-file-csv me-1"></i> Xuất file CSV
                        </a>
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary px-4 py-2">
                            Quay lại danh sách hóa đơn
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
                                <th>Mã hóa đơn</th>
                                <th>Kỳ</th>
                                <th>Phòng</th>
                                <th>Khách thuê</th>
                                <th class="text-end">Tổng tiền</th>
                                <th class="text-end">Đã thu</th>
                                <th class="text-end">Còn lại</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                @php
                                    $paidAmount = $invoice->paid_amount ?? $invoice->payments()->success()->sum('amount_paid');
                                    $remainingAmount = max(0, $invoice->total_amount - $paidAmount);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $invoice->invoice_code }}</td>
                                    <td>{{ sprintf('%02d', $invoice->month) }}/{{ $invoice->year }}</td>
                                    <td>{{ $invoice->room->room_code ?? '-' }}</td>
                                    <td>{{ $invoice->contract->tenant->full_name ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</td>
                                    <td class="text-end">{{ number_format($paidAmount, 0, ',', '.') }} VNĐ</td>
                                    <td class="text-end">{{ number_format($remainingAmount, 0, ',', '.') }} VNĐ</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $invoice->status_label }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">Không có hóa đơn để xuất</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex justify-content-end">
            {{ $invoices->links() }}
        </div>
    </div>
@endsection
