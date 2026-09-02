@extends('layouts.admin.index')

@php($reviewing = isset($roomTransfer))
@section('title', $reviewing ? 'Duyệt đổi phòng' : 'Chuyển phòng')
@section('page_title', $reviewing ? 'Duyệt yêu cầu đổi phòng' : 'Chuyển khách sang phòng khác')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 p-6 text-white shadow-lg shadow-indigo-200/60 sm:p-8">
        <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/10"></div>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="relative flex items-center gap-4">
                <span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 sm:flex"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h12m0 0-3-3m3 3-3 3M17 17H5m0 0 3 3m-3-3 3-3" /></svg></span>
                <div><p class="text-sm text-indigo-100">{{ $contract->contract_code }} · {{ $contract->tenant?->full_name }}</p><h2 class="mt-1 text-2xl font-bold">{{ $reviewing ? 'Duyệt yêu cầu đổi phòng' : 'Chuyển khách sang phòng khác' }}</h2><p class="mt-2 text-sm text-indigo-100">Phòng {{ $contract->room?->room_code }} → phòng mới</p>@if($reviewing)<p class="mt-1 text-sm text-white"><strong>Khách đề nghị:</strong> {{ $roomTransfer->reason }}</p>@endif</div>
            </div>
            <a href="{{ route('admin.room-transfers.index') }}" class="relative inline-flex h-11 items-center gap-2 rounded-xl border border-white/20 bg-white px-4 text-sm font-bold text-indigo-700 shadow-sm hover:bg-indigo-50"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m5-5-5 5 5 5" /></svg>Quay lại</a>
        </div>
        <div class="relative mt-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-white/20 bg-white/10 p-4 text-sm backdrop-blur-sm"><span class="block text-xs font-semibold uppercase text-indigo-100">Công nợ đang giữ nguyên</span><strong class="mt-1 block text-lg">{{ number_format((float)$outstanding, 0, ',', '.') }}đ</strong></div>
            <div class="rounded-xl border border-white/20 bg-white/10 p-4 text-sm backdrop-blur-sm"><span class="block text-xs font-semibold uppercase text-indigo-100">Chỉ số phòng cũ gần nhất</span><strong class="mt-1 block">Điện {{ (int)($lastOldReading?->electricity_new ?? 0) }} · Nước {{ (int)($lastOldReading?->water_new ?? 0) }}</strong></div>
            <div class="rounded-xl border border-white/20 bg-white/10 p-4 text-sm backdrop-blur-sm"><span class="block text-xs font-semibold uppercase text-indigo-100">Ngày chuyển thực tế</span><strong class="mt-1 block">{{ today()->format('d/m/Y') }}</strong></div>
        </div>
    </section>

    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ $reviewing ? route('admin.room-transfers.approve', $roomTransfer) : route('admin.room-transfers.store', $contract) }}" class="space-y-5">@csrf
        <input type="hidden" name="effective_date" value="{{ today()->toDateString() }}">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="font-bold text-slate-950">1. Chọn phòng và ghi lý do</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div><label class="mb-1 block text-sm font-semibold">Phòng mới</label><select id="new-room-id" name="new_room_id" required {{ $reviewing ? 'disabled' : '' }} class="h-11 w-full rounded-lg border border-slate-200 px-3">
                    <option value="">Chọn phòng trống</option>
                    @foreach($rooms as $room)<option value="{{ $room->id }}" @selected((string)old('new_room_id', $selectedRoomId)===(string)$room->id)>{{ $room->room_code }} · {{ number_format((float)$room->price,0,',','.') }}đ/tháng · tối đa {{ $room->max_people }} người</option>@endforeach
                </select>@if($reviewing)<input type="hidden" name="new_room_id" value="{{ $roomTransfer->new_room_id }}">@endif</div>
                <div><label class="mb-1 block text-sm font-semibold">Lý do/quyết định của admin *</label><textarea name="admin_reason" required minlength="3" rows="3" class="w-full rounded-lg border border-slate-200 p-3">{{ old('admin_reason', $reviewing ? 'Đồng ý yêu cầu đổi phòng của khách.' : '') }}</textarea></div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h3 class="font-bold text-slate-950">2. Chốt điện nước phòng cũ và bàn giao phòng mới</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><label class="mb-1 block text-sm font-semibold">Điện phòng cũ</label><input type="number" min="{{ (int)($lastOldReading?->electricity_new ?? 0) }}" name="old_electricity" value="{{ old('old_electricity', (int)($lastOldReading?->electricity_new ?? 0)) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                <div><label class="mb-1 block text-sm font-semibold">Nước phòng cũ</label><input type="number" min="{{ (int)($lastOldReading?->water_new ?? 0) }}" name="old_water" value="{{ old('old_water', (int)($lastOldReading?->water_new ?? 0)) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                <div><label class="mb-1 block text-sm font-semibold">Điện đầu phòng mới</label><input id="new-electricity" type="number" min="0" name="new_electricity" value="{{ old('new_electricity', 0) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                <div><label class="mb-1 block text-sm font-semibold">Nước đầu phòng mới</label><input id="new-water" type="number" min="0" name="new_water" value="{{ old('new_water', 0) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h3 class="font-bold text-slate-950">3. Tài sản trả tại phòng cũ</h3>
                <div class="mt-4 space-y-3">@forelse($contract->handoverItems as $item)
                    <div class="rounded-lg border border-slate-200 p-3"><p class="font-semibold">{{ $item->name }}</p><div class="mt-2 grid grid-cols-3 gap-2"><input type="number" min="0" name="old_assets[{{ $item->amenity_id }}][quantity]" value="{{ old('old_assets.'.$item->amenity_id.'.quantity', $item->quantity) }}" class="h-10 rounded border border-slate-200 px-2"><select name="old_assets[{{ $item->amenity_id }}][condition]" class="h-10 rounded border border-slate-200 px-2"><option value="normal">Bình thường</option><option value="damaged">Hư hỏng</option></select><input name="old_assets[{{ $item->amenity_id }}][note]" placeholder="Ghi chú" class="h-10 rounded border border-slate-200 px-2"></div></div>
                @empty<p class="text-sm text-slate-500">Không có tài sản bàn giao đã lưu.</p>@endforelse</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h3 class="font-bold text-slate-950">4. Tài sản nhận tại phòng mới</h3>
                @foreach($rooms as $room)
                    <div data-new-room-assets="{{ $room->id }}" class="mt-4 hidden space-y-3">@forelse($room->amenities as $asset)
                        <div class="rounded-lg border border-slate-200 p-3"><div class="flex items-center gap-3">@if($asset->pivot->image_path)<a href="{{ route('admin.rooms.assets.image', [$room, $asset]) }}" data-image-modal data-image-title="{{ $asset->name }}"><img src="{{ route('admin.rooms.assets.image', [$room, $asset]) }}" alt="{{ $asset->name }}" class="h-12 w-16 rounded-lg object-cover ring-1 ring-slate-200"></a>@endif<p class="font-semibold">{{ $asset->name }}</p></div><div class="mt-2 grid grid-cols-3 gap-2"><input type="number" min="0" name="new_assets[{{ $room->id }}][{{ $asset->id }}][quantity]" value="{{ $asset->pivot->quantity }}" class="h-10 rounded border border-slate-200 px-2"><select name="new_assets[{{ $room->id }}][{{ $asset->id }}][condition]" class="h-10 rounded border border-slate-200 px-2"><option value="normal">Bình thường</option><option value="damaged">Hư hỏng</option></select><input name="new_assets[{{ $room->id }}][{{ $asset->id }}][note]" value="{{ $asset->pivot->note }}" placeholder="Ghi chú" class="h-10 rounded border border-slate-200 px-2"></div></div>
                    @empty<p class="text-sm text-slate-500">Phòng này không có tài sản bàn giao.</p>@endforelse</div>
                @endforeach
                <p id="asset-placeholder" class="mt-4 text-sm text-slate-500">Chọn phòng mới</p>
            </div>
        </section>

        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:p-6">
            <label class="flex items-start gap-3"><input type="checkbox" name="confirm_transfer" value="1" required class="mt-1"><span class="text-sm font-semibold text-amber-950">Tôi đã đối chiếu công nợ, chỉ số và tài sản. Tôi hiểu thao tác sẽ tạo chứng từ phòng cũ, cập nhật tiền thuê/cọc theo phòng mới và gửi thông báo cho khách.</span></label>
            <button class="mt-5 inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 text-sm font-bold text-white shadow-sm hover:bg-indigo-800"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>{{ $reviewing ? 'Duyệt và chuyển phòng' : 'Xác nhận chuyển phòng' }}</button>
        </section>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selector = document.getElementById('new-room-id');
    const meters = @json($roomMeters);
    const electricity = document.getElementById('new-electricity');
    const water = document.getElementById('new-water');
    const refresh = () => {
        const id = selector.value;
        document.querySelectorAll('[data-new-room-assets]').forEach(el => el.classList.toggle('hidden', el.dataset.newRoomAssets !== id));
        document.getElementById('asset-placeholder').classList.toggle('hidden', !!id);
        if (meters[id]) { electricity.value = meters[id].electricity; water.value = meters[id].water; electricity.min = meters[id].electricity; water.min = meters[id].water; }
    };
    selector.addEventListener('change', refresh); refresh();
});
</script>
@endsection
