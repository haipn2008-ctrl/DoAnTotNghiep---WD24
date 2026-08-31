@extends('layouts.client.index')

@section('title', 'Yêu cầu gia hạn | Cổng khách thuê')
@section('page_title', 'Yêu cầu gia hạn')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <h1 class="text-2xl font-bold text-slate-950">Yêu cầu gia hạn hợp đồng</h1>

    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700"><p class="font-bold">Không thể xử lý yêu cầu</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4"><h2 class="font-bold text-slate-900">Gửi yêu cầu gia hạn</h2><p class="mt-1 text-sm text-slate-500">Ban quản lý sẽ kiểm tra công nợ và lập phụ lục. Hợp đồng chỉ gia hạn sau khi hai bên ký bản in và ảnh minh chứng được lưu.</p></div>
        <div class="p-6">
            @if($contracts->isEmpty())
                <div class="py-10 text-center text-sm text-slate-500">Không có hợp đồng đủ điều kiện gửi yêu cầu.</div>
            @else
                <form method="POST" action="{{ route('client.extension-requests.store') }}" class="space-y-5">@csrf
                    <div><label for="contract_id" class="mb-2 block text-sm font-semibold text-slate-900">Hợp đồng <span class="text-rose-500">*</span></label><select name="contract_id" id="contract_id" required class="h-11 w-full rounded-lg border border-slate-300 bg-white px-4 text-sm"><option value="">-- Chọn hợp đồng --</option>@foreach($contracts as $contract)<option value="{{ $contract->id }}" data-end-date="{{ $contract->end_date?->format('Y-m-d') }}" @selected(old('contract_id') == $contract->id)>{{ $contract->contract_code }} — Phòng {{ $contract->room->room_code ?? '-' }}</option>@endforeach</select></div>
                    <div id="contractInfo" class="hidden rounded-xl border border-indigo-100 bg-indigo-50 px-5 py-4"><p class="text-xs font-semibold uppercase text-indigo-500">Ngày kết thúc hiện tại</p><p id="currentEndDate" class="mt-1 font-bold text-indigo-950">-</p></div>
                    <div class="grid gap-5 md:grid-cols-2"><div><label for="requested_end_date" class="mb-2 block text-sm font-semibold">Muốn gia hạn đến <span class="text-rose-500">*</span></label><input type="date" name="requested_end_date" id="requested_end_date" value="{{ old('requested_end_date') }}" required class="h-11 w-full rounded-lg border border-slate-300 px-4 text-sm"></div><div><label for="reason" class="mb-2 block text-sm font-semibold">Lý do gia hạn</label><textarea name="reason" id="reason" rows="3" maxlength="1000" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm" placeholder="Nhập lý do">{{ old('reason') }}</textarea></div></div>
                    <div class="flex justify-end border-t border-slate-100 pt-5"><button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">Gửi yêu cầu gia hạn</button></div>
                </form>
            @endif
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4"><h2 class="font-bold text-slate-900">Yêu cầu và phụ lục gia hạn</h2></div>
        <div class="grid gap-4 bg-slate-50/60 p-4 lg:grid-cols-2 sm:p-6">
            @forelse($extensionRequests as $extension)
                @php
                    $meta = match($extension->status) {
                        'awaiting_confirmation' => ['Chờ ký phụ lục', 'border-sky-200 bg-sky-50 text-sky-700'],
                        'approved' => ['Đã gia hạn', 'border-emerald-200 bg-emerald-50 text-emerald-700'],
                        'rejected' => ['Admin từ chối', 'border-rose-200 bg-rose-50 text-rose-700'],
                        'declined_by_tenant' => ['Bạn không đồng ý', 'border-violet-200 bg-violet-50 text-violet-700'],
                        default => ['Chờ admin xem', 'border-amber-200 bg-amber-50 text-amber-700'],
                    };
                    $terms = $extension->terms_snapshot ?? [];
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-slate-950">{{ $extension->contract->contract_code }}</p><p class="mt-1 text-xs text-slate-500">Phòng {{ $extension->contract->room->room_code ?? '-' }}</p></div><span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $meta[1] }}">{{ $meta[0] }}</span></div>
                    <div class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4"><p class="text-xs font-semibold uppercase text-indigo-400">Thời hạn</p><p class="mt-2 text-sm"><span>{{ $extension->current_end_date?->format('d/m/Y') }}</span><span class="mx-2 text-indigo-400">→</span><strong class="text-indigo-700">{{ ($extension->approved_end_date ?: $extension->requested_end_date)?->format('d/m/Y') }}</strong></p></div>

                    @if($extension->status === 'awaiting_confirmation')
                        <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4">
                            <h3 class="font-semibold text-sky-950">Phụ lục chờ ký trực tiếp</h3>
                            <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2"><div><dt class="text-slate-500">Giá phòng hiện tại</dt><dd class="font-semibold">{{ number_format((float)($terms['old_monthly_rent'] ?? 0), 0, ',', '.') }}đ/tháng</dd></div><div><dt class="text-slate-500">Giá phòng kỳ mới</dt><dd class="font-semibold text-sky-800">{{ number_format((float)$extension->proposed_monthly_rent, 0, ',', '.') }}đ/tháng</dd></div><div><dt class="text-slate-500">Tiền cọc đang giữ</dt><dd class="font-semibold">{{ number_format((float)$extension->proposed_deposit_amount, 0, ',', '.') }}đ</dd></div><div><dt class="text-slate-500">Người tiếp tục thuê</dt><dd class="font-semibold">{{ count($terms['tenants'] ?? []) }} người</dd></div></dl>
                            @if($extension->admin_note)<p class="mt-3 border-t border-sky-200 pt-3 text-sm text-sky-900"><strong>Ghi chú quản lý:</strong> {{ $extension->admin_note }}</p>@endif
                            @if($extension->appendix)<a href="{{ route('client.contract-appendices.show', $extension->appendix) }}" class="mt-3 inline-flex h-10 items-center rounded-lg bg-sky-700 px-4 text-sm font-semibold text-white">Xem phụ lục {{ $extension->appendix->code }}</a>@endif
                        </div>
                        <form method="POST" action="{{ route('client.extension-requests.decline', $extension) }}" class="mt-3 flex gap-2">@csrf<input name="decline_reason" required minlength="3" maxlength="1000" placeholder="Lý do không đồng ý" class="h-11 min-w-0 flex-1 rounded-xl border border-slate-200 px-3 text-sm"><button class="h-11 rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700">Không đồng ý</button></form>
                    @else
                        <div class="mt-4 text-sm leading-6 text-slate-600"><strong>Lý do yêu cầu:</strong> {{ $extension->reason ?: 'Không có' }}@if($extension->admin_note)<p><strong>Phản hồi quản lý:</strong> {{ $extension->admin_note }}</p>@endif @if($extension->tenant_decline_reason)<p><strong>Lý do không đồng ý:</strong> {{ $extension->tenant_decline_reason }}</p>@endif</div>
                    @endif
                </article>
            @empty
                <div class="py-12 text-center text-sm text-slate-500 lg:col-span-2">Chưa có yêu cầu gia hạn.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('contract_id');
    const info = document.getElementById('contractInfo');
    const label = document.getElementById('currentEndDate');
    const input = document.getElementById('requested_end_date');
    if (!select || !info || !label || !input) return;
    const refresh = () => {
        const value = select.options[select.selectedIndex]?.dataset.endDate;
        if (!value) { info.classList.add('hidden'); input.removeAttribute('min'); return; }
        const date = new Date(`${value}T00:00:00`);
        info.classList.remove('hidden');
        label.textContent = date.toLocaleDateString('vi-VN');
        date.setDate(date.getDate() + 1);
        input.min = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    };
    select.addEventListener('change', refresh); refresh();
});
</script>
@endpush
