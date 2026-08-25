@extends('layouts.admin.index')

@section('title', 'Nhắc thanh toán | Quản lý phòng trọ')
@section('page_title', 'Nhắc thanh toán')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div><p class="text-sm font-medium text-slate-500">{{ $invoice->invoice_code }}</p><h2 class="mt-1 text-2xl font-bold text-slate-950">{{ $invoice->contract->tenant->full_name ?? 'Khách thuê' }}</h2><p class="mt-2 text-sm text-slate-500">Phòng {{ $invoice->room->room_code ?? '-' }} · Hạn {{ $invoice->due_date?->format('d/m/Y') }} · {{ $invoice->debt_bucket_label }}</p></div>
            <div class="flex gap-2"><a href="{{ route('admin.invoices.show', $invoice) }}" class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600">Xem hóa đơn</a><a href="{{ route('admin.debts.index') }}" class="inline-flex h-10 items-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white">Quay lại công nợ</a></div>
        </div>

        @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif

        <div class="grid gap-4 sm:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-sm text-slate-500">Phải thu</p><p class="mt-2 text-xl font-bold text-slate-950">{{ number_format($invoice->payable_amount, 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4"><p class="text-sm text-emerald-700">Đã duyệt</p><p class="mt-2 text-xl font-bold text-emerald-800">{{ number_format($paidAmount, 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4"><p class="text-sm text-amber-700">Chờ duyệt</p><p class="mt-2 text-xl font-bold text-amber-800">{{ number_format($pendingAmount, 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4"><p class="text-sm text-rose-700">Còn nợ</p><p class="mt-2 text-xl font-bold text-rose-800">{{ number_format($remainingAmount, 0, ',', '.') }}đ</p></div>
        </div>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Toàn bộ lịch sử nhắc</h3><p class="mt-1 text-sm text-slate-500">Nhật ký không thể sửa hoặc xóa.</p></div>
                <div class="divide-y divide-slate-100">
                    @forelse($invoice->reminders as $reminder)
                        <article class="p-5"><div class="flex flex-col justify-between gap-2 sm:flex-row"><div><p class="font-semibold text-slate-950">{{ $reminder->channel_label }}</p><p class="mt-1 text-sm text-slate-500">{{ $reminder->reminded_at?->format('H:i d/m/Y') }}</p></div><p class="text-sm text-slate-500">Bởi {{ $reminder->reminded_by_name }}</p></div>@if($reminder->note)<p class="mt-3 rounded-lg bg-slate-50 p-3 text-sm leading-6 text-slate-700">{{ $reminder->note }}</p>@endif</article>
                    @empty
                        <div class="p-10 text-center text-sm text-slate-500">Chưa có lần nhắc nào được ghi nhận.</div>
                    @endforelse
                </div>
            </section>

            <aside class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Gửi nhắc thanh toán</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">Thông báo sẽ được gửi trực tiếp đến tài khoản của khách thuê trong hệ thống.</p>

                @if($canRemind)
                    <form method="POST" action="{{ route('admin.debts.reminders.store', $invoice) }}" class="mt-5 space-y-4">
                        @csrf
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nội dung nhắc</label><textarea name="note" rows="5" maxlength="1000" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Để trống để dùng nội dung nhắc mặc định.">{{ old('note') }}</textarea></div>
                        <button class="h-11 w-full rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">Gửi thông báo cho khách</button>
                    </form>
                @elseif($remindedToday)
                    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Hóa đơn này đã được ghi nhận nhắc hôm nay. Có thể ghi nhận lần tiếp theo từ ngày mai.</div>
                @else
                    <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Hóa đơn không còn công nợ cần nhắc. Lịch sử vẫn được giữ để đối soát.</div>
                @endif
            </aside>
        </div>
    </div>
@endsection
