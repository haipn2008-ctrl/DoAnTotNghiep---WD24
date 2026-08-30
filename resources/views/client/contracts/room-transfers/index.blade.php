@extends('layouts.client.index')

@section('title', 'Yêu cầu đổi phòng')

@section('content')
<div class="mx-auto max-w-5xl space-y-5 px-4 py-6">
    <div><p class="text-xs font-bold uppercase tracking-wide text-indigo-500">Hợp đồng</p><h1 class="mt-1 text-2xl font-bold text-slate-950">Yêu cầu đổi phòng</h1><p class="mt-1 text-sm text-slate-600">Chọn phòng còn trống và gửi lý do. Ban quản lý sẽ kiểm tra công nợ, chỉ số và tài sản trước khi thực hiện.</p></div>
    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('client.room-transfers.store') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">@csrf
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="mb-1 block text-sm font-semibold">Hợp đồng đang thuê</label><select name="contract_id" required class="h-11 w-full rounded-lg border border-slate-200 px-3">@foreach($contracts as $contract)<option value="{{ $contract->id }}">{{ $contract->contract_code }} · Phòng {{ $contract->room?->room_code }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-sm font-semibold">Phòng muốn chuyển đến</label><select name="new_room_id" required class="h-11 w-full rounded-lg border border-slate-200 px-3"><option value="">Chọn phòng</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->room_code }} · {{ number_format((float)$room->price,0,',','.') }}đ/tháng · tối đa {{ $room->max_people }} người</option>@endforeach</select></div>
            <div><label class="mb-1 block text-sm font-semibold">Ngày mong muốn chuyển</label><input type="date" name="requested_transfer_date" min="{{ today()->toDateString() }}" max="{{ today()->addDays(30)->toDateString() }}" value="{{ old('requested_transfer_date', today()->toDateString()) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
            <div><label class="mb-1 block text-sm font-semibold">Lý do *</label><textarea name="reason" required minlength="3" rows="3" class="w-full rounded-lg border border-slate-200 p-3">{{ old('reason') }}</textarea></div>
        </div>
        <button class="mt-4 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700" @disabled($contracts->isEmpty() || $rooms->isEmpty())>Gửi yêu cầu</button>
    </form>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-bold">Lịch sử đổi phòng</h2></div>
        @forelse($roomTransfers as $transfer)
            <div class="border-b border-slate-100 p-5 last:border-0"><div class="flex flex-wrap items-center justify-between gap-2"><p class="font-bold">{{ $transfer->oldRoom?->room_code }} → {{ $transfer->newRoom?->room_code }}</p><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $transfer->status === 'pending' ? 'bg-amber-100 text-amber-800' : ($transfer->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') }}">{{ $transfer->status === 'pending' ? 'Chờ duyệt' : ($transfer->status === 'completed' ? 'Đã chuyển' : 'Bị từ chối') }}</span></div><p class="mt-2 text-sm text-slate-600">{{ $transfer->reason }}</p>@if($transfer->admin_reason)<p class="mt-1 text-sm text-slate-600"><strong>Phản hồi:</strong> {{ $transfer->admin_reason }}</p>@endif</div>
        @empty<div class="p-10 text-center text-sm text-slate-500">Bạn chưa gửi yêu cầu đổi phòng.</div>@endforelse
    </section>
</div>
@endsection
