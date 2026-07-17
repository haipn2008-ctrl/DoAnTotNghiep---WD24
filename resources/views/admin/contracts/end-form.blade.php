@extends('layouts.admin.index')

@section('title', 'Xác nhận kết thúc | Quản lý phòng trọ')
@section('page_title', 'Xác nhận kết thúc')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Hợp đồng {{ $contract->contract_code }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Xác nhận kết thúc hợp đồng</h2>
            </div>

            <a href="{{ route('admin.contracts.end.list') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
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

        <section class="rounded-lg border border-amber-200 bg-amber-50 p-5">
            <h3 class="font-semibold text-amber-950">Lưu ý trước khi kết thúc</h3>
            <p class="mt-1 text-sm leading-6 text-amber-800">Hãy đảm bảo người thuê đã thanh toán đầy đủ, chốt chỉ số điện nước, bàn giao tài sản và phòng sẽ được chuyển về trạng thái trống sau khi xác nhận.</p>
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
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Kết thúc</span><span class="font-semibold text-rose-700">{{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</span></div>
                </div>
            </section>

            <form action="{{ route('admin.contracts.end', $contract->id) }}" method="POST" class="rounded-lg border border-slate-200 bg-white shadow-sm">
                @csrf

                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Thông tin kết thúc</h3>
                </div>

                <div class="space-y-5 p-5">
                    <div>
                        <label for="actual_end_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày trả phòng thực tế</label>
                        <input id="actual_end_date" type="date" name="actual_end_date" value="{{ old('actual_end_date', date('Y-m-d')) }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" required>
                    </div>

                    <div>
                        <label for="termination_reason" class="mb-1.5 block text-sm font-semibold text-slate-700">Lý do kết thúc</label>
                        <select id="termination_reason" name="termination_reason" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                            <option value="expired" @selected(old('termination_reason') === 'expired')>Hết hạn hợp đồng</option>
                            <option value="early" @selected(old('termination_reason') === 'early')>Khách trả phòng trước hạn</option>
                            <option value="violation" @selected(old('termination_reason') === 'violation')>Vi phạm hợp đồng</option>
                            <option value="other" @selected(old('termination_reason') === 'other')>Lý do khác</option>
                        </select>
                    </div>

                    <div>
                        <label for="termination_note" class="mb-1.5 block text-sm font-semibold text-slate-700">Ghi chú</label>
                        <textarea id="termination_note" name="termination_note" rows="5" placeholder="Nhập ghi chú nếu có..." class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('termination_note') }}</textarea>
                    </div>

                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-medium text-slate-700">
                        <input id="confirmEnd" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Tôi xác nhận người thuê đã hoàn tất trả phòng và đồng ý kết thúc hợp đồng.
                    </label>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <a href="{{ route('admin.contracts.end.list') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</a>
                    <button id="btnEnd" type="submit" disabled onclick="return confirm('Bạn chắc chắn muốn kết thúc hợp đồng này?')" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                        <i class="bx bx-check-circle text-lg"></i>
                        Xác nhận kết thúc
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('confirmEnd')?.addEventListener('change', function () {
                document.getElementById('btnEnd').disabled = !this.checked;
            });
        </script>
    @endpush
@endsection
