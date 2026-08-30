@extends('layouts.admin.index')

@section('title', 'Cập nhật giấy tạm trú')
@section('page_title', 'Cập nhật giấy tạm trú')

@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    <div>
        <a href="{{ route('admin.temporary_residences.index') }}" class="text-sm font-semibold text-indigo-700">← Danh sách giấy tạm trú</a>
        <h2 class="mt-2 text-2xl font-bold text-slate-950">Thêm minh chứng giấy tạm trú</h2>
        <p class="mt-1 text-sm text-slate-500">Chỉ hiển thị những người đã được xác nhận đang ở và chưa có giấy còn hiệu lực.</p>
    </div>
    <form method="POST" action="{{ route('admin.temporary_residences.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @include('admin.temporary_residences._form')
    </form>
</div>
@endsection
