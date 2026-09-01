@extends('layouts.admin.index')

@section('title', 'Sửa bản nháp hợp đồng')
@section('page_title', 'Sửa bản nháp hợp đồng')

@section('content')
@php
    $durationOption = old('contract_duration', $contract->rental_duration_option);
    if (!is_numeric($durationOption) || (int) $durationOption < 12) {
        $durationOption = $contract->start_date?->diffInMonths($contract->end_date) ?: 12;
    }
@endphp
<div class="mx-auto max-w-5xl space-y-5">
    <div class="flex items-end justify-between">
        <div><p class="text-sm text-slate-500">{{ $contract->contract_code }}</p><h2 class="text-2xl font-bold">Chỉnh sửa bản nháp</h2></div>
        <a href="{{ route('admin.contracts.show', $contract) }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Quay lại</a>
    </div>
    @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.contracts.update', $contract) }}" enctype="multipart/form-data" data-contract-schedule data-ajax-validation-form novalidate class="rounded-xl border border-slate-200 bg-white shadow-sm">
        @csrf @method('PUT')
        <div class="grid gap-5 p-6 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold">Phòng</label>
                <select name="room_id" required class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @foreach($rooms as $room)
                        @php
                            $currentOccupancy = $room->activeContract;
                            $availabilityBlocked = $room->status === \App\Models\Room::STATUS_OCCUPIED
                                && (! $currentOccupancy || $currentOccupancy->status === \App\Models\Contract::STATUS_EXPIRED || $currentOccupancy->end_date->copy()->endOfDay()->isPast());
                            $availableFrom = $room->status === \App\Models\Room::STATUS_OCCUPIED && ! $availabilityBlocked
                                ? $currentOccupancy?->end_date?->copy()->addDay()
                                : null;
                        @endphp
                        <option value="{{ $room->id }}" data-max-people="{{ $room->max_people }}" data-room-code="{{ $room->room_code }}" data-occupied-until="{{ $currentOccupancy?->end_date?->toDateString() }}" data-available-from="{{ $availableFrom?->toDateString() }}" data-availability-blocked="{{ $availabilityBlocked ? '1' : '0' }}" @selected((string)old('room_id',$contract->room_id)===(string)$room->id)>
                            {{ $room->room_code }} - {{ number_format($room->price, 0, ',', '.') }}đ/tháng{{ $availabilityBlocked ? ' (khách chưa trả phòng, chưa thể xếp lịch)' : ($availableFrom ? ' (có thể thuê từ '.$availableFrom->format('d/m/Y').')' : '') }}
                        </option>
                    @endforeach
                </select>
                <p data-room-availability-message class="mt-2 hidden rounded-lg border px-3 py-2 text-sm font-semibold"></p>
                @error('room_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div><label class="mb-1 block text-sm font-semibold">Người thuê đại diện (được cấp tài khoản)</label><select name="tenant_id" data-contract-representative required class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" data-full-name="{{ $tenant->full_name }}" data-phone="{{ $tenant->phone }}" data-date-of-birth="{{ $tenant->date_of_birth?->toDateString() }}" data-gender="{{ $tenant->gender }}" data-cccd="{{ $tenant->cccd }}" data-cccd-issue-date="{{ $tenant->cccd_issue_date?->toDateString() }}" data-cccd-issue-place="{{ $tenant->cccd_issue_place }}" data-address="{{ $tenant->address }}" data-identity-front-url="{{ $tenant->document?->hasImage('front') ? route('admin.tenants.identity-document', [$tenant, 'front']) : '' }}" data-identity-back-url="{{ $tenant->document?->hasImage('back') ? route('admin.tenants.identity-document', [$tenant, 'back']) : '' }}" @selected((string)old('tenant_id',$contract->tenant_id)===(string)$tenant->id)>{{ $tenant->full_name }} — {{ $tenant->user?->email }}</option>@endforeach</select></div>
            @include('admin.contracts.partials.representative-fields')
            @include('admin.contracts.partials.member-selector')
            <div class="border-t border-slate-200 pt-5 md:col-span-2"><h3 class="font-semibold">Thời hạn hợp đồng</h3></div>
            <div><label class="mb-1 block text-sm font-semibold">Ngày bắt đầu thời hạn thuê *</label><input data-contract-start type="date" name="start_date" value="{{ old('start_date',$contract->start_date?->toDateString()) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
            <div><label class="mb-1 block text-sm font-semibold">Thời hạn (tối thiểu 12 tháng) *</label><input data-contract-duration type="number" min="12" max="120" name="contract_duration" value="{{ $durationOption ?: 12 }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
            <div><label class="mb-1 block text-sm font-semibold">Ngày kết thúc</label><input data-contract-end type="date" name="end_date" value="{{ old('end_date',$contract->end_date?->toDateString()) }}" readonly required class="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-slate-600"></div>
            <div class="border-t border-slate-200 pt-5 md:col-span-2"><h3 class="font-semibold">Ký, đóng cọc và nhận phòng</h3></div>
            <div><label class="mb-1 block text-sm font-semibold">Ngày dự kiến nhận phòng *</label><input data-contract-move-in type="date" name="scheduled_move_in_date" value="{{ old('scheduled_move_in_date',$contract->scheduled_move_in_date?->toDateString()) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
            <div><label class="mb-1 block text-sm font-semibold">Hạn cuối phải nhận phòng *</label><input data-contract-move-in-deadline type="date" name="reservation_expires_at" value="{{ old('reservation_expires_at',$contract->reservation_expires_at?->toDateString()) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"><p data-contract-deadline-error class="mt-1 hidden text-sm font-semibold text-rose-600"></p></div>
            <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm font-semibold text-indigo-950 md:col-span-2">Tiền cọc {{ number_format($contract->deposit_amount, 0, ',', '.') }}đ</div>
            @include('admin.contracts.partials.service-fields', [
                'selectedRoomId' => old('room_id', $contract->room_id),
            ])
            <div><label class="mb-1 block text-sm font-semibold">Lý do sửa</label><input name="edit_reason" value="{{ old('edit_reason') }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></div>
            <div class="md:col-span-2"><label class="mb-1 block text-sm font-semibold">Ghi chú</label><textarea name="note" rows="3" class="w-full rounded-lg border border-slate-200 p-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('note',$contract->note) }}</textarea></div>
        </div>
        <div class="flex justify-end border-t border-slate-200 p-5"><button class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white hover:bg-indigo-700">Lưu bản nháp</button></div>
    </form>
</div>
@endsection
