@extends('layouts.admin.index')

@section('title', 'Phân quyền')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h4 class="card-title">Phân quyền</h4>
            <p class="text-muted">Quản lý danh sách vai trò và mô tả quyền.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vai trò</th>
                            <th>Mô tả</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            <tr>
                                <td>{{ $role->id }}</td>
                                <td>{{ $role->role_name }}</td>
                                <td>{{ $role->role_name === 'Admin' ? 'Toàn quyền quản trị hệ thống' : 'Quyền truy cập người dùng / khách thuê' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
