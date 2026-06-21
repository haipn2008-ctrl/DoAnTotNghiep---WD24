@extends('layouts.admin.home')

@section('content')

<div class="container-fluid py-4">

<div class="card shadow-sm">

    <div class="card-header bg-white">
        <h4 class="mb-0">
            📄 Chi tiết hợp đồng
        </h4>
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-6">

                <h5 class="mb-3 text-primary">
                    Thông tin hợp đồng
                </h5>

                <p>
                    <strong>Mã hợp đồng:</strong>
                    HD{{ str_pad($contract->id, 3, '0', STR_PAD_LEFT) }}
                </p>

                <p>
                    <strong>Ngày bắt đầu:</strong>
                    {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}
                </p>

                <p>
                    <strong>Ngày kết thúc:</strong>
                    {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}
                </p>

                <p>
                    <strong>Trạng thái:</strong>

                    @if($contract->status == 'active')
                        <span class="badge bg-success">
                            Đang thuê
                        </span>

                    @elseif($contract->status == 'ended' || $contract->status == 'ended')
                        <span class="badge bg-danger">
                            Đã kết thúc
                        </span>

                    @else
                        <span class="badge bg-secondary">
                            {{ $contract->status }}
                        </span>
                    @endif
                </p>

            </div>

            <div class="col-md-6">

                <h5 class="mb-3 text-primary">
                    Thông tin phòng
                </h5>

                <p>
                    <strong>Mã phòng:</strong>
                    {{ $contract->room->room_code ?? '---' }}
                </p>
                <p>
                    <strong>Diện tích:</strong>
                    {{ $contract->room->area ?? '---' }} m²
                </p>

            </div>

        </div>

        <hr>

        <h5 class="mb-3 text-primary">
            Thông tin người thuê
        </h5>

        <p>
            <strong>Họ tên:</strong>
            {{ $contract->tenant->full_name ?? '---' }}
        </p>
        <p>
            <strong>CCCD:</strong>
            {{ $contract->tenant->cccd ?? '---' }}
        </p>

        <p>
            <strong>Địa chỉ:</strong>
            {{ $contract->tenant->address ?? '---' }}
        </p>

        @if(isset($contract->tenant))
            <p>
                <strong>Số điện thoại:</strong>
                {{ $contract->tenant->phone }}
            </p>

            <p>
                <strong>Email:</strong>
                {{ $contract->tenant->email }}
            </p>
        @endif

        <hr>

        <h5 class="mb-3 text-primary">
            Thông tin tài chính
        </h5>
        
        <p>
            <strong>Tiền thuê/tháng:</strong>
            {{ number_format($contract->room->price ?? 0, 0, ',', '.') }} VNĐ
        </p>
        <p>
            <strong>Tiền cọc:</strong>
            {{ number_format($contract->deposit_amount, 0, ',', '.') }} VNĐ
        </p>

        <div class="mt-4">
            <a href="{{ route('admin.contracts.edit', $contract->id) }}"
            class="btn btn-warning">
            ✏️ Sửa
        </a>

        <a href="{{ route('admin.contracts.print', $contract->id) }}"
            class="btn btn-info text-white"
            target="_blank">
            🖨️ In hợp đồng
            </a>

        <a href="{{ route('admin.contracts.index') }}"
            class="btn btn-secondary">
            ← Quay lại
        </a>

</div>


    </div>

</div>

</div>

@endsection
