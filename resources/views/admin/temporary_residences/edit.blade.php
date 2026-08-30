@extends('layouts.admin.index')

@section('title', 'Chỉnh sửa giấy tạm trú')
@section('page_title', 'Chỉnh sửa giấy tạm trú')

@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    <div>
        <a href="{{ route('admin.temporary_residences.show', $temporaryResidence) }}" class="text-sm font-semibold text-indigo-700">← Chi tiết giấy tạm trú</a>
        <h2 class="mt-2 text-2xl font-bold text-slate-950">Cập nhật minh chứng</h2>
        <p class="mt-1 text-sm text-slate-500">Không chọn tệp mới nếu chỉ muốn sửa thời hạn, mã hồ sơ hoặc ghi chú.</p>
    </div>
    <form method="POST" action="{{ route('admin.temporary_residences.update', $temporaryResidence) }}" enctype="multipart/form-data" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf @method('PUT')
        @include('admin.temporary_residences._form')
    </form>
</div>
@endsection
