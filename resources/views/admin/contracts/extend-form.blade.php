@extends('layouts.admin.index')

@section('title', 'Xác nhận gia hạn | Quản lý phòng trọ')
@section('page_title', 'Xác nhận gia hạn')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Hợp đồng {{ $contract->contract_code }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Xác nhận gia hạn hợp đồng</h2>
            </div>

            <a href="{{ route('admin.contracts.extend.list') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i class="bx bx-arrow-back text-lg"></i>
                Quay lại
            </a>
        </div>

        <section class="rounded-lg border border-sky-200 bg-sky-50 p-5">
            <h3 class="font-semibold text-sky-950">Lưu ý khi gia hạn</h3>
            <p class="mt-1 text-sm leading-6 text-sky-800">Đảm bảo người thuê muốn tiếp tục thuê, đã thanh toán các khoản còn nợ và ngày kết thúc mới lớn hơn ngày kết thúc hiện tại.</p>
        </section>

        <div class="grid gap-6 lg:grid-cols-[380px_1fr]">
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Thông tin hợp đồng</h3>
                </div>
                <div class="space-y-3 p-5 text-sm">
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Mã hợp đồng</span><span class="font-semibold text-slate-950">{{ $contract->contract_code }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Người thuê</span><span class="font-semibold text-slate-950">{{ $contract->tenant->full_name }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Phòng</span><span class="font-semibold text-slate-950">{{ $contract->room->room_code }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Bắt đầu</span><span class="font-semibold text-slate-950">{{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Kết thúc hiện tại</span><span class="font-semibold text-rose-700">{{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</span></div>
                </div>
            </section>

            <form action="{{ route('admin.contracts.extend', $contract->id) }}" method="POST" class="rounded-lg border border-slate-200 bg-white shadow-sm">
                @csrf

                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Thông tin gia hạn</h3>
                </div>

                <div class="space-y-5 p-5">
                    <div>
                        <label for="new_end_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày kết thúc mới</label>
                        <input id="new_end_date" type="date" name="new_end_date" value="{{ \Carbon\Carbon::parse($contract->end_date)->addMonth()->format('Y-m-d') }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" required>
                    </div>

                    <div>
                        <label for="extend_reason" class="mb-1.5 block text-sm font-semibold text-slate-700">Lý do gia hạn</label>
                        <select id="extend_reason" name="extend_reason" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                            <option value="tenant_request">Người thuê có nhu cầu tiếp tục thuê</option>
                            <option value="renew_contract">Gia hạn theo hợp đồng</option>
                            <option value="agreement">Hai bên thỏa thuận</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>

                    <div>
                        <label for="extend_note" class="mb-1.5 block text-sm font-semibold text-slate-700">Ghi chú</label>
                        <textarea id="extend_note" name="extend_note" rows="5" placeholder="Nhập ghi chú..." class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></textarea>
                    </div>

                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-medium text-slate-700">
                        <input id="confirmExtend" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Tôi xác nhận hai bên đã thống nhất gia hạn hợp đồng.
                    </label>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <a href="{{ route('admin.contracts.extend.list') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</a>
                    <button id="btnExtend" type="submit" disabled class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                        <i class="bx bx-check-circle text-lg"></i>
                        Xác nhận gia hạn
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('confirmExtend')?.addEventListener('change', function () {
                document.getElementById('btnExtend').disabled = !this.checked;
            });
        </script>
    @endpush
@endsection
