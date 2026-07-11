@extends('layouts.admin.index')

@section('title', 'Cập nhật giá | Quản lý phòng trọ')
@section('page_title', 'Cập nhật giá')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-slate-500">Hệ thống và cài đặt</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Cập nhật {{ $typeData['label'] }}</h2>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Vui lòng kiểm tra lại thông tin.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Giá hiện tại</p>
                <p class="mt-4 text-3xl font-bold text-slate-950">{{ number_format($currentValue, 0, ',', '.') }}đ</p>
                <p class="mt-2 text-sm text-slate-500">{{ $typeData['unit'] }}</p>
                <p class="mt-5 rounded-lg bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">{{ $typeData['description'] }}</p>
            </section>

            <form action="{{ route('admin.settings.update', ['type' => $type]) }}" method="POST" class="rounded-lg border border-slate-200 bg-white shadow-sm">
                @csrf
                @method('PUT')

                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Thông tin cập nhật</h3>
                    <p class="text-sm text-slate-500">Giá mới sẽ được áp dụng khi phát hành hóa đơn tiếp theo.</p>
                </div>

                <div class="p-5">
                    <label for="{{ $typeData['field'] }}" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ $typeData['label'] }} ({{ $typeData['unit'] }})</label>
                    <input id="{{ $typeData['field'] }}" type="number" step="0.01" name="{{ $typeData['field'] }}" value="{{ old($typeData['field'], $currentValue) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @error($typeData['field']) <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end border-t border-slate-200 px-5 py-4">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        <i class="bx bx-save text-lg"></i>
                        Lưu {{ mb_strtolower($typeData['label']) }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
