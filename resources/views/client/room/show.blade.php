@extends('layouts.client.index')

@section('title', 'Phòng của tôi | Cổng khách thuê')
@section('page_title', 'Phòng của tôi')

@section('content')
    <div class="space-y-5">
        <div><p class="text-sm font-medium text-slate-500">Thông tin nơi ở hiện tại</p><h2 class="mt-1 text-2xl font-bold text-slate-950">Phòng của tôi</h2></div>

        @if($room)
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="grid lg:grid-cols-[360px_1fr]">
                    <div class="bg-slate-100">
                        @if($room->thumbnail)<img src="{{ asset('storage/'.$room->thumbnail) }}" alt="Phòng {{ $room->room_code }}" class="h-full min-h-64 w-full object-cover">@else<div class="flex min-h-64 items-center justify-center text-6xl text-slate-300">□</div>@endif
                    </div>
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4"><div><p class="text-sm text-slate-500">Mã phòng</p><h3 class="mt-1 text-3xl font-bold text-slate-950">{{ $room->room_code }}</h3></div><span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Đang thuê</span></div>
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Tầng</p><p class="mt-1 text-lg font-bold">{{ $room->floor }}</p></div>
                            <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Diện tích</p><p class="mt-1 text-lg font-bold">{{ number_format($room->area, 0, ',', '.') }} m²</p></div>
                            <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Giá thuê</p><p class="mt-1 text-lg font-bold">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ/tháng</p></div>
                            <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Số người</p><p class="mt-1 text-lg font-bold">{{ $contract->number_of_people }}/{{ $room->max_people }} người</p></div>
                        </div>
                        @if($room->description)<div class="mt-5"><p class="text-sm font-semibold text-slate-700">Mô tả</p><p class="mt-2 text-sm leading-6 text-slate-500">{{ $room->description }}</p></div>@endif
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Tài sản bàn giao</h3>
                <div class="mt-4 flex flex-wrap gap-2">@forelse($room->amenities->where('category', \App\Models\Amenity::CATEGORY_ASSET) as $asset)<span class="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">{{ $asset->name }} × {{ $asset->pivot->quantity }}</span>@empty<span class="text-sm text-slate-500">Chưa cập nhật tài sản bàn giao.</span>@endforelse</div>
            </section>
        @else
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center"><p class="font-semibold text-slate-950">Chưa có phòng đang thuê</p><p class="mt-2 text-sm text-slate-500">Thông tin phòng sẽ xuất hiện khi tài khoản có hợp đồng đang hiệu lực.</p></div>
        @endif
    </div>
@endsection
