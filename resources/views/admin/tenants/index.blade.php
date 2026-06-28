@extends('layouts.admin.home')

@section('title', 'Danh sách khách thuê')

@section('content')

    <div class="container mt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h2 class="h3 fw-bold mb-0">
                Danh sách khách thuê
            </h2>

            <a href="{{ route('admin.tenants.create') }}" class="btn text-white shadow-sm"
                style="background-color:#4e73df;">
                <i class="fas fa-plus-circle me-1"></i>
                Thêm khách thuê
            </a>

        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>
            </div>
        @endif

        <div class="card shadow-sm border-0">

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead style="background:#4e73df;color:white;">

                            <tr>
                                <th width="5%">ID</th>
                                <th width="20%">Họ tên</th>
                                <th width="15%">CCCD</th>
                                <th width="15%">SĐT</th>
                                <th width="15%">Phòng thuê</th>
                                <th width="15%">Ngày tạo</th>
                                <th width="15%" class="text-center">
                                    Thao tác
                                </th>
                            </tr>

                        </thead>

                        <tbody>

                            @forelse($tenants as $tenant)

                                @php
                                    $activeContract = $tenant->contracts
                                        ->where('status', 'active')
                                        ->first();
                                @endphp

                                <tr>

                                    <td>
                                        {{ $tenant->id }}
                                    </td>

                                    <td class="fw-semibold">
                                        {{ $tenant->full_name }}
                                    </td>

                                    <td>
                                        {{ $tenant->cccd }}
                                    </td>

                                    <td>
                                        {{ $tenant->phone }}
                                    </td>

                                    <td>

                                        @if($tenant->contracts->where('status', 'active')->count())
                                            <span class="badge bg-success">
                                                Đang thuê
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                Chưa thuê
                                            </span>
                                        @endif

                                    </td>

                                    <td>
                                        {{ $tenant->created_at->format('d/m/Y') }}
                                    </td>

                                    <td>

                                        <div class="d-flex justify-content-center gap-1">

                                            <a href="{{ route('admin.tenants.show', $tenant->id) }}"
                                                class="btn btn-sm btn-info text-white" title="Chi tiết">

                                                <i class="fas fa-eye"></i>

                                            </a>

                                            <a href="{{ route('admin.tenants.edit', $tenant->id) }}"
                                                class="btn btn-sm text-white" style="background-color:#f6c23e;" title="Sửa">

                                                <i class="fas fa-edit"></i>

                                            </a>

                                            <form action="{{ route('admin.tenants.destroy', $tenant->id) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Bạn có chắc muốn xóa khách thuê này?')">

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm text-white"
                                                    style="background-color:#e74a3b;" title="Xóa">

                                                    <i class="fas fa-trash"></i>

                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="7" class="text-center py-5">

                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>

                                        <p class="text-muted mb-0">
                                            Chưa có khách thuê nào
                                        </p>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        <div class="mt-3">

            {{ $tenants->links() }}

        </div>

    </div>

@endsection
