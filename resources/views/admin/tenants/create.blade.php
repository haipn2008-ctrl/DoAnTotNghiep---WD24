```blade
@extends('layouts.admin.index')

@section('title', 'Thêm khách thuê | Quản lý phòng trọ')
@section('page_title', 'Thêm khách thuê')

@section('content')

    <div class="mx-auto max-w-6xl">

        {{-- Header --}}
        <div class="mb-5 flex items-center justify-between">

            <div>
                <div class="mb-1 text-sm text-slate-500">
                    Quản lý khách thuê
                </div>

                <h1 class="text-xl font-bold text-slate-950">
                    Thêm khách thuê
                </h1>
            </div>

            <a href="{{ route('admin.tenants.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">

                <i class="bx bx-arrow-back text-lg"></i>

                Quay lại

            </a>

        </div>


        {{-- Form --}}
        <form action="{{ route('admin.tenants.store') }}" method="POST">

            @csrf

            @include('admin.tenants._form')

        </form>

    </div>

@endsection
```
