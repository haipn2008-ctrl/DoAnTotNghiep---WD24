<div class="space-y-6 p-5 sm:p-6" data-checkout-fields data-deposit-amount="{{ (float) $contract->deposit_amount }}" data-outstanding-amount="{{ (float) $totalOutstanding }}">
    <div class="grid gap-4 sm:grid-cols-3">
        <label class="text-xs font-semibold text-slate-600">Thời điểm trả phòng
            <input type="datetime-local" name="actual_move_out_at" value="{{ old('actual_move_out_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required data-actual-move-out class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
        </label>
        <label class="text-xs font-semibold text-slate-600">Chỉ số điện cuối
            <input type="number" min="{{ $latestReading?->electricity_new ?? 0 }}" name="checkout_electricity" value="{{ old('checkout_electricity', $latestReading?->electricity_new) }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
        </label>
        <label class="text-xs font-semibold text-slate-600">Chỉ số nước cuối
            <input type="number" min="{{ $latestReading?->water_new ?? 0 }}" name="checkout_water" value="{{ old('checkout_water', $latestReading?->water_new) }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
        </label>
    </div>

    @if($contract->approvedTerminationRequest && $contract->scheduled_move_out_at)
        <div class="rounded-xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-900" data-approved-departure-date="{{ $contract->approvedTerminationRequest->approved_end_date?->toDateString() ?? $contract->scheduled_move_out_at->toDateString() }}">
            <strong>Ngày bàn giao đã duyệt:</strong> {{ ($contract->approvedTerminationRequest->approved_end_date ?? $contract->scheduled_move_out_at)->format('d/m/Y') }} · Giờ hành chính 08:00–17:00 · {{ $contract->approvedTerminationRequest->type_label }}
            <label class="mt-3 hidden text-xs font-semibold text-violet-900" data-schedule-variance-field>Lý do thay đổi sang ngày khác
                <textarea name="schedule_variance_reason" rows="2" maxlength="1000" placeholder="Để trống nếu bàn giao đúng lịch" class="mt-1.5 w-full rounded-lg border border-violet-200 bg-white px-3 py-2 text-sm font-normal">{{ old('schedule_variance_reason') }}</textarea>
            </label>
        </div>
    @endif

    @if($contract->handoverItems->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3"><h4 class="text-sm font-bold text-slate-900">Đối chiếu tài sản bàn giao</h4><p class="mt-0.5 text-xs text-slate-500">Ghi nhận tình trạng của tất cả tài sản trước khi tiếp tục.</p></div>
            <div class="divide-y divide-slate-100">
                @foreach($contract->handoverItems as $item)
                    <div class="grid gap-3 px-4 py-3 sm:grid-cols-[1fr_180px_1fr] sm:items-center">
                        <div><p class="text-sm font-semibold text-slate-800">{{ $item->name }}</p><p class="text-xs text-slate-500">Số lượng: {{ $item->quantity }}</p></div>
                        <select name="asset_conditions[{{ $item->id }}][condition]" required data-asset-condition class="h-10 rounded-lg border border-slate-200 px-2 text-sm">
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

    <label class="block text-xs font-semibold text-slate-600">Lý do trả phòng
        <textarea name="checkout_reason" rows="3" required placeholder="Ví dụ: Kết thúc hợp đồng đúng hạn" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-normal">{{ old('checkout_reason', $contract->approvedTerminationRequest?->type_label) }}</textarea>
    </label>

    <fieldset>
        <legend class="text-sm font-bold text-slate-900">Phòng hoặc tài sản có hư hỏng/thất lạc không?</legend>
        <p class="mt-1 text-xs text-slate-500">Lựa chọn này quyết định việc ghi nhận tiền bồi thường vào bảng quyết toán.</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50 has-[:checked]:ring-2 has-[:checked]:ring-emerald-100">
                <input type="radio" name="has_damage" value="0" required data-damage-choice class="mt-0.5 border-slate-300 text-emerald-600" @checked(old('has_damage') === '0')>
                <span><strong class="block text-sm text-slate-900">Không có hư hỏng</strong><span class="mt-1 block text-xs leading-5 text-slate-500">Phòng và tài sản được bàn giao bình thường; không phát sinh tiền bồi thường.</span></span>
            </label>
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 has-[:checked]:border-rose-400 has-[:checked]:bg-rose-50 has-[:checked]:ring-2 has-[:checked]:ring-rose-100">
                <input type="radio" name="has_damage" value="1" required data-damage-choice class="mt-0.5 border-slate-300 text-rose-600" @checked(old('has_damage') === '1')>
                <span><strong class="block text-sm text-slate-900">Có hư hỏng hoặc thất lạc</strong><span class="mt-1 block text-xs leading-5 text-slate-500">Bắt buộc mô tả, nhập tiền bồi thường và cung cấp ảnh hiện trạng.</span></span>
            </label>
        </div>
        <p class="mt-2 hidden text-xs font-semibold text-rose-700" data-asset-damage-warning>Tài sản đã được đánh dấu hư hỏng/thất lạc nên hệ thống tự chọn “Có”.</p>
    </fieldset>

    <div class="hidden rounded-xl border border-rose-200 bg-rose-50/50 p-4" data-damage-fields>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="text-xs font-semibold text-slate-700 md:col-span-2">Mô tả hư hỏng hoặc thất lạc
                <textarea name="checkout_damage_note" rows="3" maxlength="2000" data-damage-required placeholder="Mô tả vị trí, tình trạng và tài sản liên quan" class="mt-1.5 w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-normal">{{ old('checkout_damage_note') }}</textarea>
            </label>
            <label class="text-xs font-semibold text-slate-700">Tiền người thuê cần bồi thường
                <div class="relative mt-1.5"><input type="number" min="1" step="1" name="settlement_amount" value="{{ old('settlement_amount') }}" data-damage-required data-compensation-amount placeholder="Ví dụ: 500000" class="h-11 w-full rounded-lg border border-rose-200 bg-white px-3 pr-10 text-sm font-normal"><span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-500">đ</span></div>
            </label>
            <label class="text-xs font-semibold text-slate-700">Nội dung khoản bồi thường
                <input name="settlement_description" value="{{ old('settlement_description') }}" maxlength="1000" data-damage-required placeholder="Ví dụ: Bồi thường cửa phòng bị hỏng" class="mt-1.5 h-11 w-full rounded-lg border border-rose-200 bg-white px-3 text-sm font-normal">
            </label>
            <div class="md:col-span-2 rounded-lg border border-rose-200 bg-white p-3">
                <label class="block text-xs font-semibold text-slate-700">Ảnh đồ vật hư hỏng/thất lạc (tối đa 10 ảnh)
                    <input type="file" name="checkout_photos[]" multiple accept="image/jpeg,image/png,image/webp" data-checkout-photos class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-normal">
                </label>
                <p class="mt-1 text-xs text-rose-600">Bắt buộc tải ít nhất một ảnh làm chứng cứ cho khoản bồi thường.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-3 rounded-xl border border-indigo-200 bg-indigo-50 p-4 sm:grid-cols-3">
        <div><p class="text-xs font-medium text-indigo-700">Tiền cọc đã ghi nhận</p><p class="mt-1 text-lg font-bold text-indigo-950" data-preview-deposit>0đ</p></div>
        <div><p class="text-xs font-medium text-indigo-700">Công nợ hiện có + bồi thường</p><p class="mt-1 text-lg font-bold text-indigo-950" data-preview-obligation>0đ</p></div>
        <div><p class="text-xs font-medium text-indigo-700">Tạm tính sau đối trừ</p><p class="mt-1 text-lg font-bold" data-preview-result>0đ</p><p class="mt-1 text-xs font-semibold" data-preview-party></p></div>
        <p class="text-xs leading-5 text-indigo-700 sm:col-span-3">Đây là tạm tính từ công nợ hiện có và tiền bồi thường. Phí điện, nước, tiền phòng lẻ kỳ và dịch vụ cuối kỳ sẽ được hệ thống tính chính xác khi xác nhận bàn giao.</p>
    </div>

    <label class="flex items-start gap-3 rounded-xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-950">
        <input type="checkbox" name="handover_confirmed" value="1" required class="mt-0.5 rounded border-violet-300 text-violet-600">
        <span><strong>Xác nhận biên bản:</strong> Ban quản lý và người thuê đại diện đã cùng đối chiếu chỉ số, tài sản và thống nhất nội dung trên.</span>
    </label>
</div>
