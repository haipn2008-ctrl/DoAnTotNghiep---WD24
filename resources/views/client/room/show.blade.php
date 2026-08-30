@extends('layouts.client.index')

@section('title', 'Phòng của tôi | Cổng khách thuê')
@section('page_title', 'Phòng của tôi')

@php
    $evidenceLabels = [
        \App\Models\RoomImage::TYPE_BASELINE => 'Trước khi bàn giao phòng',
        \App\Models\RoomImage::TYPE_HANDOVER => 'Khi bàn giao phòng',
        \App\Models\RoomImage::TYPE_CHECKOUT => 'Khi nhận lại phòng',
        \App\Models\RoomImage::TYPE_MAINTENANCE => 'Hiện trạng bảo trì',
        \App\Models\RoomImage::TYPE_GENERAL => 'Ảnh hiện trạng',
        \App\Models\RoomImage::TYPE_LEGACY => 'Ảnh phòng trước đây',
    ];
@endphp

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold text-indigo-600">Thông tin nơi ở hiện tại</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Phòng của tôi</h2>
                <p class="mt-2 text-sm text-slate-500">Theo dõi thông tin phòng, giá thuê và tài sản đã bàn giao.</p>
            </div>
            @if($room)
                <a href="{{ route('client.room.members.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                    <i class="bx bx-group text-lg"></i>
                    Xem thành viên
                </a>
            @endif
        </div>

        @if($room)
            <section id="room-information" class="scroll-mt-24 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="grid lg:grid-cols-[360px_1fr]">
                    <div class="relative min-h-64 overflow-hidden bg-gradient-to-br from-indigo-100 via-slate-100 to-violet-100">
                        @if($room->thumbnail)<img src="{{ asset('storage/'.$room->thumbnail) }}" alt="Phòng {{ $room->room_code }}" class="absolute inset-0 h-full w-full object-cover">@else<div class="flex h-full min-h-64 items-center justify-center"><i class="bx bx-building-house text-7xl text-indigo-200"></i></div>@endif
                        <span class="absolute bottom-4 left-4 rounded-lg bg-slate-950/70 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur">Phòng {{ $room->room_code }}</span>
                    </div>
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4"><div><p class="text-sm text-slate-500">Mã phòng</p><h3 class="mt-1 text-3xl font-bold text-slate-950">{{ $room->room_code }}</h3></div><span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Đang thuê</span></div>
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="flex items-center gap-2 text-sm text-slate-500"><i class="bx bx-layer"></i>Tầng</p><p class="mt-2 text-lg font-bold text-slate-950">{{ $room->floor }}</p></div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="flex items-center gap-2 text-sm text-slate-500"><i class="bx bx-expand"></i>Diện tích</p><p class="mt-2 text-lg font-bold text-slate-950">{{ number_format($room->area, 0, ',', '.') }} m²</p></div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="flex items-center gap-2 text-sm text-slate-500"><i class="bx bx-wallet"></i>Giá thuê</p><p class="mt-2 text-lg font-bold text-slate-950">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ/tháng</p></div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="flex items-center gap-2 text-sm text-slate-500"><i class="bx bx-user"></i>Sức chứa</p><p class="mt-2 text-lg font-bold text-slate-950">{{ $contract->number_of_people }}/{{ $room->max_people }} người</p></div>
                        </div>
                        @if($room->description)<div class="mt-5"><p class="text-sm font-semibold text-slate-700">Mô tả</p><p class="mt-2 text-sm leading-6 text-slate-500">{{ $room->description }}</p></div>@endif
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700"><i class="bx bx-package text-xl"></i></span><div><h3 class="font-semibold text-slate-950">Tài sản bàn giao</h3><p class="mt-0.5 text-xs text-slate-500">Danh sách tài sản đi kèm phòng khi nhận bàn giao.</p></div></div>
                <div class="mt-4 flex flex-wrap gap-2">@forelse($room->amenities->where('category', \App\Models\Amenity::CATEGORY_ASSET) as $asset)<span class="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">{{ $asset->name }} × {{ $asset->pivot->quantity }}</span>@empty<span class="text-sm text-slate-500">Chưa cập nhật tài sản bàn giao.</span>@endforelse</div>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col justify-between gap-3 border-b border-slate-200 bg-gradient-to-r from-white to-indigo-50/50 px-5 py-5 sm:flex-row sm:items-center sm:px-6">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                            <i class="bx bx-images text-xl"></i>
                        </span>
                        <div>
                            <h3 class="font-semibold text-slate-950">Ảnh tài sản và hiện trạng phòng</h3>
                            <p class="mt-1 text-sm leading-5 text-slate-500">Ảnh do ban quản lý ghi nhận kèm thời gian máy chủ, giúp đối chiếu tài sản và hiện trạng khi bàn giao.</p>
                        </div>
                    </div>
                    <span class="self-start rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 sm:self-auto">
                        {{ $room->images->count() }} ảnh
                    </span>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3 sm:p-6">
                    @forelse($room->images as $image)
                        <article class="group overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg hover:shadow-indigo-100/60">
                            <a href="{{ asset('storage/'.$image->path) }}" data-image-modal data-image-title="{{ $evidenceLabels[$image->evidence_type] ?? 'Ảnh hiện trạng phòng' }}" class="relative block overflow-hidden bg-slate-100">
                                <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $evidenceLabels[$image->evidence_type] ?? 'Ảnh hiện trạng phòng' }}" loading="lazy" class="h-52 w-full object-cover transition duration-300 group-hover:scale-[1.02]">
                                <span class="absolute bottom-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-slate-950/65 text-white opacity-0 backdrop-blur transition group-hover:opacity-100">
                                    <i class="bx bx-expand-alt text-lg"></i>
                                </span>
                            </a>
                            <div class="space-y-2 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <h4 class="text-sm font-bold text-slate-950">{{ $evidenceLabels[$image->evidence_type] ?? 'Ảnh hiện trạng phòng' }}</h4>
                                    <span class="shrink-0 rounded-full bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700">Đã ghi nhận</span>
                                </div>
                                <p class="flex items-center gap-2 text-xs text-slate-500">
                                    <i class="bx bx-time-five text-sm text-slate-400"></i>
                                    {{ $image->taken_at?->format('d/m/Y H:i') ?? 'Chưa rõ thời điểm' }}
                                </p>
                                <p class="flex items-center gap-2 text-xs text-slate-500">
                                    <i class="bx bx-user text-sm text-slate-400"></i>
                                    {{ $image->uploader?->name ?? 'Dữ liệu chuyển đổi' }}
                                </p>
                                @if($image->caption)
                                    <p class="border-t border-slate-100 pt-2 text-xs leading-5 text-slate-600">{{ $image->caption }}</p>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center sm:col-span-2 xl:col-span-3">
                            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                                <i class="bx bx-image-alt text-3xl"></i>
                            </span>
                            <h4 class="mt-4 font-semibold text-slate-800">Chưa có ảnh hiện trạng</h4>
                            <p class="mt-1 text-sm text-slate-500">Ảnh tài sản sẽ xuất hiện sau khi ban quản lý ghi nhận.</p>
                        </div>
                    @endforelse
                </div>
            </section>

        @else
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center"><p class="font-semibold text-slate-950">Chưa có phòng đang thuê</p><p class="mt-2 text-sm text-slate-500">Thông tin phòng sẽ xuất hiện khi tài khoản có hợp đồng đang hiệu lực.</p></div>
        @endif
    </div>
@endsection
