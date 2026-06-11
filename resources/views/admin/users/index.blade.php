@extends('layouts.admin.index')

@section('title', 'Quản lý người dùng')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h4 class="card-title">Danh sách người dùng</h4>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Thêm tài khoản</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <input type="text" name="search" class="form-control" placeholder="Tìm kiếm tên hoặc email" value="{{ $search }}">
                </div>
                <div class="col-auto">
                    <button class="btn btn-secondary">Tìm</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th>Thời gian tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->role->role_name ?? '-' }}</td>
                                <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-warning me-1">Sửa</a>
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Xác nhận xóa tài khoản này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
