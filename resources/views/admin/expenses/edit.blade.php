@extends('layouts.admin.index')

@section('title', 'Chỉnh sửa phiếu chi | Quản lý phòng trọ')
@section('page_title', 'Chỉnh sửa phiếu chi')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.expenses.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-indigo-600">
                    <i class="bx bx-arrow-back"></i> Quay lại danh sách chi phí
                </a>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Chỉnh sửa phiếu chi: {{ $expense->expense_code }}</h2>
            </div>
        </div>

        @if($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <p class="font-semibold">Vui lòng kiểm tra lại các thông tin sau:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.expenses.update', $expense) }}" enctype="multipart/form-data" class="space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            @if($expense->support_request_id)
                <input type="hidden" name="support_request_id" value="{{ $expense->support_request_id }}">
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-center gap-2 font-medium text-amber-800">
                        <i class="bx bx-wrench text-lg"></i>
                        Liên kết với Yêu cầu sửa chữa #{{ $expense->support_request_id }}
                    </div>
                </div>
            @endif

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Danh mục chi phí <span class="text-rose-500">*</span>
                    </label>
                    <select name="category" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $expense->category) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Số tiền chi (VNĐ) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="amount" value="{{ old('amount', (int)$expense->amount) }}" min="1000" step="1000" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-900 focus:border-indigo-500 focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Tên khoản chi / Nội dung <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title', $expense->title) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Ngày chi <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Phòng liên quan
                    </label>
                    <select name="room_id" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Chi phí chung (Cả tòa nhà)</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" @selected((string)old('room_id', $expense->room_id) === (string)$room->id)>
                                Phòng {{ $room->room_code }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Phương thức thanh toán <span class="text-rose-500">*</span>
                    </label>
                    <select name="payment_method" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @foreach($paymentMethods as $key => $label)
                            <option value="{{ $key }}" @selected(old('payment_method', $expense->payment_method) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Đơn vị / Người nhận tiền
                    </label>
                    <input type="text" name="payer_name" value="{{ old('payer_name', $expense->payer_name) }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Ảnh hóa đơn / Chứng từ
                    </label>
                    @if($expense->receiptExists())
                        <div class="mb-3 flex items-center gap-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <a href="{{ route('admin.expenses.receipt', $expense) }}" target="_blank" class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:underline">
                                <i class="bx bx-image text-lg"></i> Xem chứng từ hiện tại
                            </a>
                            <label class="inline-flex items-center gap-1.5 text-xs text-rose-600 cursor-pointer">
                                <input type="checkbox" name="remove_receipt" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                Xóa ảnh chứng từ này
                            </label>
                        </div>
                    @endif
                    <input type="file" name="receipt_image" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-slate-400">Tải lên ảnh mới nếu muốn thay đổi.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Ghi chú thêm
                    </label>
                    <textarea name="notes" rows="3" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-indigo-500 focus:outline-none">{{ old('notes', $expense->notes) }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('admin.expenses.index') }}" class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Hủy bỏ
                </a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-check mr-1"></i> Cập nhật phiếu chi
                </button>
            </div>
        </form>
    </div>
@endsection

