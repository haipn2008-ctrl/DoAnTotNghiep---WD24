@extends('layouts.client.index')

@section('title', 'Phòng của tôi | Cổng khách thuê')
@section('page_title', 'Phòng của tôi')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold text-indigo-600">Thông tin nơi ở hiện tại</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Phòng của tôi</h2>
                <p class="mt-2 text-sm text-slate-500">Theo dõi tất cả phòng, giá thuê và tài sản đã bàn giao theo từng hợp đồng.</p>
            </div>
            @if($contracts->isNotEmpty())
                <span class="inline-flex w-fit items-center rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 ring-1 ring-indigo-200">{{ $contracts->count() }} phòng đang thuê</span>
            @endif
        </div>

        @if($contracts->isNotEmpty())
            <div class="grid gap-5 xl:grid-cols-2">
                @foreach($contracts as $contract)
                    @php
                        $room = $contract->room;
                        $assets = $room?->amenities?->where('category', \App\Models\Amenity::CATEGORY_ASSET) ?? collect();
                        $roomImages = $room?->images?->filter(fn ($image) => ! $image->contract_id || (int) $image->contract_id === (int) $contract->id) ?? collect();
                        $coverImage = $roomImages->first();
                    @endphp
                    <article id="room-{{ $contract->id }}" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                        <div class="relative h-48 overflow-hidden bg-gradient-to-br from-indigo-100 via-slate-100 to-violet-100">
                            @if($coverImage)
                                <img src="{{ asset('storage/'.$coverImage->path) }}" alt="Phòng {{ $room?->room_code }}" class="h-full w-full object-cover">
                            @elseif($room?->thumbnail)
                                <img src="{{ asset('storage/'.$room->thumbnail) }}" alt="Phòng {{ $room->room_code }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full items-center justify-center"><i class="bx bx-building-house text-7xl text-indigo-200"></i></div>
                            @endif
                            <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 bg-gradient-to-t from-slate-950/80 to-transparent px-5 pb-4 pt-12 text-white">
                                <div><p class="text-xs font-medium text-white/75">{{ $contract->contract_code }}</p><h3 class="mt-1 text-2xl font-bold">Phòng {{ $room?->room_code ?? '—' }}</h3></div>
                                <span class="rounded-full bg-emerald-500/90 px-3 py-1 text-xs font-bold backdrop-blur">Đang thuê</span>
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Tầng</p><p class="mt-1 font-bold text-slate-950">{{ $room?->floor ?? '—' }}</p></div>
                                <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Diện tích</p><p class="mt-1 font-bold text-slate-950">{{ $room?->area ? number_format($room->area, 0, ',', '.').' m²' : '—' }}</p></div>
                                <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Tiền thuê</p><p class="mt-1 font-bold text-slate-950">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ</p></div>
                                <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Số người</p><p class="mt-1 font-bold text-slate-950">{{ $contract->number_of_people }}/{{ $room?->max_people ?? '—' }}</p></div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500">
                                <span><i class="bx bx-calendar mr-1"></i>{{ $contract->start_date?->format('d/m/Y') }} – {{ ($contract->extend_end_date ?? $contract->end_date)?->format('d/m/Y') }}</span>
                                <span><i class="bx bx-images mr-1"></i>{{ $roomImages->count() }} ảnh hiện trạng</span>
                            </div>

                            <div class="mt-4 border-t border-slate-100 pt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tài sản bàn giao</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @forelse($assets->take(5) as $asset)
                                        <span class="rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700">{{ $asset->name }} × {{ $asset->pivot->quantity }}</span>
                                    @empty
                                        <span class="text-xs text-slate-500">Chưa cập nhật tài sản.</span>
                                    @endforelse
                                    @if($assets->count() > 5)<span class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-600">+{{ $assets->count() - 5 }} tài sản</span>@endif
                                </div>
                            </div>

                            @if($roomImages->isNotEmpty())
                                <details class="mt-4 rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                                    <summary class="cursor-pointer text-sm font-semibold text-slate-700">Ảnh tài sản và hiện trạng phòng</summary>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                        @foreach($roomImages as $image)
                                            <a href="{{ asset('storage/'.$image->path) }}" data-image-modal data-image-title="{{ $image->caption ?: 'Ảnh hiện trạng phòng '.$room?->room_code }}" class="group overflow-hidden rounded-lg border border-slate-200 bg-white">
                                                <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->caption ?: 'Ảnh hiện trạng phòng' }}" loading="lazy" class="h-28 w-full object-cover transition group-hover:scale-[1.02]">
                                                <span class="block p-2 text-xs text-slate-600">
                                                    <strong class="block truncate text-slate-800">{{ $image->caption ?: 'Ảnh hiện trạng' }}</strong>
                                                    {{ $image->taken_at?->format('d/m/Y H:i') ?? 'Chưa rõ thời điểm' }}
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                </details>
                            @endif

                            <div class="mt-5 flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                                <a href="{{ route('client.contracts.show', $contract) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="bx bx-file"></i>Xem hợp đồng</a>
                                <a href="{{ route('client.room.members.index', ['contract' => $contract->id]) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"><i class="bx bx-group"></i>Xem thành viên</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center"><p class="font-semibold text-slate-950">Chưa có phòng đang thuê</p><p class="mt-2 text-sm text-slate-500">Thông tin phòng sẽ xuất hiện khi tài khoản có hợp đồng đang hiệu lực.</p></div>
        @endif
    </div>
@endsection
