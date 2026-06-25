@extends('layouts.admin.home')

@section('title', 'Xuất danh sách khách thuê')

@section('content')
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Xuất danh sách khách thuê</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.tenants.index') }}">Khách thuê</a></li>
                            <li class="breadcrumb-item active">Xuất danh sách khách thuê</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body bg-light">
                <div class="row">
                    <div class="col-md-8 align-self-center">
                        <p class="mb-0">
                            Trang xuất danh sách khách thuê chỉ dùng để tải file CSV toàn bộ khách thuê.
                        </p>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.tenants.export.download') }}" class="btn btn-success px-4 py-2">
                            <i class="fas fa-file-csv me-1"></i> Xuất file CSV
                        </a>
                        <a href="{{ route('admin.tenants.index') }}" class="btn btn-outline-secondary px-4 py-2">
                            Quay lại danh sách khách thuê
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="text-white" style="background-color:#4e73df;">
                            <tr>
                                <th width="5%">ID</th>
                                <th width="20%">Họ tên</th>
                                <th width="15%">CCCD</th>
                                <th width="12%">SĐT</th>
                                <th width="18%">Phòng thuê</th>
                                <th width="15%">Trạng thái</th>
                                <th width="15%">Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenants as $tenant)
                                @php
                                    $activeRoom = $tenant->contracts
                                        ->where('status', 'active')
                                        ->pluck('room.room_code')
                                        ->first();
                                @endphp
                                <tr>
                                    <td>{{ $tenant->id }}</td>
                                    <td class="fw-semibold">{{ $tenant->full_name }}</td>
                                    <td>{{ $tenant->cccd }}</td>
                                    <td>{{ $tenant->phone }}</td>
                                    <td>{{ $activeRoom ?? '-' }}</td>
                                    <td>
                                        @if($activeRoom)
                                            <span class="badge bg-success">Đang thuê</span>
                                        @else
                                            <span class="badge bg-secondary">Chưa thuê</span>
                                        @endif
                                    </td>
                                    <td>{{ $tenant->created_at->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">Chưa có khách thuê nào</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex justify-content-end">
            {{ $tenants->links() }}
        </div>
    </div>
@endsection
