    @extends('layouts.admin.home')

@section('content')
<div class="container">
    <h2 class="mb-4">Cập nhật khách thuê</h2>

    <form action="{{ route('admin.tenants.update', $tenant->id) }}"
          method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">
                Họ và tên
            </label>

            <input type="text"
                   name="full_name"
                   class="form-control"
                   value="{{ old('full_name', $tenant->full_name) }}">

            @error('full_name')
                <small class="text-danger">
                    {{ $message }}
                </small>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">
                CCCD
            </label>

            <input type="text"
                   name="cccd"
                   class="form-control"
                   value="{{ old('cccd', $tenant->cccd) }}">

            @error('cccd')
                <small class="text-danger">
                    {{ $message }}
                </small>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">
                Số điện thoại
            </label>

            <input type="text"
                   name="phone"
                   class="form-control"
                   value="{{ old('phone', $tenant->phone) }}">

            @error('phone')
                <small class="text-danger">
                    {{ $message }}
                </small>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">
                Email
            </label>

            <input type="email"
                   name="email"
                   class="form-control"
                   value="{{ old('email', $tenant->email) }}">

            @error('email')
                <small class="text-danger">
                    {{ $message }}
                </small>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">
                Địa chỉ
            </label>

            <textarea name="address"
                      class="form-control"
                      rows="3">{{ old('address', $tenant->address) }}</textarea>

            @error('address')
                <small class="text-danger">
                    {{ $message }}
                </small>
            @enderror
        </div>

        <button type="submit"
                class="btn btn-warning">
            Cập nhật
        </button>

        <a href="{{ route('admin.tenants.index') }}"
           class="btn btn-secondary">
            Quay lại
        </a>
    </form>
</div>
@endsection
