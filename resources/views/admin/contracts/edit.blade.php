@extends('layouts.admin.index')

@section('title', 'Sửa người thuê | Quản lý phòng trọ')
@section('page_title', 'Sửa thông tin người thuê')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Hợp đồng {{ $contract->contract_code ?: 'HD' . str_pad($contract->id, 3, '0', STR_PAD_LEFT) }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Sửa thông tin người thuê</h2>
            </div>

            <a href="{{ route('admin.contracts.show', $contract) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
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

        <form action="{{ route('admin.contracts.update', $contract) }}" method="POST" class="rounded-lg border border-slate-200 bg-white shadow-sm">
            @csrf
            @method('PUT')

            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Thông tin người thuê</h3>
                <p class="text-sm text-slate-500">Cập nhật thông tin liên hệ gắn với hợp đồng này.</p>
            </div>

            <div class="grid gap-5 p-5 md:grid-cols-2">
                <div>
                    <label for="full_name" class="mb-1.5 block text-sm font-semibold text-slate-700">Họ tên</label>
                    <input id="full_name" type="text" name="full_name" value="{{ old('full_name', $contract->tenant->full_name) }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" required>
                </div>

                <div>
                    <label for="cccd" class="mb-1.5 block text-sm font-semibold text-slate-700">CCCD</label>
                    <input id="cccd" type="text" name="cccd" value="{{ old('cccd', $contract->tenant->cccd) }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                </div>

                <div>
                    <label for="phone" class="mb-1.5 block text-sm font-semibold text-slate-700">Số điện thoại</label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone', $contract->tenant->phone) }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $contract->tenant->email) }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="mb-1.5 block text-sm font-semibold text-slate-700">Địa chỉ</label>
                    <textarea id="address" name="address" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('address', $contract->tenant->address) }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                <a href="{{ route('admin.contracts.show', $contract) }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-save text-lg"></i>
                    Cập nhật
                </button>
            </div>
        </form>
    </div>
@endsection
