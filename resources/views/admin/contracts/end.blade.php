@extends('layouts.admin.home')

@section('content')

<div class="container-fluid py-4">

    {{-- Thông báo --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}

            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}

            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif


    <div class="card shadow-sm border-0">

        <div class="card-header bg-white d-flex justify-content-between align-items-center">

            <h4 class="mb-0 fw-bold">
                📄 Danh sách kết thúc hợp đồng
            </h4>

            <span class="badge bg-danger fs-6">
                {{ $contracts->count() }} hợp đồng
            </span>

        </div>

        <div class="card-body">

            {{-- Tìm kiếm --}}
            <form method="GET" class="row g-3 mb-4">

                <div class="col-md-5">

                    <input type="text"
                           name="keyword"
                           class="form-control"
                           placeholder="Tìm mã HĐ, người thuê, phòng..."
                           value="{{ request('keyword') }}">

                </div>

                <div class="col-md-3">

                    <button class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Tìm kiếm
                    </button>

                    <a href="{{ route('admin.contracts.end.list') }}"
                       class="btn btn-secondary">
                        Làm mới
                    </a>

                </div>

            </form>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-primary">

                        <tr>

                            <th width="60">STT</th>

                            <th>Mã HĐ</th>

                            <th>Người thuê</th>

                            <th>Phòng</th>

                            <th>Ngày bắt đầu</th>

                            <th>Ngày kết thúc</th>

                            <th>Còn lại</th>

                            <th>Trạng thái</th>

                            <th class="text-center" width="150">
                                Thao tác
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    @forelse($contracts as $contract)

                        <tr>

                            <td>
                                {{ $loop->iteration }}
                            </td>

                            <td class="fw-bold text-primary">
                                {{ $contract->contract_code }}
                            </td>

                            <td>
                                {{ $contract->tenant->full_name }}
                            </td>

                            <td>

                                <span class="badge bg-info text-dark">

                                    {{ $contract->room->room_code }}

                                </span>

                            </td>

                            <td>

                                {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}

                            </td>

                            <td>

                                {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}

                            </td>

                            <td>
                                @php
                                    $today = \Carbon\Carbon::today();
                                    $endDate = \Carbon\Carbon::parse($contract->end_date);

                                    $days = $today->diffInDays($endDate, false);
                                @endphp

                                @if($days > 0)

                                    <span class="badge bg-success">
                                        Còn {{ $days }} ngày
                                    </span>

                                @elseif($days == 0)

                                    <span class="badge bg-warning text-dark">
                                        Hôm nay
                                    </span>

                                @else

                                    <span class="badge bg-danger">
                                        Quá hạn {{ abs($days) }} ngày
                                    </span>

                                @endif
                            </td>

                            <td>

                                <span class="badge bg-success">

                                    Đang thuê

                                </span>

                            </td>

                            <td class="text-center">

                                <a href="{{ route('admin.contracts.end.form', $contract->id) }}"
                                class="btn btn-danger btn-sm">

                                    <i class="fas fa-times-circle"></i>
                                    Kết thúc

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="8" class="text-center py-5 text-muted">

                                <i class="fas fa-folder-open fa-3x mb-3"></i>

                                <br>

                                Không có hợp đồng nào đang hoạt động.

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection