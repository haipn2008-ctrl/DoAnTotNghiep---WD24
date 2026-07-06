@extends('layouts.admin.home')

@section('content')

<div class="container-fluid py-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h4 class="mb-0">
                📝 Tạo hợp đồng thuê phòng
            </h4>
        </div>
        <div class="card-body">
            @if(session('error'))

                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <form action="{{ route('admin.contracts.store') }}"
                  method="POST">
                @csrf
                <div class="row">
                    <!-- Phòng -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            🏠 Phòng thuê
                        </label>
                        <select name="room_id"
                                class="form-select">
                            <option value="">
                                -- Chọn phòng --
                            </option>
                            @foreach($rooms as $room)
                            <option value="{{ $room->id }}">
                                {{ $room->room_code }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Người thuê -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            👤 Người thuê
                        </label>
                        <select name="tenant_id"
                                class="form-select">
                            <option value="">
                                -- Chọn người thuê --
                            </option>
                            @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}">
                                {{ $tenant->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Ngày bắt đầu -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            📅 Ngày bắt đầu
                        </label>
                        <input type="date"
                               name="start_date"
                               class="form-control">
                    </div>
                    <!-- Ngày kết thúc -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            📅 Ngày kết thúc
                        </label>
                        <input type="date"
                               name="end_date"
                               class="form-control">
                    </div>
                    <!-- Tiền cọc -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            💰 Tiền cọc
                        </label>
                        <input type="number"
                               name="deposit_amount"
                               class="form-control"
                               placeholder="Nhập tiền cọc">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit"
                            class="btn btn-primary px-4">
                        + Tạo hợp đồng
                    </button>
                    <a href="{{ route('admin.contracts.index') }}"
                       class="btn btn-secondary">
                        Quay lại
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection