@extends('layouts.admin.index')

@section('title', 'Quản lý đổi phòng')
@section('page_title', 'Yêu cầu và lịch sử đổi phòng')

@section('content')
@php
    $pendingCount = $roomTransfers->where('status', 'pending')->count();
    $completedCount = $roomTransfers->where('status', 'completed')->count();
    $rejectedCount = $roomTransfers->where('status', 'rejected')->count();
@endphp
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><p class="text-xs font-bold uppercase tracking-[.16em] text-indigo-600">Quản lý hợp đồng</p><h1 class="mt-1 text-2xl font-bold text-slate-950">Yêu cầu đổi phòng</h1><p class="mt-2 text-sm text-slate-500">Kiểm tra yêu cầu, đối chiếu số liệu phòng cũ và bàn giao phòng mới.</p></div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Tổng yêu cầu', $roomTransfers->count(), 'bg-indigo-50 text-indigo-700'],
            ['Chờ xử lý', $pendingCount, 'bg-amber-50 text-amber-700'],
            ['Đã chuyển', $completedCount, 'bg-emerald-50 text-emerald-700'],
            ['Đã từ chối', $rejectedCount, 'bg-rose-50 text-rose-700'],
        ] as [$label, $count, $color])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><div><p class="text-sm font-medium text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ $count }}</p></div><span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $color }}"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h12m0 0-3-3m3 3-3 3M17 17H5m0 0 3 3m-3-3 3-3" /></svg></span></div></div>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6"><div><h2 class="font-bold text-slate-950">Danh sách đổi phòng</h2><p class="mt-0.5 text-sm text-slate-500">Ưu tiên các yêu cầu đang chờ xử lý.</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $roomTransfers->count() }} yêu cầu</span></div>
        <div class="divide-y divide-slate-100">
            @forelse($roomTransfers as $transfer)
                @php
                    $status = match($transfer->status) {
                        'pending' => ['Chờ xử lý', 'bg-amber-50 text-amber-700', 'bg-amber-500'],
                        'completed' => ['Đã chuyển', 'bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
                        default => ['Đã từ chối', 'bg-rose-50 text-rose-700', 'bg-rose-500'],
                    };
                @endphp
                <article id="request-{{ $transfer->id }}" class="p-5 transition hover:bg-slate-50/70 sm:p-6">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                        <div class="flex min-w-0 gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h12m0 0-3-3m3 3-3 3M17 17H5m0 0 3 3m-3-3 3-3" /></svg></span>
                            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="font-bold text-slate-950">{{ $transfer->contract?->contract_code }} · {{ $transfer->contract?->tenant?->full_name }}</h3><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $status[1] }}"><span class="h-1.5 w-1.5 rounded-full {{ $status[2] }}"></span>{{ $status[0] }}</span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $transfer->source === 'tenant' ? 'Khách yêu cầu' : 'Admin chủ động' }}</span></div>
                                <p class="mt-2 text-sm text-slate-700">Phòng <strong>{{ $transfer->oldRoom?->room_code }}</strong> <span class="mx-1 text-indigo-500">→</span> <strong>{{ $transfer->newRoom?->room_code }}</strong> · Mong muốn {{ $transfer->requested_transfer_date?->format('d/m/Y') }}</p>
                                <p class="mt-1 text-sm text-slate-500"><strong class="text-slate-700">Lý do:</strong> {{ $transfer->reason }}</p>
                                @if($transfer->admin_reason)<p class="mt-1 text-sm text-slate-500"><strong class="text-slate-700">Phản hồi:</strong> {{ $transfer->admin_reason }}</p>@endif
                                @if($transfer->status === 'completed')<p class="mt-2 text-xs text-slate-500">Công nợ trước chuyển: {{ number_format((float)$transfer->outstanding_amount, 0, ',', '.') }}đ · Chênh lệch cọc: {{ number_format((float)$transfer->deposit_difference, 0, ',', '.') }}đ</p>@endif
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                            @if($transfer->status === 'pending')
                                <a href="{{ route('admin.room-transfers.review', $transfer) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white shadow-sm hover:bg-indigo-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>Kiểm tra và thực hiện</a>
                                <form method="POST" action="{{ route('admin.room-transfers.reject', $transfer) }}" class="flex gap-2">@csrf<input name="admin_reason" required minlength="3" placeholder="Lý do từ chối" class="h-11 min-w-0 flex-1 rounded-xl border border-slate-300 px-3 text-sm sm:w-44"><button class="h-11 rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-bold text-rose-700 hover:bg-rose-100">Từ chối</button></form>
                            @elseif($transfer->status === 'completed' && in_array($transfer->contract?->status, \App\Models\Contract::OPEN_OCCUPANCY_STATUSES, true) && $transfer->oldRoom?->status === \App\Models\Room::STATUS_AVAILABLE)
                                <a data-keep-action-label href="{{ route('admin.room-transfers.create', ['contract' => $transfer->contract, 'room_id' => $transfer->old_room_id]) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-bold text-indigo-700 hover:bg-indigo-100">Chuyển lại phòng cũ</a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-6 py-14 text-center"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h12m0 0-3-3m3 3-3 3M17 17H5m0 0 3 3m-3-3 3-3" /></svg></span><p class="mt-4 font-semibold text-slate-800">Chưa có yêu cầu đổi phòng</p></div>
            @endforelse
        </div>
    </section>
</div>
@endsection
