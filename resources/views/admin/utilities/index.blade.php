@extends('layouts.admin.index')

@section('title', 'Quản lý điện / nước')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-sm-6">
                <h4 class="mb-sm-0 font-size-18">Kiểm tra chỉ số Điện/Nước</h4>
            </div>
            <div class="col-sm-6">
                {{-- Đồng bộ form lọc: Tự động submit khi thay đổi (onchange) giống trang create --}}
                <form action="{{ route('admin.utilities.index') }}" method="GET"
                    class="d-flex justify-content-end align-items-center">
                    <label class="me-2 mb-0">Kỳ:</label>
                    <select name="month" class="form-select form-select-sm w-auto me-2" onchange="this.form.submit()">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>Tháng
                                {{ $m }}
                            </option>
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

        {{-- Giữ nguyên phần thống kê của index vì nó cần thiết cho trang tổng quan --}}
        <div class="row">
            <div class="col-xl-4 col-md-4">
                <div class="card card-h-100">
                    <div class="card-body">
                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Tổng điện tiêu thụ</span>
                        <h4 class="mb-1 text-primary">
                            {{ number_format($totalElectricity) }} <span class="font-size-16 text-muted">kWh</span>
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-4">
                <div class="card card-h-100">
                    <div class="card-body">
                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Tổng nước tiêu thụ</span>
                        <h4 class="mb-1 text-info">
                            {{ number_format($totalWater) }} <span class="font-size-16 text-muted">Khối</span>
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-4">
                <div class="card card-h-100">
                    <div class="card-body">
                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Tiến độ chốt số</span>
                        <h4 class="mb-1 {{ $roomsRead < $totalRooms ? 'text-warning' : 'text-success' }}">
                            {{ $roomsRead }} / {{ $totalRooms }} <span class="font-size-16 text-muted">Phòng</span>
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        {{-- Đồng bộ tiêu đề Card có hiển thị tháng/năm --}}
                        <h5 class="card-title mb-0">Chi tiết các phòng đã nhập - Tháng
                            {{ $month }}/{{ $year }}</h5>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-nowrap table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 15%">Tên Phòng</th>
                                        <th class="text-center" style="width: 12%">Số Điện Cũ</th>
                                        <th class="text-center" style="width: 12%">Số Điện Mới</th>
                                        <th class="text-center" style="width: 18%">Dùng (kWh)</th>
                                        <th class="text-center" style="width: 12%">Số Nước Cũ</th>
                                        <th class="text-center" style="width: 12%">Số Nước Mới</th>
                                        <th class="text-center" style="width: 18%">Dùng (Khối)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($readings as $item)
                                        @php
                                            $dienDung = $item->electricity_new - $item->electricity_old;
                                            $nuocDung = $item->water_new - $item->water_old;
                                            $activeContract = $item->room ? $item->room->contracts->first() : null;
                                            $startDate = $activeContract && $activeContract->start_date
                                                ? \Carbon\Carbon::parse($activeContract->start_date)->format('d/m/Y')
                                                : 'N/A';
                                        @endphp
                                        <tr>
                                            <td class="fw-bold align-middle">
                                                <span class="fs-6">{{ $item->room->room_code ?? 'Phòng trống' }}</span>
                                                <br>
                                                <small class="text-muted fw-normal">
                                                    <i class="mdi mdi-calendar-check"></i> Ngày thuê:
                                                    {{ $startDate }}
                                                </small>
                                            </td>

                                            {{-- Đồng bộ UI điện --}}
                                            <td class="text-center align-middle">{{ $item->electricity_old }}</td>
                                            <td class="text-center align-middle text-primary fw-bold fs-6">
                                                {{ $item->electricity_new }}
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="text-success fw-bold">Dùng: {{ $dienDung }}</span>
                                            </td>

                                            {{-- Đồng bộ UI nước --}}
                                            <td class="text-center align-middle">{{ $item->water_old }}</td>
                                            <td class="text-center align-middle text-info fw-bold fs-6">
                                                {{ $item->water_new }}
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="text-success fw-bold">Dùng: {{ $nuocDung }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <div class="mb-3">
                                                    <i class="mdi mdi-text-box-search-outline" style="font-size: 3rem;"></i>
                                                </div>
                                                Chưa có dữ liệu chốt số cho tháng {{ $month }}/{{ $year }}.
                                                <br>
                                                <a href="{{ route('admin.utilities.create', ['month' => $month, 'year' => $year]) }}"
                                                    class="btn btn-primary mt-3">
                                                    <i class="mdi mdi-plus-circle-outline me-1"></i> Nhập số ngay
                                                </a>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
