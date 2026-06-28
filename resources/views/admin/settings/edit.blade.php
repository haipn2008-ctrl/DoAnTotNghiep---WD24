@extends('layouts.admin.index')

@section('title', 'Cập nhật giá')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-sm-6">
                <h4 class="mb-sm-0 font-size-18">Cập nhật {{ $typeData['label'] }}</h4>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-xl-4 col-lg-5">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Giá hiện tại</h5>
                        <p class="text-muted mb-2">{{ $typeData['description'] }}</p>
                        <div class="display-6 fw-bold">{{ number_format($currentValue, 0, ',', '.') }} VNĐ</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8 col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.settings.update', ['type' => $type]) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">{{ $typeData['label'] }}</label>
                                <input type="number" step="0.01" name="{{ $typeData['field'] }}" class="form-control"
                                    value="{{ old($typeData['field'], $currentValue) }}" required>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-content-save"></i> Lưu {{ $typeData['label'] }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
