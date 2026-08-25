@php
    $feeFields = [
        'electric_price' => ['Đơn giá điện', 'VNĐ/kWh'],
        'water_price' => ['Đơn giá nước', 'VNĐ/m³'],
        'internet_fee' => ['Phí Internet', 'VNĐ/tháng'],
        'service_fee' => ['Phí dịch vụ chung', 'VNĐ/tháng'],
    ];
@endphp
<div class="grid gap-4 md:grid-cols-2">
    @foreach($feeFields as $field => [$label, $unit])
        <div>
            <label for="{{ $field }}" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ $label }} <span class="font-normal text-slate-400">({{ $unit }})</span></label>
            <input id="{{ $field }}" type="number" step="0.01" min="0" name="{{ $field }}" value="{{ old($field, $setting->{$field}) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error($field)<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    @endforeach
</div>

<div class="border-t border-slate-200 pt-6">
    <label for="fee_effective_from" class="mb-1.5 block text-sm font-semibold text-slate-700">Áp dụng cho chi phí phát sinh từ tháng</label>
    <input id="fee_effective_from" type="month" name="fee_effective_from" value="{{ old('fee_effective_from', now()->format('Y-m')) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 md:max-w-sm">
    <p class="mt-1.5 text-sm text-slate-500">Hóa đơn phát hành tháng sau sẽ lấy bảng giá theo tháng sử dụng dịch vụ, không theo ngày tạo hóa đơn.</p>
    @error('fee_effective_from')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
</div>

<section class="border-t border-slate-200 pt-6">
    <h4 class="font-semibold text-slate-900">Lịch thu tiền hằng tháng</h4>
    <p class="mt-1 text-sm text-slate-500">Hóa đơn thu các chi phí của tháng trước. Nếu tháng không có ngày đã chọn, hệ thống dùng ngày cuối tháng.</p>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div>
            <label for="invoice_day" class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày phát hành hóa đơn <span class="font-normal text-slate-400">(1–31)</span></label>
            <input id="invoice_day" type="number" min="1" max="31" name="invoice_day" value="{{ old('invoice_day', $setting->invoice_day) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('invoice_day')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="payment_due_days" class="mb-1.5 block text-sm font-semibold text-slate-700">Số ngày được thanh toán <span class="font-normal text-slate-400">(1–90 ngày)</span></label>
            <input id="payment_due_days" type="number" min="1" max="90" name="payment_due_days" value="{{ old('payment_due_days', $setting->payment_due_days) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
            @error('payment_due_days')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

@if($feeSchedules->isNotEmpty())
    <section class="border-t border-slate-200 pt-6">
        <h4 class="font-semibold text-slate-900">Lịch sử bảng giá</h4>
        <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-3 py-2.5 font-semibold">Từ tháng</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Điện</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Nước</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Internet</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Dịch vụ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($feeSchedules as $schedule)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2.5 font-semibold text-slate-900">{{ $schedule->effective_from->format('m/Y') }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-right">{{ number_format($schedule->electric_price, 0, ',', '.') }}đ</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-right">{{ number_format($schedule->water_price, 0, ',', '.') }}đ</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-right">{{ number_format($schedule->internet_fee, 0, ',', '.') }}đ</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-right">{{ number_format($schedule->service_fee, 0, ',', '.') }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
