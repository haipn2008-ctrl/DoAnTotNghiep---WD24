<div class="space-y-6 p-5 sm:p-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <label class="text-xs font-semibold text-slate-600">Thời điểm trả phòng
            <input type="datetime-local" name="actual_move_out_at" value="{{ old('actual_move_out_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
        </label>
        <label class="text-xs font-semibold text-slate-600">Chỉ số điện cuối
            <input type="number" min="{{ $latestReading?->electricity_new ?? 0 }}" name="checkout_electricity" value="{{ old('checkout_electricity', $latestReading?->electricity_new) }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
        </label>
        <label class="text-xs font-semibold text-slate-600">Chỉ số nước cuối
            <input type="number" min="{{ $latestReading?->water_new ?? 0 }}" name="checkout_water" value="{{ old('checkout_water', $latestReading?->water_new) }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
        </label>
        <label class="text-xs font-semibold text-slate-600">Số chìa khóa đã trả
            <input type="number" min="0" max="100" name="checkout_key_count" value="{{ old('checkout_key_count', 0) }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
        </label>
    </div>

    @if($contract->approvedTerminationRequest && $contract->scheduled_move_out_at)
        <div class="rounded-xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-900">
            <strong>Lịch đã duyệt:</strong> {{ $contract->scheduled_move_out_at->format('H:i d/m/Y') }} · {{ $contract->approvedTerminationRequest->type_label }}
        </div>
    @endif

    @if($contract->handoverItems->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3"><h4 class="text-sm font-bold text-slate-900">Đối chiếu tài sản bàn giao</h4><p class="mt-0.5 text-xs text-slate-500">Ghi nhận tình trạng của tất cả tài sản trước khi tiếp tục.</p></div>
            <div class="divide-y divide-slate-100">
                @foreach($contract->handoverItems as $item)
                    <div class="grid gap-3 px-4 py-3 sm:grid-cols-[1fr_180px_1fr] sm:items-center">
                        <div><p class="text-sm font-semibold text-slate-800">{{ $item->name }}</p><p class="text-xs text-slate-500">Số lượng: {{ $item->quantity }}</p></div>
                        <select name="asset_conditions[{{ $item->id }}][condition]" required class="h-10 rounded-lg border border-slate-200 px-2 text-sm">
                            <option value="good" @selected(old("asset_conditions.{$item->id}.condition") === 'good')>Tốt</option>
                            <option value="worn" @selected(old("asset_conditions.{$item->id}.condition") === 'worn')>Hao mòn</option>
                            <option value="damaged" @selected(old("asset_conditions.{$item->id}.condition") === 'damaged')>Hư hỏng</option>
                            <option value="missing" @selected(old("asset_conditions.{$item->id}.condition") === 'missing')>Thất lạc</option>
                        </select>
                        <input name="asset_conditions[{{ $item->id }}][note]" value="{{ old("asset_conditions.{$item->id}.note") }}" maxlength="500" placeholder="Ghi chú tình trạng" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <label class="text-xs font-semibold text-slate-600">Lý do trả phòng
            <textarea name="checkout_reason" rows="3" required placeholder="Ví dụ: Kết thúc hợp đồng đúng hạn" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-normal">{{ old('checkout_reason', $contract->approvedTerminationRequest?->type_label) }}</textarea>
        </label>
        <label class="text-xs font-semibold text-slate-600">Hư hỏng hoặc thất lạc
            <textarea name="checkout_damage_note" rows="3" placeholder="Chỉ nhập khi có hư hỏng/thất lạc" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-normal">{{ old('checkout_damage_note') }}</textarea>
        </label>
        <label class="text-xs font-semibold text-slate-600">Khoản bồi thường/điều chỉnh
            <input type="number" min="0" name="settlement_amount" value="{{ old('settlement_amount') }}" placeholder="0" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
        </label>
        <label class="text-xs font-semibold text-slate-600">Nội dung khoản điều chỉnh
            <input name="settlement_description" value="{{ old('settlement_description') }}" placeholder="Bắt buộc nếu có khoản điều chỉnh" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
        </label>
    </div>

    <label class="block text-xs font-semibold text-slate-600">Ảnh hiện trạng (tối đa 10 ảnh)
        <input type="file" name="checkout_photos[]" multiple accept="image/jpeg,image/png,image/webp" class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-normal">
    </label>

    <label class="flex items-start gap-3 rounded-xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-950">
        <input type="checkbox" name="handover_confirmed" value="1" required class="mt-0.5 rounded border-violet-300 text-violet-600">
        <span><strong>Xác nhận biên bản:</strong> Ban quản lý và người thuê đại diện đã cùng đối chiếu chỉ số, chìa khóa, tài sản và thống nhất nội dung trên.</span>
    </label>
</div>
