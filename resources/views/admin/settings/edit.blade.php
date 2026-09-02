@extends('layouts.admin.index')

@section('title', $typeData['label'].' | Quản lý phòng trọ')
@section('page_title', $typeData['label'])

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-slate-500">Hệ thống và cài đặt</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">{{ $typeData['label'] }}</h2>
            <p class="mt-2 text-sm text-slate-500">{{ $typeData['description'] }}</p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Vui lòng kiểm tra lại thông tin.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
            <section class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                @if ($type === 'fees')
                    <p class="text-sm font-medium text-slate-500">Mức phí đang áp dụng</p>
                    <dl class="mt-3 divide-y divide-slate-100 text-sm">
                        @foreach([
                            'Điện (VNĐ/kWh)' => $setting->electric_price,
                            'Nước (VNĐ/m³)' => $setting->water_price,
                            'Internet/người/tháng' => $setting->internet_fee,
                            'Dịch vụ chung/người/tháng' => $setting->service_fee,
                        ] as $label => $value)
                            <div class="flex items-center justify-between gap-3 py-2.5"><dt class="text-slate-500">{{ $label }}</dt><dd class="font-semibold text-slate-900">{{ number_format($value, 0, ',', '.') }}đ</dd></div>
                        @endforeach
                    </dl>
                    <div class="mt-4 border-t border-slate-100 pt-4 text-sm text-slate-600">
                        <p>Phát hành vào ngày <strong class="text-slate-900">{{ $setting->invoice_day }}</strong> hằng tháng</p>
                        <p class="mt-1">Hạn thanh toán sau <strong class="text-slate-900">{{ $setting->payment_due_days }} ngày</strong></p>
                    </div>
                @elseif ($type === 'property-payment')
                    <p class="text-sm font-medium text-slate-500">Thông tin đang sử dụng</p>
                    <p class="mt-4 text-xl font-bold text-slate-950">{{ $setting->property_name ?: 'Chưa cấu hình nhà trọ' }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $setting->landlord_name ?: 'Chưa có thông tin chủ nhà' }}</p>
                    <div class="my-4 border-t border-slate-100"></div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tài khoản nhận tiền</p>
                    <p class="mt-2 font-bold text-slate-900">{{ $setting->bank_account_no ?: 'Chưa cấu hình' }}</p>
                    <p class="text-sm text-slate-500">{{ $setting->bank_account_name }} {{ $setting->bank_id ? '· '.$setting->bank_id : '' }}</p>
                @elseif ($type === 'property')
                    <p class="text-sm font-medium text-slate-500">Tài sản hiện tại</p>
                    <p class="mt-4 text-xl font-bold text-slate-950">{{ $setting->property_name ?: 'Chưa cấu hình' }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $setting->property_address }}</p>
                @elseif ($type === 'bank')
                    <p class="text-sm font-medium text-slate-500">Tài khoản hiện tại</p>
                    <p class="mt-4 text-xl font-bold text-slate-950">{{ $setting->bank_account_no ?: 'Chưa cấu hình' }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $setting->bank_account_name }} {{ $setting->bank_id ? '· '.$setting->bank_id : '' }}</p>
                @else
                    <p class="text-sm font-medium text-slate-500">Giá hiện tại</p>
                    <p class="mt-4 text-3xl font-bold text-slate-950">{{ number_format($currentValue, 0, ',', '.') }}đ</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $typeData['unit'] }}</p>
                @endif
                <p class="mt-5 rounded-lg bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">Thay đổi chỉ áp dụng cho hóa đơn và hợp đồng phát hành sau thời điểm lưu; snapshot hợp đồng cũ không bị thay đổi.</p>
            </section>

            <form action="{{ route('admin.settings.update', ['type' => $type]) }}" method="POST" class="rounded-lg border border-slate-200 bg-white shadow-sm">
                @csrf
                @method('PUT')
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Thông tin cập nhật</h3>
                    <p class="text-sm text-slate-500">Các trường trong cùng biểu mẫu được kiểm tra và lưu cùng lúc.</p>
                </div>
                <div class="space-y-7 p-5">
                    @if ($type === 'fees')
                        @include('admin.settings.partials.fee-fields')
                    @elseif ($type === 'property-payment')
                        <section><h4 class="mb-4 font-semibold text-slate-900">Thông tin tài sản và chủ nhà</h4>@include('admin.settings.partials.property-fields')</section>
                        <section class="border-t border-slate-200 pt-6"><h4 class="mb-4 font-semibold text-slate-900">Tài khoản nhận thanh toán và VietQR</h4>@include('admin.settings.partials.bank-fields')</section>
                    @elseif ($type === 'property')
                        @include('admin.settings.partials.property-fields')
                    @elseif ($type === 'bank')
                        @include('admin.settings.partials.bank-fields')
                    @else
                        <label for="{{ $typeData['field'] }}" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ $typeData['label'] }} ({{ $typeData['unit'] }})</label>
                        <input id="{{ $typeData['field'] }}" type="number" step="0.01" min="0" name="{{ $typeData['field'] }}" value="{{ old($typeData['field'], $currentValue) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @endif
                </div>
                <div class="flex justify-end border-t border-slate-200 px-5 py-4">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"><i class="bx bx-save text-lg"></i>Lưu cấu hình</button>
                </div>
            </form>
        </div>
    </div>
@endsection
