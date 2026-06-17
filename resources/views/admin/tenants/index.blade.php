@extends('layouts.admin.home')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800 fw-bold">Danh sách khách thuê</h2>
        <a href="{{ route('admin.tenants.create') }}" class="btn text-white px-3 py-2 shadow-sm" style="background-color: #4e73df;">
            <i class="fas fa-plus-circle me-1"></i> Thêm khách thuê mới
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm custom-table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="text-white" style="background-color: #4e73df;">
                        <tr>
                            <th class="ps-3 py-3" width="5%">ID</th>
                            <th class="py-3" width="20%">Họ tên</th>
                            <th class="py-3" width="15%">CCCD</th>
                            <th class="py-3" width="15%">SĐT</th>
                            <th class="py-3" width="15%">Email</th>
                            <th class="py-3" width="15%">Địa chỉ</th>
                            <th class="text-center py-3" width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $tenant)
                        <tr style="transition: all 0.2s;">
                            <td class="ps-3 fw-bold text-secondary">{{ $tenant->id }}</td>
                            <td class="fw-semibold text-dark">{{ $tenant->full_name }}</td>
                            <td><small class="bg-light px-2 py-1 rounded text-muted fw-mono">{{ $tenant->cccd }}</small></td>
                            <td>{{ $tenant->phone }}</td>
                            <td class="text-muted">{{ $tenant->email ?? '---' }}</td>
                            <td>
                                <span class="d-inline-block text-truncate" style="max-width: 150px;" title="{{ $tenant->address }}">
                                    {{ $tenant->address }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('admin.tenants.edit', $tenant->id) }}" class="btn btn-sm text-white px-2 py-1" style="background-color: #f6c23e;" title="Sửa">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>

                                    <form action="{{ route('admin.tenants.destroy', $tenant->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách thuê này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm text-white px-2 py-1" style="background-color: #e74a3b;" title="Xóa">
                                            <i class="fas fa-trash-alt"></i> Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-users-slash fa-2x mb-2 d-block text-gray-300"></i>
                                <p class="mb-0">Chưa có khách thuê nào trong hệ thống.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        {{ $tenants->links() }}
    </div>
</div>

<style>
    .custom-table-card {
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(78, 115, 223, 0.04);
    }
</style>
@endsection
