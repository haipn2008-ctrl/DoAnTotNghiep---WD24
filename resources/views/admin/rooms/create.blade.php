@extends('layouts.admin.index')

@section('title', 'Thêm phòng mới | Quản lý phòng trọ')
@section('page_title', 'Thêm phòng mới')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Quản lý phòng trọ</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Thêm phòng mới</h2>
            </div>

            <a href="{{ route('admin.rooms.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i class="bx bx-arrow-back text-lg"></i>
                Quay lại
            </a>
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

        <form action="{{ route('admin.rooms.store') }}" method="POST" enctype="multipart/form-data" class="rounded-lg border border-slate-200 bg-white shadow-sm">
            @csrf

            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Thông tin phòng</h3>
            </div>

            <div class="grid gap-5 p-5 md:grid-cols-2">
                <div>
                    <label for="room_code" class="mb-1.5 block text-sm font-semibold text-slate-700">Mã phòng</label>
                    <input id="room_code" type="text" name="room_code" value="{{ old('room_code') }}" placeholder="P101" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @error('room_code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="floor" class="mb-1.5 block text-sm font-semibold text-slate-700">Tầng</label>
                    <select id="floor" name="floor" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" @selected((int) old('floor', 1) === $i)>Tầng {{ $i }}</option>
                        @endfor
                    </select>
                    @error('floor') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="area" class="mb-1.5 block text-sm font-semibold text-slate-700">Diện tích (m²)</label>
                    <input id="area" type="number" step="0.01" name="area" value="{{ old('area') }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @error('area') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="price" class="mb-1.5 block text-sm font-semibold text-slate-700">Giá thuê (VNĐ)</label>
                    <input id="price" type="number" name="price" value="{{ old('price') }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @error('price') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="max_people" class="mb-1.5 block text-sm font-semibold text-slate-700">Sức chứa tối đa</label>
                    <input id="max_people" type="number" min="1" max="20" name="max_people" value="{{ old('max_people', 4) }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @error('max_people') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Tài sản bàn giao</label>
                    @include('admin.rooms.partials.inventory-fields')
                </div>

                <div class="md:col-span-2">
                    <label for="images" class="mb-1.5 block text-sm font-semibold text-slate-700">Ảnh hiện trạng ban đầu</label>
                    <input id="images" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" data-preview-target="images-preview" data-max-files="15" class="js-image-preview-input block w-full rounded-lg border border-slate-200 text-sm text-slate-600 file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                    <div id="images-preview" class="mt-3 hidden rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p data-preview-count class="mb-2 text-xs font-semibold text-slate-600"></p>
                        <div data-preview-grid class="flex flex-wrap gap-3"></div>
                        <p data-preview-error class="mt-2 hidden text-xs font-semibold text-rose-600"></p>
                    </div>
                    @error('images') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    @error('images.*') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="mb-1.5 block text-sm font-semibold text-slate-700">Mô tả</label>
                    <textarea id="description" name="description" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                <a href="{{ route('admin.rooms.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-save text-lg"></i>
                    Thêm phòng
                </button>
            </div>
        </form>
    </div>
@endsection
