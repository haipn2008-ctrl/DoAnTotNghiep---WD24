@extends('layouts.admin.index')

@section('title', 'Lập phiếu chi mới | Quản lý phòng trọ')
@section('page_title', 'Lập phiếu chi mới')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.expenses.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-indigo-600">
                    <i class="bx bx-arrow-back"></i> Quay lại danh sách chi phí
                </a>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Lập phiếu chi mới</h2>
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

        <form method="POST" action="{{ route('admin.expenses.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            @if($defaultSupportRequestId)
                <input type="hidden" name="support_request_id" value="{{ $defaultSupportRequestId }}">
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-center gap-2 font-medium text-amber-800">
                        <i class="bx bx-wrench text-lg"></i>
                        Liên kết với Yêu cầu sửa chữa #{{ $defaultSupportRequestId }}
                    </div>
                    <p class="mt-1 text-xs text-amber-700">Phiếu chi này sẽ được gắn với sự cố để tiện theo dõi lịch sử bảo trì phòng.</p>
                </div>
            @endif

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Danh mục chi phí <span class="text-rose-500">*</span>
                    </label>
                    <select name="category" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $defaultCategory) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Chọn đúng danh mục để báo cáo đối soát chính xác.</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Số tiền chi (VNĐ) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="amount" value="{{ old('amount') }}" min="1000" step="1000" placeholder="Ví dụ: 1500000" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-900 focus:border-indigo-500 focus:outline-none">
                    <p class="mt-1 text-xs text-slate-400">Nhập số tiền thực chi ra.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Tên khoản chi / Nội dung <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Ví dụ: Nộp tiền điện EVN T8/2026, Thay vòi sen tắm phòng 201..." required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Ngày chi <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', now()->format('Y-m-d')) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Phòng liên quan
                    </label>
                    <select name="room_id" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Chi phí chung (Cả tòa nhà)</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" @selected((string)old('room_id', $defaultRoomId) === (string)$room->id)>
                                Phòng {{ $room->room_code }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Để trống nếu là khoản chi chung cho toàn bộ nhà.</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Phương thức thanh toán <span class="text-rose-500">*</span>
                    </label>
                    <select name="payment_method" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @foreach($paymentMethods as $key => $label)
                            <option value="{{ $key }}" @selected(old('payment_method') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Đơn vị / Người nhận tiền
                    </label>
                    <input type="text" name="payer_name" value="{{ old('payer_name') }}" placeholder="Ví dụ: Công ty Điện lực EVN, Thợ sửa điện lạnh Tuấn..." class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Ảnh hóa đơn / Chứng từ / Biên lai chuyển khoản
                    </label>
                    <input type="file" name="receipt_image" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-slate-400">Định dạng JPG, PNG, WEBP tối đa 5MB để lưu trữ làm căn cứ đối soát.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-900">
                        Ghi chú thêm
                    </label>
                    <textarea name="notes" rows="3" placeholder="Ghi chú chi tiết thêm về khoản chi..." class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-indigo-500 focus:outline-none">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('admin.expenses.index') }}" class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Hủy bỏ
                </a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-check mr-1"></i> Lưu phiếu chi
                </button>
            </div>
        </form>
    </div>
@endsection

