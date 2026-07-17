@extends('layouts.admin.index')

@section('title', 'Thêm tài khoản | Quản lý phòng trọ')
@section('page_title', 'Thêm tài khoản')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Hệ thống và cài đặt</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Thêm tài khoản người dùng</h2>
            </div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i class="bx bx-arrow-back text-lg"></i>
                Quay lại
            </a>
        </div>

        @include('admin.users._form')
    </div>
@endsection
