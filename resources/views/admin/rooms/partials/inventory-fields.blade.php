@php
    $oldInventory = old('inventory');
    $existingInventory = isset($room)
        ? $room->amenities->keyBy('id')
        : collect();
    $conditionLabels = ['normal' => 'Sử dụng bình thường', 'damaged' => 'Có hư hỏng'];
@endphp

<div data-inventory-selector class="overflow-hidden rounded-lg border border-slate-200">
    @if ($amenities->isNotEmpty())
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">
            <p data-inventory-selection-count class="text-xs font-medium text-slate-500">0/{{ $amenities->count() }} mục đã chọn</p>
            <button type="button" data-inventory-toggle-all class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                <i class="bx bx-check-square text-base"></i>
                <span>Chọn tất cả</span>
            </button>
        </div>
    @endif
    <div class="hidden grid-cols-[minmax(180px,1fr)_120px_220px] gap-3 bg-slate-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 md:grid">
        <span>Tiện ích / tài sản</span><span>Số lượng</span><span>Tình trạng</span>
    </div>
    <div class="divide-y divide-slate-200">
        @forelse ($amenities as $amenity)
            @php
                $existing = $existingInventory->get($amenity->id);
                $oldItem = is_array($oldInventory) ? ($oldInventory[$amenity->id] ?? []) : null;
                $selected = is_array($oldInventory)
                    ? (bool) ($oldItem['selected'] ?? false)
                    : (bool) $existing;
                $quantity = $oldItem['quantity'] ?? $existing?->pivot?->quantity ?? 1;
                $condition = $oldItem['condition'] ?? match ($existing?->pivot?->condition) {
                    'damaged' => 'damaged',
                    default => 'normal',
                };
            @endphp
            <div class="grid gap-3 px-4 py-3 md:grid-cols-[minmax(180px,1fr)_120px_220px] md:items-center">
                <label class="flex cursor-pointer items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="inventory[{{ $amenity->id }}][selected]" value="1" @checked($selected) data-inventory-checkbox class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    {{ $amenity->name }}
                </label>
                <div>
                    <span class="mb-1 block text-xs font-medium text-slate-500 md:hidden">Số lượng</span>
                    @if ($amenity->is_quantifiable)
                        <input type="number" min="1" max="100" name="inventory[{{ $amenity->id }}][quantity]" value="{{ $quantity }}" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm">
                    @else
                        <input type="hidden" name="inventory[{{ $amenity->id }}][quantity]" value="1">
                        <span class="text-sm text-slate-400">Không áp dụng</span>
                    @endif
                </div>
                <div>
                    <span class="mb-1 block text-xs font-medium text-slate-500 md:hidden">Tình trạng</span>
                    <select name="inventory[{{ $amenity->id }}][condition]" class="h-10 w-full rounded-lg border border-slate-200 px-2 text-sm">
                        @foreach ($conditionLabels as $value => $label)
                            <option value="{{ $value }}" @selected($condition === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @empty
            <p class="px-4 py-6 text-center text-sm text-slate-500">Chưa có danh mục tiện ích hoặc tài sản.</p>
        @endforelse
    </div>
</div>
@error('inventory') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
@foreach ($errors->get('inventory.*') as $messages)
    @foreach ($messages as $message)<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@endforeach
@endforeach
