@extends('layouts.admin.home')

@section('content')

    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-warning">
                <h5 class="mb-0 fw-bold text-dark">
                    Cập nhật khách thuê
                </h5>
            </div>

            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('admin.tenants.update', $tenant->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Tài khoản đăng nhập
                            </label>

                            <select name="user_id" class="form-select">

                                <option value="">
                                    -- Chọn tài khoản --
                                </option>

                                @foreach($users as $user)

                                                        <option value="{{ $user->id }}" {{
                                    old('user_id', $tenant->user_id) == $user->id
                                    ? 'selected'
                                    : ''
                                            }}>
                                                            {{ $user->name }}
                                                            ({{ $user->email }})
                                                        </option>

                                @endforeach

                            </select>

                            @error('user_id')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Họ và tên
                            </label>

                            <input type="text" name="full_name" class="form-control"
                                value="{{ old('full_name', $tenant->full_name) }}">

                            @error('full_name')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Ngày sinh
                            </label>

                            <input type="date" name="date_of_birth" class="form-control"
                                value="{{ old('date_of_birth', $tenant->date_of_birth) }}">

                            @error('date_of_birth')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                CCCD
                            </label>

                            <input type="text" name="cccd" class="form-control" value="{{ old('cccd', $tenant->cccd) }}">

                            @error('cccd')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Ngày cấp CCCD
                            </label>

                            <input type="date" name="cccd_issue_date" class="form-control"
                                value="{{ old('cccd_issue_date', $tenant->cccd_issue_date) }}">

                            @error('cccd_issue_date')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">
                                Nơi cấp CCCD
                            </label>

                            <input type="text" name="cccd_issue_place" class="form-control"
                                value="{{ old('cccd_issue_place', $tenant->cccd_issue_place) }}">

                            @error('cccd_issue_place')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Số điện thoại
                            </label>

                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $tenant->phone) }}">

                            @error('phone')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Email
                            </label>

                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', $tenant->email) }}">

                            @error('email')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">
                                Địa chỉ
                            </label>

                            <textarea name="address" class="form-control"
                                rows="3">{{ old('address', $tenant->address) }}</textarea>

                            @error('address')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>

                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i>
                            Cập nhật
                        </button>

                        <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">
                            Quay lại
                        </a>
                    </div>

                </form>

            </div>
        </div>

    </div>
@endsection
