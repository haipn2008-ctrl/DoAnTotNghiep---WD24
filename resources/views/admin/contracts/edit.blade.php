@extends('layouts.admin.home')

@section('content')

<div class="container-fluid py-4">
    <div class="card shadow-sm">
    <div class="card-header bg-white">
        <h4 class="mb-0">
            ✏️ Sửa thông tin người thuê
        </h4>
    </div>

    <div class="card-body">

        <form action="{{ route('admin.contracts.update', $contract->id) }}"
              method="POST">

            @csrf
            @method('PUT')

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Họ tên
                    </label>
                    <input type="text"
                           name="full_name"
                           value="{{ $contract->tenant->full_name }}"
                           class="form-control"
                           required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        CCCD
                    </label>
                    <input type="text"
                           name="cccd"
                           value="{{ $contract->tenant->cccd }}"
                           class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Số điện thoại
                    </label>
                    <input type="text"
                           name="phone"
                           value="{{ $contract->tenant->phone }}"
                           class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Email
                    </label>
                    <input type="email"
                           name="email"
                           value="{{ $contract->tenant->email }}"
                           class="form-control">
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">
                        Địa chỉ
                    </label>
                    <textarea name="address"
                              class="form-control"
                              rows="3">{{ $contract->tenant->address }}</textarea>
                </div>

            </div>

            <div class="mt-3">

                <button type="submit"
                        class="btn btn-primary">
                    💾 Cập nhật
                </button>

                <a href="{{ route('admin.contracts.show', $contract->id) }}"
                   class="btn btn-secondary">
                    Quay lại
                </a>

            </div>

        </form>

    </div>
</div>

</div>
@endsection
