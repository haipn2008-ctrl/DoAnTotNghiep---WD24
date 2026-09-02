@extends('layouts.client.index')

@section('title', 'Điện nước của tôi | Cổng khách thuê')
@section('page_title', 'Điện nước của tôi')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 p-6 text-white shadow-lg shadow-indigo-200/60 sm:p-8 lg:flex lg:items-center lg:justify-between">
            <div class="absolute -right-12 -top-16 h-52 w-52 rounded-full bg-white/10"></div>
            <div class="relative flex items-center gap-4">
                <span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 sm:flex"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3.75h8v16.5H8V3.75Zm2.5 4h3M10 16a2 2 0 1 0 4 0 2 2 0 0 0-4 0Z" /></svg></span>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-2xl font-bold sm:text-3xl">Điện nước của tôi</h2>
                    <span class="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 text-xs font-semibold text-indigo-100">Mốc giữa kỳ không tự chia tiền</span>
                </div>
            </div>
            <form method="GET" action="{{ route('client.utilities.index') }}" class="relative mt-5 flex items-end gap-2 rounded-xl border border-white/20 bg-white/10 p-3 backdrop-blur-sm lg:mt-0">
                <div><label class="mb-1 block text-xs font-semibold uppercase text-indigo-100">Năm</label><select name="year" class="h-10 rounded-lg border border-white/30 bg-white px-3 text-sm text-slate-700"><option value="">Tất cả</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>@endforeach</select></div>
                <button class="h-10 rounded-lg bg-white px-4 text-sm font-bold text-indigo-700">Lọc</button>
                @if(request('year'))<a href="{{ route('client.utilities.index') }}" class="inline-flex h-10 items-center rounded-lg border border-white/30 px-3 text-sm font-semibold text-white">Xóa lọc</a>@endif
            </form>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            @forelse($readings as $reading)
                @php
                    $electricityDetail = $reading->invoice?->details->firstWhere('type', 'electricity');
                    $waterDetail = $reading->invoice?->details->firstWhere('type', 'water');
                    $isCheckpoint = $reading->reading_type === 'interim';
                    $readingTitle = match ($reading->reading_type) {
                        'handover' => 'Chỉ số bàn giao',
                        'checkout' => 'Chỉ số trả phòng',
                        'interim' => 'Mốc giữa kỳ',
                        default => 'Kỳ chốt tháng '.$reading->month.'/'.$reading->year,
                    };
                @endphp
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                        <div><h3 class="font-bold text-slate-950">{{ $readingTitle }}</h3><p class="mt-1 text-xs text-slate-500">Phòng {{ $reading->room->room_code ?? '-' }}{{ $reading->record_date ? ' · Chốt ngày '.$reading->record_date->format('d/m/Y') : '' }}</p></div>
                        @if($isCheckpoint)<span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">Mốc đối chiếu</span>@elseif($reading->invoice)<a href="{{ route('client.invoices.show', $reading->invoice) }}" class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Xem hóa đơn</a>@else<span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Chưa có hóa đơn</span>@endif
                    </div>

                    <div class="grid divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                        <div class="p-5">
                            <div class="flex items-center justify-between"><p class="font-semibold text-indigo-700">Điện</p>@if($reading->meterImageExists('electricity'))<a href="{{ route('client.utilities.image', [$reading, 'electricity']) }}" data-image-modal data-image-title="Ảnh đồng hồ điện" class="text-xs font-semibold text-indigo-600">Xem ảnh đồng hồ</a>@elseif($reading->electricity_image)<span class="text-xs font-medium text-amber-700">Ảnh không còn tồn tại</span>@endif</div>
                            <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($reading->electricity_usage, 0, ',', '.') }} <span class="text-sm font-medium text-slate-500">kWh</span></p>
                            <p class="mt-1 text-xs text-slate-500">Chỉ số {{ number_format($reading->electricity_old, 0, ',', '.') }} → {{ number_format($reading->electricity_new, 0, ',', '.') }}</p>
                            @if($electricityDetail)
                                <div class="mt-3 space-y-1 text-sm"><div class="flex justify-between text-slate-500"><span>Đơn giá</span><span>{{ number_format($electricityDetail->unit_price, 0, ',', '.') }}đ/kWh</span></div><div class="flex justify-between font-bold text-slate-950"><span>Tiền điện</span><span>{{ number_format($electricityDetail->amount, 0, ',', '.') }}đ</span></div></div>
                            @endif
                        </div>
                        <div class="p-5">
                            <div class="flex items-center justify-between"><p class="font-semibold text-sky-700">Nước</p>@if($reading->meterImageExists('water'))<a href="{{ route('client.utilities.image', [$reading, 'water']) }}" data-image-modal data-image-title="Ảnh đồng hồ nước" class="text-xs font-semibold text-sky-600">Xem ảnh đồng hồ</a>@elseif($reading->water_image)<span class="text-xs font-medium text-amber-700">Ảnh không còn tồn tại</span>@endif</div>
                            <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($reading->water_usage, 0, ',', '.') }} <span class="text-sm font-medium text-slate-500">m³</span></p>
                            <p class="mt-1 text-xs text-slate-500">Chỉ số {{ number_format($reading->water_old, 0, ',', '.') }} → {{ number_format($reading->water_new, 0, ',', '.') }}</p>
                            @if($waterDetail)
                                <div class="mt-3 space-y-1 text-sm"><div class="flex justify-between text-slate-500"><span>Đơn giá</span><span>{{ number_format($waterDetail->unit_price, 0, ',', '.') }}đ/m³</span></div><div class="flex justify-between font-bold text-slate-950"><span>Tiền nước</span><span>{{ number_format($waterDetail->amount, 0, ',', '.') }}đ</span></div></div>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center lg:col-span-2"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3.75h8v16.5H8V3.75Zm2.5 4h3M10 16a2 2 0 1 0 4 0 2 2 0 0 0-4 0Z" /></svg></span><p class="mt-3 font-semibold text-slate-950">Chưa có chỉ số điện nước</p></div>
            @endforelse
        </div>

        @if($readings->hasPages())<div>{{ $readings->links() }}</div>@endif
    </div>
@endsection
