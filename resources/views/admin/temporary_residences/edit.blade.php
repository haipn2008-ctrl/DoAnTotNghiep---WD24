@extends('layouts.admin.index')

@section('title', 'Chỉnh sửa giấy tạm trú')
@section('page_title', 'Chỉnh sửa giấy tạm trú')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div class="flex items-start gap-4">
        <a href="{{ route('admin.temporary_residences.show', $temporaryResidence) }}" class="app-icon-action mt-1 inline-flex shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-700" aria-label="Quay lại chi tiết" title="Quay lại chi tiết">
            <i class="bx bx-arrow-back text-xl" aria-hidden="true"></i><span class="sr-only">Quay lại chi tiết</span>
        </a>
        <div>
            <p class="text-sm font-semibold text-indigo-600">QUẢN LÝ LƯU TRÚ</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Cập nhật giấy tạm trú</h2>
            <p class="mt-1 text-sm text-slate-500">Điều chỉnh thời hạn, mã hồ sơ, ghi chú hoặc thay minh chứng mới.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.temporary_residences.update', $temporaryResidence) }}" enctype="multipart/form-data" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @csrf @method('PUT')
        @include('admin.temporary_residences._form')
    </form>
</div>
@endsection
