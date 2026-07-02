@extends('layouts.admin.index')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Sửa hóa đơn #{{ $invoice->id }}</h4>
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">Quay lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.invoices.update', $invoice->id) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="unpaid" {{ $invoice->status == 'unpaid' ? 'selected' : '' }}>Chưa thanh toán</option>
                        <option value="partial" {{ $invoice->status == 'partial' ? 'selected' : '' }}>Thanh toán một phần</option>
                        <option value="paid" {{ $invoice->status == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ghi chú</label>
                    <textarea name="note" class="form-control"></textarea>
                </div>
                <button class="btn btn-primary">Lưu</button>
            </form>
        </div>
    </div>
</div>
@endsection
