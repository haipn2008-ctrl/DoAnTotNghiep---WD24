@extends('layouts.admin.index')

@section('title', 'Thêm tài khoản người dùng')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h4 class="card-title">Thêm tài khoản người dùng</h4>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Quay lại</a>
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

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Họ và tên</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nhập lại mật khẩu</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Vai trò</label>
                    <select name="role_id" class="form-select" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->role_name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Lưu</button>
            </form>
        </div>
    </div>
</div>
@endsection
