@php
    $selectedRoomId = (string) ($selectedRoomId ?? old('room_id'));
    $conditionLabels = ['normal' => 'Sử dụng bình thường', 'damaged' => 'Có hư hỏng'];
@endphp

<div data-contract-services class="space-y-4 md:col-span-2">
    <section class="overflow-hidden rounded-lg border border-slate-200">
        <div class="border-b border-slate-200 px-4 py-3">
            <h4 class="font-semibold text-slate-950">Tài sản bàn giao của phòng</h4>
        </div>
        <div class="p-4">
            <p data-room-inventory-prompt class="text-sm text-slate-500 {{ $selectedRoomId !== '' ? 'hidden' : '' }}">Chọn phòng</p>
            @foreach($rooms as $room)
                <div data-room-inventory="{{ $room->id }}" class="{{ $selectedRoomId === (string) $room->id ? '' : 'hidden' }}">
                    @forelse($room->amenities as $asset)
                        <div class="flex flex-col gap-2 border-b border-slate-100 py-3 first:pt-0 last:border-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">@if($asset->pivot->image_path)<a href="{{ route('admin.rooms.assets.image', [$room, $asset]) }}" data-image-modal data-image-title="{{ $asset->name }}"><img src="{{ route('admin.rooms.assets.image', [$room, $asset]) }}" alt="{{ $asset->name }}" class="h-12 w-16 rounded-lg object-cover ring-1 ring-slate-200"></a>@endif<div><p class="text-sm font-semibold text-slate-900">{{ $asset->name }} × {{ $asset->pivot->quantity }}</p>@if($asset->pivot->note)<p class="mt-1 text-xs text-slate-500">{{ $asset->pivot->note }}</p>@endif</div></div>
                            <span class="text-xs font-semibold {{ $asset->pivot->condition === 'damaged' ? 'text-rose-700' : 'text-emerald-700' }}">{{ $conditionLabels[$asset->pivot->condition] ?? 'Chưa xác định' }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-amber-700">Phòng này chưa được khai báo tài sản bàn giao.</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </section>
</div>
