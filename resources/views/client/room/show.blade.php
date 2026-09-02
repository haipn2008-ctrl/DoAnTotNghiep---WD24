@extends('layouts.client.index')

@section('title', 'Phòng của tôi | Cổng khách thuê')
@section('page_title', 'Phòng của tôi')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 p-6 text-white shadow-lg shadow-indigo-200/60 sm:p-8">
            <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/10"></div>
            <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div class="flex items-center gap-4">
                    <span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 sm:flex"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 11.25 12 4.5l8.25 6.75M6.5 9.5v10h11v-10M9.5 19.5v-6h5v6" /></svg></span>
                    <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-indigo-100">Thông tin nơi ở hiện tại</p><h2 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Phòng của tôi</h2><p class="mt-2 text-sm text-indigo-100">Xem thông tin phòng, tài sản và hiện trạng đang được bàn giao.</p></div>
                </div>
                @if($contracts->isNotEmpty())
                    <span class="inline-flex w-fit items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-3.5 py-2 text-xs font-bold text-white backdrop-blur-sm"><span class="h-2 w-2 rounded-full bg-emerald-300"></span>{{ $contracts->count() }} phòng đang thuê</span>
                @endif
            </div>
        </section>

        @if($contracts->isNotEmpty())
            @if($contracts->count() > 1)
                <nav class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-label="Chọn nhanh phòng đang thuê">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div><h3 class="font-bold text-slate-900">Danh sách phòng đang thuê</h3><p class="mt-0.5 text-sm text-slate-500">Chọn phòng để chuyển nhanh đến thông tin chi tiết.</p></div>
                        <span class="w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $contracts->count() }} phòng</span>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($contracts as $roomContract)
                            <a href="#room-{{ $roomContract->id }}" class="group flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3 hover:border-indigo-300 hover:bg-indigo-50">
                                <span class="flex min-w-0 items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 11.25 12 4.5l8.25 6.75M6.5 9.5v10h11v-10" /></svg></span><span class="min-w-0"><strong class="block truncate text-sm text-slate-900">Phòng {{ $roomContract->room?->room_code ?? '—' }}</strong><small class="mt-0.5 block truncate text-xs text-slate-500">{{ $roomContract->contract_code }}</small></span></span>
                                <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
                            </a>
                        @endforeach
                    </div>
                </nav>
            @endif

            <div @class(['space-y-6', 'mx-auto max-w-5xl' => $contracts->count() === 1, 'max-w-6xl' => $contracts->count() > 1])>
                @foreach($contracts as $contract)
                    @php
                        $room = $contract->room;
                        $assets = $room?->amenities?->where('category', \App\Models\Amenity::CATEGORY_ASSET) ?? collect();
                        $roomImages = $room?->images?->filter(fn ($image) => ! $image->contract_id || (int) $image->contract_id === (int) $contract->id) ?? collect();
                        $hasThumbnail = $room?->thumbnail && ! str_starts_with($room->thumbnail, 'room-evidence/');
                    @endphp
                    <article id="room-{{ $contract->id }}" class="flex scroll-mt-24 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-200 hover:shadow-lg hover:shadow-slate-200/70">
                        <div class="relative h-48 overflow-hidden bg-gradient-to-br from-indigo-50 via-slate-100 to-violet-100 sm:h-64">
                            @if($hasThumbnail)
                                <img src="{{ route('client.room.thumbnail', $room) }}" alt="Ảnh đại diện phòng {{ $room?->room_code }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full flex-col items-center justify-center text-indigo-300"><span class="flex h-20 w-20 items-center justify-center rounded-3xl bg-white/60 shadow-sm"><i class="bx bx-building-house text-5xl"></i></span><p class="mt-3 text-sm font-semibold text-indigo-400">Chưa có ảnh đại diện phòng</p></div>
                            @endif
                            <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 bg-gradient-to-t from-slate-950/85 via-slate-950/40 to-transparent px-5 pb-4 pt-16 text-white">
                                <div><p class="text-[11px] font-semibold uppercase tracking-wider text-white/70">{{ $contract->contract_code }}</p><h3 class="mt-0.5 text-2xl font-bold tracking-tight">Phòng {{ $room?->room_code ?? '—' }}</h3></div>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-emerald-500/90 px-3 py-1 text-xs font-bold shadow-sm backdrop-blur"><span class="h-1.5 w-1.5 rounded-full bg-white"></span>Đang thuê</span>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-5 sm:p-6">
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3"><p class="text-[11px] font-medium text-slate-500">Tầng</p><p class="mt-1 text-sm font-bold text-slate-950">{{ $room?->floor ?? '—' }}</p></div>
                                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3"><p class="text-[11px] font-medium text-slate-500">Diện tích</p><p class="mt-1 text-sm font-bold text-slate-950">{{ $room?->area ? number_format($room->area, 0, ',', '.').' m²' : '—' }}</p></div>
                                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3"><p class="text-[11px] font-medium text-slate-500">Tiền thuê/tháng</p><p class="mt-1 text-sm font-bold text-slate-950">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ</p></div>
                                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-3"><p class="text-[11px] font-medium text-slate-500">Người đang ở</p><p class="mt-1 text-sm font-bold text-slate-950">{{ $contract->number_of_people }}/{{ $room?->max_people ?? '—' }}</p></div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 rounded-lg px-1 text-xs font-medium text-slate-500">
                                <span class="inline-flex items-center gap-1.5"><i class="bx bx-calendar text-base text-indigo-500"></i>{{ $contract->start_date?->format('d/m/Y') }} – {{ ($contract->extend_end_date ?? $contract->end_date)?->format('d/m/Y') }}</span>
                                <span class="inline-flex items-center gap-1.5"><i class="bx bx-images text-base text-indigo-500"></i>{{ $roomImages->count() }} ảnh hiện trạng</span>
                            </div>

                            <section class="mt-5 border-t border-slate-100 pt-5">
                                <div class="flex items-center justify-between gap-3"><div><h4 class="font-bold text-slate-900">Tài sản trong phòng</h4><p class="mt-0.5 text-xs text-slate-500">Thiết bị và vật dụng đang được bàn giao cho bạn.</p></div><span class="shrink-0 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">{{ $assets->count() }} loại</span></div>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    @forelse($assets as $asset)
                                        <article class="flex min-h-20 min-w-0 gap-3 rounded-xl border border-slate-200 bg-white p-2.5 transition hover:border-indigo-200 hover:bg-indigo-50/30">
                                            @if($asset->pivot->image_path)
                                                <a href="{{ route('client.room.assets.image', [$room, $asset]) }}" data-image-modal data-image-title="{{ $asset->name }} · Phòng {{ $room->room_code }}" class="shrink-0 overflow-hidden rounded-lg"><img src="{{ route('client.room.assets.image', [$room, $asset]) }}" alt="{{ $asset->name }}" class="h-16 w-16 object-cover transition hover:scale-105"></a>
                                            @else
                                                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-400"><i class="bx bx-package text-2xl"></i></span>
                                            @endif
                                            <div class="min-w-0"><p class="truncate text-sm font-bold text-slate-900">{{ $asset->name }}</p><p class="mt-1 text-xs font-semibold text-slate-600">Số lượng: {{ $asset->pivot->quantity }}</p><p class="mt-1 text-xs {{ $asset->pivot->condition === 'damaged' ? 'font-semibold text-rose-600' : 'text-emerald-600' }}">{{ $asset->pivot->condition === 'damaged' ? 'Có hư hỏng' : 'Sử dụng bình thường' }}</p>@if($asset->pivot->note)<p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $asset->pivot->note }}</p>@endif</div>
                                        </article>
                                    @empty
                                        <p class="text-sm text-slate-500">Chưa cập nhật tài sản.</p>
                                    @endforelse
                                </div>
                            </section>

                            @if($roomImages->isNotEmpty())
                                <details class="group mt-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-bold text-slate-700"><span class="inline-flex items-center gap-2"><i class="bx bx-images text-lg text-indigo-600"></i>Nhật ký ảnh hiện trạng <span class="font-medium text-slate-400">({{ $roomImages->count() }} ảnh)</span></span><i class="bx bx-chevron-down text-xl transition group-open:rotate-180"></i></summary>
                                    <p class="mt-1 text-xs text-slate-500">Ảnh được lưu theo thời gian để đối chiếu tình trạng bàn giao và sử dụng phòng.</p>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach($roomImages as $image)
                                            <a href="{{ route('client.room.evidence.image', [$room, $image]) }}" data-image-modal data-image-title="{{ $image->caption ?: 'Ảnh hiện trạng phòng '.$room?->room_code }}" class="group overflow-hidden rounded-lg border border-slate-200 bg-white">
                                                <img src="{{ route('client.room.evidence.image', [$room, $image]) }}" alt="{{ $image->caption ?: 'Ảnh hiện trạng phòng' }}" loading="lazy" class="h-28 w-full object-cover transition group-hover:scale-[1.02]">
                                                <span class="block p-2 text-xs text-slate-600">
                                                    <strong class="block truncate text-slate-800">{{ $image->caption ?: 'Ảnh hiện trạng' }}</strong>
                                                    {{ $image->taken_at?->format('d/m/Y H:i') ?? 'Chưa rõ thời điểm' }}
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <div class="mt-5 flex min-h-14 items-center justify-between gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-500"><span class="inline-flex items-center gap-2 font-semibold"><i class="bx bx-images text-lg text-slate-400"></i>Nhật ký ảnh hiện trạng</span><span class="text-xs">Chưa có ảnh</span></div>
                            @endif

                            <div class="mt-auto flex flex-col gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                                <a href="{{ route('client.contracts.show', $contract) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"><i class="bx bx-file text-lg"></i>Xem hợp đồng</a>
                                <a href="{{ route('client.room.members.index', ['contract' => $contract->id]) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 hover:shadow-md"><i class="bx bx-group text-lg"></i>Xem thành viên</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 11.25 12 4.5l8.25 6.75M6.5 9.5v10h11v-10M9.5 19.5v-6h5v6" /></svg></span><p class="mt-4 font-semibold text-slate-950">Chưa có phòng đang thuê</p><p class="mt-1 text-sm text-slate-500">Thông tin phòng sẽ xuất hiện khi hợp đồng bắt đầu có hiệu lực.</p></div>
        @endif
    </div>
@endsection
