@extends('layouts.admin.index')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Danh sách hóa đơn</h4>
                <div>
                    <a href="{{ route('admin.invoices.generate') }}" class="btn btn-primary">Sinh hóa đơn</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-2">
                    <select name="month" class="form-select">
                        <option value="">-- Chọn tháng --</option>
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="year" class="form-select">
                        <option value="">-- Chọn năm --</option>
                        <option value="2025" {{ request('year') == '2025' ? 'selected' : '' }}>2025</option>
                        <option value="2026" {{ request('year') == '2026' ? 'selected' : '' }}>2026</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="room_id" class="form-select">
                        <option value="">-- Chọn phòng --</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>{{ $room->room_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">-- Trạng thái --</option>
                        <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Chưa thanh toán</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Thanh toán một phần</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Lọc</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary w-100">Đặt lại</a>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Mã hóa đơn</th>
                            <th>Phòng</th>
                            <th>Người thuê</th>
                            <th>Kỳ</th>
                            <th>Tổng tiền</th>
                            <th>Đã thanh toán</th>
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
                                <td>
                                    @if($invoice->month && $invoice->year)
                                        {{ $invoice->month }}/{{ $invoice->year }}
                                    @else
                                        {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('m/Y') }}
                                    @endif
                                </td>
                                <td>{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</td>
                                <td>{{ number_format($invoice->paid_amount, 0, ',', '.') }} VNĐ</td>
                                <td>{{ number_format($invoice->balance_amount, 0, ',', '.') }} VNĐ</td>
                                <td>
                                    <span class="badge bg-{{ $invoice->effective_status == 'paid' ? 'success' : ($invoice->effective_status == 'partial' ? 'warning' : 'secondary') }}">
                                        {{ $invoice->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-sm btn-info">Xem</a>
                                    <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="btn btn-sm btn-warning">Sửa</a>
                                    <a href="{{ route('admin.invoices.print', $invoice->id) }}" class="btn btn-sm btn-dark">In</a>
                                    @if($invoice->effective_status === 'unpaid')
                                        <form action="{{ route('admin.invoices.destroy', $invoice->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('Xóa hóa đơn này?')">Xóa</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">Không có hóa đơn nào.</td>
                            </tr>
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
