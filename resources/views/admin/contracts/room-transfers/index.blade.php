@extends('layouts.admin.index')

@section('title', 'Quản lý đổi phòng')
@section('page_title', 'Yêu cầu và lịch sử đổi phòng')

@section('content')
<div class="space-y-5">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-slate-950">Quy trình đổi phòng</h2>
        <p class="mt-1 text-sm text-slate-600">Công nợ và hóa đơn đã phát hành vẫn thuộc phòng cũ. Khi thực hiện, hệ thống chốt chỉ số, tài sản và chi phí phòng cũ trước khi bàn giao phòng mới.</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-bold text-slate-950">Danh sách đổi phòng</h3></div>
        @forelse($roomTransfers as $transfer)
            <article id="request-{{ $transfer->id }}" class="border-b border-slate-100 p-5 last:border-0">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-bold text-slate-950">{{ $transfer->contract?->contract_code }} · {{ $transfer->contract?->tenant?->full_name }}</h4>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $transfer->status === 'pending' ? 'bg-amber-100 text-amber-800' : ($transfer->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') }}">
                                {{ $transfer->status === 'pending' ? 'Chờ xử lý' : ($transfer->status === 'completed' ? 'Đã chuyển' : 'Đã từ chối') }}
                            </span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $transfer->source === 'tenant' ? 'Khách yêu cầu' : 'Admin chủ động' }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-700">Phòng <strong>{{ $transfer->oldRoom?->room_code }}</strong> → <strong>{{ $transfer->newRoom?->room_code }}</strong> · Ngày mong muốn {{ $transfer->requested_transfer_date?->format('d/m/Y') }}</p>
                        <p class="mt-1 text-sm text-slate-600"><strong>Lý do:</strong> {{ $transfer->reason }}</p>
                        @if($transfer->admin_reason)<p class="mt-1 text-sm text-slate-600"><strong>Ý kiến admin:</strong> {{ $transfer->admin_reason }}</p>@endif
                        @if($transfer->status === 'completed')
                            <p class="mt-2 text-xs text-slate-500">Công nợ trước chuyển: {{ number_format((float)$transfer->outstanding_amount, 0, ',', '.') }}đ · Chênh lệch cọc: {{ number_format((float)$transfer->deposit_difference, 0, ',', '.') }}đ @if($transfer->transferInvoice)· Hóa đơn chốt <a class="font-bold text-indigo-700" href="{{ route('admin.invoices.show', $transfer->transferInvoice) }}">{{ $transfer->transferInvoice->invoice_code }}</a>@endif @if($transfer->depositInvoice)· Cọc bổ sung <a class="font-bold text-indigo-700" href="{{ route('admin.invoices.show', $transfer->depositInvoice) }}">{{ $transfer->depositInvoice->invoice_code }}</a>@endif</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 gap-2">
                        @if($transfer->status === 'pending')
                            <a href="{{ route('admin.room-transfers.review', $transfer) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">Kiểm tra và thực hiện</a>
                            <form method="POST" action="{{ route('admin.room-transfers.reject', $transfer) }}" class="flex gap-2">@csrf
                                <input name="admin_reason" required minlength="3" placeholder="Lý do từ chối" class="h-10 w-48 rounded-lg border border-slate-200 px-3 text-sm">
                                <button class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-bold text-rose-700">Từ chối</button>
                            </form>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="p-12 text-center text-sm text-slate-500">Chưa có yêu cầu hoặc lịch sử đổi phòng.</div>
        @endforelse
    </div>
</div>
@endsection
