@extends('layouts.admin.index')

@section('title', 'Quản lý điện / nước')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-sm-6">
                <h4 class="mb-sm-0 font-size-18">Nhập chỉ số Điện/Nước</h4>
            </div>
            <div class="col-sm-6">
                <form action="{{ route('admin.utilities.create') }}" method="GET"
                    class="d-flex justify-content-end align-items-center">
                    <label class="me-2 mb-0">Kỳ chốt:</label>
                    <select name="month" class="form-select form-select-sm w-auto me-2" onchange="this.form.submit()">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>Tháng
                                {{ $m }}</option>
                        @endfor
                    </select>
                    <select name="year" class="form-select form-select-sm w-auto me-2" onchange="this.form.submit()">
                        @for ($y = date('Y'); $y >= date('Y') - 2; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Năm {{ $y }}
                            </option>
                        @endfor
                    </select>
                </form>
            </div>
        </div>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Danh sách phòng - Tháng {{ $month }}/{{ $year }}</h5>
                    </div>
                    <div class="card-body px-0 pt-0">

                        <form action="{{ route('admin.utilities.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">

                            <div class="table-responsive">
                                <table class="table align-middle table-nowrap table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 15%">Tên Phòng</th>
                                            <th class="text-center" style="width: 15%">Số Điện Cũ</th>
                                            <th class="text-center" style="width: 20%">Số Điện Mới</th>
                                            <th class="text-center" style="width: 15%">Số Nước Cũ</th>
                                            <th class="text-center" style="width: 20%">Số Nước Mới</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($readings as $index => $item)
                                            <tr>
                                                <td class="fw-bold">
                                                    {{ $item['room_name'] }}
                                                    <input type="hidden" name="readings[{{ $index }}][room_id]"
                                                        value="{{ $item['room_id'] }}">
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" class="form-control bg-light text-center"
                                                        name="readings[{{ $index }}][electricity_old]"
                                                        value="{{ $item['electricity_old'] }}" readonly>
                                                </td>
                                                <td class="text-center">
                                                    <input type="number"
                                                        class="form-control text-center text-primary fw-bold"
                                                        name="readings[{{ $index }}][electricity_new]"
                                                        min="{{ $item['electricity_old'] }}" placeholder="Nhập số điện..."
                                                        required>
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" class="form-control bg-light text-center"
                                                        name="readings[{{ $index }}][water_old]"
                                                        value="{{ $item['water_old'] }}" readonly>
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" class="form-control text-center text-info fw-bold"
                                                        name="readings[{{ $index }}][water_new]"
                                                        min="{{ $item['water_old'] }}" placeholder="Nhập số nước..."
                                                        required>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    <i class="mdi mdi-inbox-remove fs-1"></i>
                                                    <p>Không có phòng nào đang được thuê để nhập chỉ số!</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if (count($readings) > 0)
                                <div class="card-footer bg-white text-end mt-3 border-top">
                                    <a href="{{ route('admin.utilities.index') }}" class="btn btn-light me-2">Hủy</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-content-save"></i> Lưu tất cả chỉ số
                                    </button>
                                </div>
                            @endif

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
