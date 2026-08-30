@extends('layouts.admin.index')

@php($reviewing = isset($roomTransfer))
@section('title', $reviewing ? 'Duyệt đổi phòng' : 'Chuyển phòng')
@section('page_title', $reviewing ? 'Duyệt yêu cầu đổi phòng' : 'Chuyển khách sang phòng khác')

@section('content')
<div class="mx-auto max-w-6xl space-y-5">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">{{ $contract->contract_code }} · {{ $contract->tenant?->full_name }}</p>
                <h2 class="mt-1 text-xl font-bold text-slate-950">Phòng {{ $contract->room?->room_code }} → phòng mới</h2>
                @if($reviewing)<p class="mt-2 text-sm text-slate-700"><strong>Khách đề nghị:</strong> {{ $roomTransfer->reason }}</p>@endif
            </div>
            <a href="{{ route('admin.room-transfers.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700">Quay lại</a>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg bg-rose-50 p-3 text-sm text-rose-900"><span class="block text-xs font-semibold uppercase text-rose-600">Công nợ đang giữ nguyên</span><strong>{{ number_format((float)$outstanding, 0, ',', '.') }}đ</strong></div>
            <div class="rounded-lg bg-slate-50 p-3 text-sm"><span class="block text-xs font-semibold uppercase text-slate-500">Chỉ số phòng cũ gần nhất</span>Điện {{ (int)($lastOldReading?->electricity_new ?? 0) }} · Nước {{ (int)($lastOldReading?->water_new ?? 0) }}</div>
            <div class="rounded-lg bg-indigo-50 p-3 text-sm text-indigo-900"><span class="block text-xs font-semibold uppercase text-indigo-600">Ngày chuyển thực tế</span>{{ today()->format('d/m/Y') }}</div>
        </div>
    </div>

    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ $reviewing ? route('admin.room-transfers.approve', $roomTransfer) : route('admin.room-transfers.store', $contract) }}" class="space-y-5">@csrf
        <input type="hidden" name="effective_date" value="{{ today()->toDateString() }}">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-bold text-slate-950">1. Chọn phòng và ghi lý do</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div><label class="mb-1 block text-sm font-semibold">Phòng mới</label><select id="new-room-id" name="new_room_id" required {{ $reviewing ? 'disabled' : '' }} class="h-11 w-full rounded-lg border border-slate-200 px-3">
                    <option value="">Chọn phòng trống</option>
                    @foreach($rooms as $room)<option value="{{ $room->id }}" @selected((string)old('new_room_id', $selectedRoomId)===(string)$room->id)>{{ $room->room_code }} · {{ number_format((float)$room->price,0,',','.') }}đ/tháng · tối đa {{ $room->max_people }} người</option>@endforeach
                </select>@if($reviewing)<input type="hidden" name="new_room_id" value="{{ $roomTransfer->new_room_id }}">@endif</div>
                <div><label class="mb-1 block text-sm font-semibold">Lý do/quyết định của admin *</label><textarea name="admin_reason" required minlength="3" rows="3" class="w-full rounded-lg border border-slate-200 p-3">{{ old('admin_reason', $reviewing ? 'Đồng ý yêu cầu đổi phòng của khách.' : '') }}</textarea></div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-bold text-slate-950">2. Chốt điện nước phòng cũ và bàn giao phòng mới</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><label class="mb-1 block text-sm font-semibold">Điện phòng cũ</label><input type="number" min="{{ (int)($lastOldReading?->electricity_new ?? 0) }}" name="old_electricity" value="{{ old('old_electricity', (int)($lastOldReading?->electricity_new ?? 0)) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                <div><label class="mb-1 block text-sm font-semibold">Nước phòng cũ</label><input type="number" min="{{ (int)($lastOldReading?->water_new ?? 0) }}" name="old_water" value="{{ old('old_water', (int)($lastOldReading?->water_new ?? 0)) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                <div><label class="mb-1 block text-sm font-semibold">Điện đầu phòng mới</label><input id="new-electricity" type="number" min="0" name="new_electricity" value="{{ old('new_electricity', 0) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
                <div><label class="mb-1 block text-sm font-semibold">Nước đầu phòng mới</label><input id="new-water" type="number" min="0" name="new_water" value="{{ old('new_water', 0) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3"></div>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-bold text-slate-950">3. Tài sản trả tại phòng cũ</h3>
                <div class="mt-4 space-y-3">@forelse($contract->handoverItems as $item)
                    <div class="rounded-lg border border-slate-200 p-3"><p class="font-semibold">{{ $item->name }}</p><div class="mt-2 grid grid-cols-3 gap-2"><input type="number" min="0" name="old_assets[{{ $item->amenity_id }}][quantity]" value="{{ old('old_assets.'.$item->amenity_id.'.quantity', $item->quantity) }}" class="h-10 rounded border border-slate-200 px-2"><select name="old_assets[{{ $item->amenity_id }}][condition]" class="h-10 rounded border border-slate-200 px-2"><option value="normal">Bình thường</option><option value="damaged">Hư hỏng</option></select><input name="old_assets[{{ $item->amenity_id }}][note]" placeholder="Ghi chú" class="h-10 rounded border border-slate-200 px-2"></div></div>
                @empty<p class="text-sm text-slate-500">Không có tài sản bàn giao đã lưu.</p>@endforelse</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-bold text-slate-950">4. Tài sản nhận tại phòng mới</h3>
                @foreach($rooms as $room)
                    <div data-new-room-assets="{{ $room->id }}" class="mt-4 hidden space-y-3">@forelse($room->amenities as $asset)
                        <div class="rounded-lg border border-slate-200 p-3"><p class="font-semibold">{{ $asset->name }}</p><div class="mt-2 grid grid-cols-3 gap-2"><input type="number" min="0" name="new_assets[{{ $room->id }}][{{ $asset->id }}][quantity]" value="{{ $asset->pivot->quantity }}" class="h-10 rounded border border-slate-200 px-2"><select name="new_assets[{{ $room->id }}][{{ $asset->id }}][condition]" class="h-10 rounded border border-slate-200 px-2"><option value="normal">Bình thường</option><option value="damaged">Hư hỏng</option></select><input name="new_assets[{{ $room->id }}][{{ $asset->id }}][note]" value="{{ $asset->pivot->note }}" placeholder="Ghi chú" class="h-10 rounded border border-slate-200 px-2"></div></div>
                    @empty<p class="text-sm text-slate-500">Phòng này không có tài sản bàn giao.</p>@endforelse</div>
                @endforeach
                <p id="asset-placeholder" class="mt-4 text-sm text-slate-500">Chọn phòng mới để xem tài sản.</p>
            </div>
        </section>

        <section class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <label class="flex items-start gap-3"><input type="checkbox" name="confirm_transfer" value="1" required class="mt-1"><span class="text-sm font-semibold text-amber-950">Tôi đã đối chiếu công nợ, chỉ số và tài sản. Tôi hiểu thao tác sẽ tạo chứng từ phòng cũ, cập nhật tiền thuê/cọc theo phòng mới và gửi thông báo cho khách.</span></label>
            <button class="mt-4 rounded-lg bg-indigo-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-800">{{ $reviewing ? 'Duyệt và chuyển phòng' : 'Xác nhận chuyển phòng' }}</button>
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
