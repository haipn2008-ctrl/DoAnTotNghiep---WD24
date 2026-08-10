@extends('layouts.client.index')

@section('title', 'Điện nước của tôi | Cổng khách thuê')
@section('page_title', 'Điện nước của tôi')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Lịch sử sử dụng theo kỳ</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Điện nước của tôi</h2>
                <p class="mt-2 text-sm text-slate-500">Theo dõi lượng tiêu thụ, chi phí và ảnh đồng hồ do ban quản lý ghi nhận.</p>
            </div>
            <form method="GET" action="{{ route('client.utilities.index') }}" class="flex items-end gap-2">
                <div><label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Năm</label><select name="year" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm"><option value="">Tất cả</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>@endforeach</select></div>
                <button class="h-10 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white">Lọc</button>
                @if(request('year'))<a href="{{ route('client.utilities.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600">Xóa lọc</a>@endif
            </form>
        </div>

        <div class="rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900">
            Chi phí bên dưới được lấy từ hóa đơn đã phát hành của đúng kỳ. Nếu chưa có hóa đơn, hệ thống chỉ hiển thị lượng điện nước đã dùng.
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            @forelse($readings as $reading)
                @php
                    $electricityDetail = $reading->invoice?->details->firstWhere('type', 'electricity');
                    $waterDetail = $reading->invoice?->details->firstWhere('type', 'water');
                @endphp
                <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                        <div><h3 class="font-bold text-slate-950">Tháng {{ $reading->month }}/{{ $reading->year }}</h3><p class="mt-1 text-xs text-slate-500">Phòng {{ $reading->room->room_code ?? '-' }}{{ $reading->record_date ? ' · Ghi ngày '.$reading->record_date->format('d/m/Y') : '' }}</p></div>
                        @if($reading->invoice)<a href="{{ route('client.invoices.show', $reading->invoice) }}" class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Xem hóa đơn</a>@else<span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Chưa có hóa đơn</span>@endif
                    </div>

                    <div class="grid divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                        <div class="p-5">
                            <div class="flex items-center justify-between"><p class="font-semibold text-indigo-700">Điện</p>@if($reading->meterImageExists('electricity'))<a href="{{ route('client.utilities.image', [$reading, 'electricity']) }}" target="_blank" class="text-xs font-semibold text-indigo-600">Xem ảnh đồng hồ</a>@elseif($reading->electricity_image)<span class="text-xs font-medium text-amber-700">Ảnh không còn tồn tại</span>@endif</div>
                            <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($reading->electricity_usage, 0, ',', '.') }} <span class="text-sm font-medium text-slate-500">kWh</span></p>
                            @if($electricityDetail)
                                <div class="mt-3 space-y-1 text-sm"><div class="flex justify-between text-slate-500"><span>Đơn giá</span><span>{{ number_format($electricityDetail->unit_price, 0, ',', '.') }}đ/kWh</span></div><div class="flex justify-between font-bold text-slate-950"><span>Tiền điện</span><span>{{ number_format($electricityDetail->amount, 0, ',', '.') }}đ</span></div></div>
                            @else<p class="mt-3 text-sm text-slate-400">Chi phí sẽ có khi phát hành hóa đơn.</p>@endif
                        </div>
                        <div class="p-5">
                            <div class="flex items-center justify-between"><p class="font-semibold text-sky-700">Nước</p>@if($reading->meterImageExists('water'))<a href="{{ route('client.utilities.image', [$reading, 'water']) }}" target="_blank" class="text-xs font-semibold text-sky-600">Xem ảnh đồng hồ</a>@elseif($reading->water_image)<span class="text-xs font-medium text-amber-700">Ảnh không còn tồn tại</span>@endif</div>
                            <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($reading->water_usage, 0, ',', '.') }} <span class="text-sm font-medium text-slate-500">m³</span></p>
                            @if($waterDetail)
                                <div class="mt-3 space-y-1 text-sm"><div class="flex justify-between text-slate-500"><span>Đơn giá</span><span>{{ number_format($waterDetail->unit_price, 0, ',', '.') }}đ/m³</span></div><div class="flex justify-between font-bold text-slate-950"><span>Tiền nước</span><span>{{ number_format($waterDetail->amount, 0, ',', '.') }}đ</span></div></div>
                            @else<p class="mt-3 text-sm text-slate-400">Chi phí sẽ có khi phát hành hóa đơn.</p>@endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center lg:col-span-2"><p class="font-semibold text-slate-950">Chưa có lịch sử điện nước</p><p class="mt-2 text-sm text-slate-500">Dữ liệu sẽ xuất hiện sau khi ban quản lý ghi chỉ số cho phòng của bạn.</p></div>
            @endforelse
        </div>

        @if($readings->hasPages())<div>{{ $readings->links() }}</div>@endif
    </div>
@endsection
