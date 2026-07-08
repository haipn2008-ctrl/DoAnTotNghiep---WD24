@extends('layouts.admin.index')

@section('title', 'Danh sách hóa đơn')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-sm-6">
                <h4 class="mb-sm-0 font-size-18">Danh sách hóa đơn</h4>
            </div>
            <div class="col-sm-6 text-sm-end">
                <a href="{{ route('admin.invoices.generate') }}" class="btn btn-primary">
                    <i class="mdi mdi-file-plus-outline me-1"></i> Sinh hóa đơn
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <span class="text-muted d-block mb-2">Tổng hóa đơn</span>
                        <h4 class="mb-0 text-primary">{{ number_format($summary['count']) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <span class="text-muted d-block mb-2">Tổng phải thu</span>
                        <h4 class="mb-0 text-success">{{ number_format($summary['total_amount'], 0, ',', '.') }} VND</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <span class="text-muted d-block mb-2">Chưa thanh toán</span>
                        <h4 class="mb-0 text-warning">{{ number_format($summary['unpaid']) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <span class="text-muted d-block mb-2">Đã thanh toán</span>
                        <h4 class="mb-0 text-info">{{ number_format($summary['paid']) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <form action="{{ route('admin.invoices.index') }}" method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Tìm kiếm</label>
                        <input type="text" name="keyword" value="{{ $keyword }}" class="form-control"
                            placeholder="Mã HĐ, phòng, khách thuê...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tháng</label>
                        <select name="month" class="form-select">
                            <option value="">Tất cả</option>
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>Tháng {{ $m }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Năm</label>
                        <select name="year" class="form-select">
                            <option value="">Tất cả</option>
                            @for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Năm {{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Chưa thanh toán</option>
                            <option value="partial" {{ $status === 'partial' ? 'selected' : '' }}>Thanh toán một phần</option>
                            <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-light me-1">Xóa lọc</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-magnify me-1"></i> Lọc
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Hóa đơn đã phát hành</h5>
            </div>
            <div class="card-body px-0 pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-nowrap table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã hóa đơn</th>
                                <th>Kỳ</th>
                                <th>Phòng</th>
                                <th>Khách thuê</th>
                                <th class="text-end">Tổng tiền</th>
                                <th class="text-end">Đã thu</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)

                                @php
                                    $paidAmount = $invoice->paid_amount ?? $invoice->payments()->success()->sum('amount_paid');

                                    $status = $invoice->effective_status;

                                    $badge = match ($status) {
                                        'paid' => 'bg-success',
                                        'partial' => 'bg-info',
                                        default => 'bg-warning'
                                    };
                                @endphp

                                <tr>

                                    <td class="fw-bold">
                                        {{ $invoice->invoice_code }}
                                    </td>

                                    <td>
                                        {{ sprintf('%02d', $invoice->month) }}/{{ $invoice->year }}
                                    </td>

                                    <td>
                                        {{ $invoice->room->room_code ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $invoice->contract->tenant->full_name ?? '-' }}
                                        <br>
                                        <small class="text-muted">
                                            {{ $invoice->contract->contract_code ?? '' }}
                                        </small>
                                    </td>

                                    <td class="text-end fw-bold">
                                        {{ number_format($invoice->total_amount, 0, ',', '.') }} đ
                                    </td>

                                    <td class="text-end">
                                        {{ number_format($paidAmount, 0, ',', '.') }} đ
                                    </td>

                                    <td class="text-center">

                                        <span class="badge {{ $badge }}">
                                            {{ $invoice->status_label }}
                                        </span>

                                    </td>

                                    <td class="text-end">

                                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-info btn-sm">

                                            <i class="mdi mdi-eye"></i>

                                        </a>

                                        @if($invoice->canPay())

                                            <a href="{{ route('admin.invoices.show', $invoice) }}#payment-form"
                                               class="btn btn-warning btn-sm"
                                               title="Ghi nhận thanh toán">

                                                <i class="mdi mdi-cash"></i>

                                            </a>

                                        @endif

                                        <a href="{{ route('admin.invoices.print', $invoice) }}" target="_blank"
                                            class="btn btn-secondary btn-sm">

                                            <i class="mdi mdi-printer"></i>

                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8" class="text-center py-5">

                                        Chưa có hóa đơn nào.

                                    </td>

                                </tr>

                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($invoices->hasPages())
                <div class="card-footer bg-white">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
