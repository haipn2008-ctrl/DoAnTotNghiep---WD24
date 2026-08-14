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

        <div class="hidden grid-cols-[minmax(220px,1fr)_120px_220px] gap-3 bg-slate-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 md:grid">
            <span>Tài sản</span><span>Số lượng</span><span>Tình trạng</span>
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
                @endphp
                <div class="grid gap-3 px-4 py-3 md:grid-cols-[minmax(220px,1fr)_120px_220px] md:items-center">
                    <label class="flex cursor-pointer items-start gap-3 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="inventory[{{ $asset->id }}][selected]" value="1" @checked($selected) data-inventory-checkbox class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>{{ $asset->name }}</span>
                    </label>
                    <div>
                        <span class="mb-1 block text-xs font-medium text-slate-500 md:hidden">Số lượng</span>
                        <input type="number" min="1" max="100" name="inventory[{{ $asset->id }}][quantity]" value="{{ $quantity }}" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    </div>
                    <div>
                        <span class="mb-1 block text-xs font-medium text-slate-500 md:hidden">Tình trạng</span>
                        <select name="inventory[{{ $asset->id }}][condition]" class="h-10 w-full rounded-lg border border-slate-200 px-2 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                            @foreach ($conditionLabels as $value => $label)
                                <option value="{{ $value }}" @selected($condition === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
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
