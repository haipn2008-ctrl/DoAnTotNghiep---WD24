@extends('layouts.admin.index')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Sinh hóa đơn</h4>
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">Quay lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">
                Hóa đơn được sinh tự động từ dữ liệu hợp đồng đang hoạt động, giá phòng, chỉ số điện nước và các mức giá cài đặt trong hệ thống, rồi lưu vào cơ sở dữ liệu.
            </div>
            <form method="POST" action="{{ route('admin.invoices.generate.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tháng</label>
                        <select name="month" class="form-select" required>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Năm</label>
                        <select name="year" class="form-select" required>
                            @foreach($years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phòng / hợp đồng</label>
                        <select name="contract_id" class="form-select">
                            <option value="">-- Sinh cho toàn bộ phòng đang thuê --</option>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}">{{ $contract->room->room_code ?? '-' }} - {{ $contract->tenant->full_name ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary">Sinh hóa đơn</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
