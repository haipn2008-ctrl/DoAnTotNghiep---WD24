@php
    $oldInventory = old('inventory');
    $existingInventory = isset($room) ? $room->amenities->keyBy('id') : collect();
    $assets = $amenities->where('category', \App\Models\Amenity::CATEGORY_ASSET);
    $conditionLabels = ['normal' => 'Sử dụng bình thường', 'damaged' => 'Có hư hỏng'];
@endphp

<div class="space-y-4">
    <section data-inventory-selector class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-200 bg-slate-50/70 px-4 py-3 sm:flex-row sm:items-center">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700"><i class="bx bx-package text-lg"></i></span>
                <div>
                    <h4 class="text-sm font-semibold text-slate-950">Tài sản trong phòng</h4>
                    <p data-inventory-selection-count class="mt-1 text-xs font-semibold text-indigo-600">0/{{ $assets->count() }} tài sản đã chọn</p>
                </div>
            </div>
            @if ($assets->isNotEmpty())
                <button type="button" data-inventory-toggle-all class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                    <i class="bx bx-check-square text-base"></i><span>Chọn tất cả tài sản</span>
                </button>
            @endif
        </div>

        <div class="hidden grid-cols-12 gap-2 bg-slate-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 md:grid">
            <span class="col-span-2">Tài sản</span>
            <span class="col-span-1">SL</span>
            <span class="col-span-2">Tình trạng</span>
            <span class="col-span-3">Ghi chú</span>
            <span class="col-span-4">Ảnh tài sản</span>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse ($assets as $asset)
                @php
                    $existing = $existingInventory->get($asset->id);
                    $oldItem = is_array($oldInventory) ? ($oldInventory[$asset->id] ?? []) : null;
                    $selected = is_array($oldInventory)
                        ? (bool) ($oldItem['selected'] ?? false)
                        : (isset($room) ? (bool) $existing : true);
                    $quantity = $oldItem['quantity'] ?? $existing?->pivot?->quantity ?? 1;
                    $condition = $oldItem['condition'] ?? ($existing?->pivot?->condition === 'damaged' ? 'damaged' : 'normal');
                    $note = $oldItem['note'] ?? $existing?->pivot?->note;
                @endphp
                <div class="grid grid-cols-1 gap-2 px-4 py-2.5 md:grid-cols-12 md:items-center">
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700 md:col-span-2">
                        <input type="checkbox" name="inventory[{{ $asset->id }}][selected]" value="1" @checked($selected) data-inventory-checkbox class="h-4 w-4 shrink-0 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="truncate" title="{{ $asset->name }}">{{ $asset->name }}</span>
                    </label>
                    <div class="md:col-span-1">
                        <span class="mb-1 block text-xs font-medium text-slate-500 md:hidden">Số lượng</span>
                        <input type="number" min="1" max="100" name="inventory[{{ $asset->id }}][quantity]" value="{{ $quantity }}" aria-label="Số lượng {{ $asset->name }}" class="h-9 w-full rounded-lg border border-slate-200 px-2 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    </div>
                    <div class="md:col-span-2">
                        <span class="mb-1 block text-xs font-medium text-slate-500 md:hidden">Tình trạng</span>
                        <select name="inventory[{{ $asset->id }}][condition]" aria-label="Tình trạng {{ $asset->name }}" class="h-9 w-full rounded-lg border border-slate-200 px-2 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                            @foreach ($conditionLabels as $value => $label)
                                <option value="{{ $value }}" @selected($condition === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <span class="mb-1 block text-xs font-medium text-slate-500 md:hidden">Ghi chú</span>
                        <input name="inventory[{{ $asset->id }}][note]" value="{{ $note }}" maxlength="500" placeholder="Ghi chú nếu có" aria-label="Ghi chú {{ $asset->name }}" class="h-9 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        @error("inventory.{$asset->id}.note")<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-4">
                        <span class="mb-1 block text-xs font-medium text-slate-500 md:hidden">Ảnh tài sản</span>
                        <div class="flex items-center gap-2">
                            @if($existing?->pivot?->image_path)
                                <a href="{{ route('admin.rooms.assets.image', [$room, $asset]) }}" data-image-modal data-image-title="{{ $asset->name }} · Phòng {{ $room->room_code }}" class="shrink-0">
                                    <img src="{{ route('admin.rooms.assets.image', [$room, $asset]) }}" alt="{{ $asset->name }}" class="h-10 w-14 rounded-lg object-cover ring-1 ring-slate-200">
                                </a>
                            @endif
                            <input type="file" name="inventory[{{ $asset->id }}][image]" accept="image/jpeg,image/png,image/webp" aria-label="Ảnh {{ $asset->name }}" class="min-w-0 flex-1 text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-indigo-50 file:px-2 file:py-2 file:font-semibold file:text-indigo-700">
                        </div>
                        @error("inventory.{$asset->id}.image")<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-sm text-slate-500">Chưa có danh mục tài sản.</p>
            @endforelse
        </div>
    </section>
</div>

@error('inventory') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
@foreach ($errors->get('inventory.*') as $messages)
    @foreach ($messages as $message)<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@endforeach
@endforeach
