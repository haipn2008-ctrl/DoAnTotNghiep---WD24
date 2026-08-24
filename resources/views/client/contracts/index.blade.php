@extends('layouts.client.index')

@section('title', 'Hợp đồng của tôi | Cổng khách thuê')
@section('page_title', 'Hợp đồng của tôi')

@php
    $statuses = ['draft'=>['Bản nháp','bg-slate-100 text-slate-700'],'pending_signature'=>['Chờ ký','bg-amber-50 text-amber-700'],'pending_deposit'=>['Chờ tiền cọc','bg-orange-50 text-orange-700'],'awaiting_move_in'=>['Chờ nhận phòng','bg-sky-50 text-sky-700'],'active'=>['Đang ở','bg-emerald-50 text-emerald-700'],'expired'=>['Quá hạn vẫn ở','bg-rose-50 text-rose-700'],'settling'=>['Quyết toán','bg-violet-50 text-violet-700'],'completed'=>['Hoàn tất','bg-green-50 text-green-700'],'cancelled'=>['Đã hủy','bg-gray-50 text-gray-700']];
@endphp

@section('content')
    <div class="space-y-5">
        <div><h2 class="text-2xl font-bold text-slate-950">Hợp đồng của tôi</h2></div>
        <div class="space-y-4">
            @forelse($contracts as $contract)
                @php($status = $statuses[$contract->status] ?? ['Không xác định', 'bg-slate-100 text-slate-700'])
                <a href="{{ route('client.contracts.show', $contract) }}" class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-200 md:grid-cols-[1fr_auto] md:items-center">
                    <div><div class="flex flex-wrap items-center gap-2"><h3 class="text-lg font-bold text-slate-950">{{ $contract->contract_code }}</h3><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $status[1] }}">{{ $status[0] }}</span></div><p class="mt-2 text-sm text-slate-500">Phòng {{ $contract->room->room_code ?? '-' }} · {{ $contract->start_date->format('d/m/Y') }} – {{ ($contract->extend_end_date ?? $contract->end_date)->format('d/m/Y') }}</p></div>
                    <div class="md:text-right"><p class="text-sm text-slate-500">Tiền thuê mỗi tháng</p><p class="mt-1 text-xl font-bold text-slate-950">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ</p></div>
                </a>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">Chưa có hợp đồng được liên kết với tài khoản.</div>
            @endforelse
        </div>
        @if($contracts->hasPages())<div>{{ $contracts->links() }}</div>@endif
    </div>
@endsection
