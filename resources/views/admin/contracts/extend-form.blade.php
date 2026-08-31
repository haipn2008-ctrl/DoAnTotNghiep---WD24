@extends('layouts.admin.index')

@section('title', 'Gia hạn hợp đồng | Quản lý phòng trọ')
@section('page_title', 'Gia hạn hợp đồng')

@section('content')
@php
    $outstanding = $contract->invoices()
        ->whereIn('status', [\App\Models\Invoice::STATUS_UNPAID, \App\Models\Invoice::STATUS_PARTIAL])
        ->get()->sum(fn ($invoice) => (float) $invoice->remaining_amount);
@endphp
<div class="space-y-6">
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-semibold">Không thể gia hạn hợp đồng:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-medium text-slate-500">Hợp đồng {{ $contract->contract_code }}</p><h2 class="mt-1 text-2xl font-bold text-slate-950">Lập phụ lục gia hạn</h2></div>
        <a href="{{ route('admin.contracts.extend.list') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Quay lại</a>
    </div>

    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900">Sau khi xác nhận, thời hạn và giá thuê mới sẽ được áp dụng ngay cho hợp đồng.</div>

    <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-slate-950">Thông tin hiện tại</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Người đại diện</dt><dd class="text-right font-semibold">{{ $contract->tenant->full_name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Phòng</dt><dd class="font-semibold">{{ $contract->room->room_code }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Ngày hết hạn</dt><dd class="font-semibold text-rose-700">{{ $contract->end_date->format('d/m/Y') }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Giá thuê</dt><dd class="font-semibold">{{ number_format($contract->monthly_rent, 0, ',', '.') }} VNĐ/tháng</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Công nợ</dt><dd class="font-semibold {{ $outstanding > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format($outstanding, 0, ',', '.') }} VNĐ</dd></div>
            </dl>
        </section>

        <form action="{{ route('admin.contracts.extend', $contract) }}" method="POST" class="rounded-xl border border-slate-200 bg-white shadow-sm">
            @csrf
            <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Thông tin gia hạn</h3></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <label class="text-sm font-semibold text-slate-700">Ngày kết thúc mới
                    <input type="date" name="new_end_date" min="{{ $contract->end_date->copy()->addDay()->format('Y-m-d') }}" value="{{ old('new_end_date', $contract->end_date->copy()->addYear()->format('Y-m-d')) }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 font-normal">
                </label>
                <label class="text-sm font-semibold text-slate-700">Giá thuê mới (VNĐ/tháng)
                    <input type="number" name="proposed_monthly_rent" min="0" step="1000" value="{{ old('proposed_monthly_rent', (float) $contract->monthly_rent) }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 font-normal">
                </label>
                <label class="text-sm font-semibold text-slate-700 sm:col-span-2">Lý do gia hạn
                    <textarea name="reason" rows="3" required class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 font-normal" placeholder="Ví dụ: Khách có nhu cầu tiếp tục thuê 12 tháng">{{ old('reason') }}</textarea>
                </label>
                @if($outstanding > 0)
                <label class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900 sm:col-span-2">Lý do chấp nhận gia hạn khi còn công nợ
                    <textarea name="financial_override_reason" rows="3" required class="mt-2 w-full rounded-lg border border-amber-200 bg-white px-3 py-2 font-normal" placeholder="Ghi rõ phê duyệt ngoại lệ và kế hoạch thu nợ">{{ old('financial_override_reason') }}</textarea>
                </label>
                @endif
                <label class="flex items-start gap-3 rounded-lg bg-slate-50 p-4 text-sm text-slate-700 sm:col-span-2">
                    <input type="checkbox" name="confirm_extend" value="1" required @checked(old('confirm_extend')) class="mt-0.5 rounded border-slate-300 text-indigo-600">
                    <span>Xác nhận thông tin trên dùng để lập phụ lục gia hạn</span>
                </label>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                <a href="{{ route('admin.contracts.extend.list') }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</a>
                <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Lập phụ lục gia hạn</button>
            </div>
        </form>
    </div>
</div>
@endsection
