@php
    $feeFields = [
        'electric_price' => ['Đơn giá điện', 'VNĐ/kWh'],
        'water_price' => ['Đơn giá nước', 'VNĐ/m³'],
        'internet_fee' => ['Phí Internet', 'VNĐ/tháng'],
        'service_fee' => ['Phí dịch vụ chung', 'VNĐ/tháng'],
        'motorcycle_parking_fee' => ['Phí trông xe máy', 'VNĐ/xe/tháng'],
        'car_parking_fee' => ['Phí trông ô tô', 'VNĐ/xe/tháng'],
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
