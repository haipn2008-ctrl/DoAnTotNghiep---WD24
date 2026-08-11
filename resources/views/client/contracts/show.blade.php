@extends('layouts.client.index')

@section('title', 'Chi tiết hợp đồng | Cổng khách thuê')
@section('page_title', 'Chi tiết hợp đồng')

@php
    $statuses = ['draft'=>'Bản nháp','pending_signature'=>'Chờ ký','pending_deposit'=>'Chờ cọc','awaiting_move_in'=>'Chờ nhận phòng','active'=>'Đang ở','expired'=>'Quá hạn vẫn ở','settling'=>'Đang quyết toán','completed'=>'Đã hoàn tất','cancelled'=>'Đã hủy'];
    $depositStatuses = ['pending'=>'Chưa thu đủ','paid'=>'Đã thu','refunded'=>'Đã hoàn','deducted'=>'Đã khấu trừ','retained'=>'Đã giữ lại','not_required'=>'Không yêu cầu'];
    $effectiveEnd = $contract->extend_end_date ?? $contract->end_date;
    $plannedResidentCount = $contract->occupants->whereIn('status', [
        \App\Models\ContractOccupant::STATUS_PENDING,
        \App\Models\ContractOccupant::STATUS_APPROVED,
        \App\Models\ContractOccupant::STATUS_CHECKED_IN,
    ])->count();
    $occupancyLimitReached = $plannedResidentCount >= (int) $contract->room->max_people;
@endphp

@section('content')
    <div class="space-y-5">
        @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end"><div><a href="{{ route('client.contracts.index') }}" class="text-sm font-semibold text-indigo-700">← Hợp đồng của tôi</a><h2 class="mt-2 text-2xl font-bold text-slate-950">{{ $contract->contract_code }}</h2><p class="mt-1 text-sm text-slate-500">{{ $statuses[$contract->status] ?? 'Không xác định' }}</p></div>@if($contract->contractFileExists())<a href="{{ route('client.contracts.file', $contract) }}" target="_blank" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Xem file hợp đồng</a>@elseif($contract->contract_file)<span class="text-sm font-medium text-amber-700">File hợp đồng không còn tồn tại</span>@endif</div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Phòng</p><p class="mt-2 text-xl font-bold">{{ $contract->room->room_code ?? '-' }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Tiền thuê/tháng</p><p class="mt-2 text-xl font-bold">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Tiền cọc</p><p class="mt-2 text-xl font-bold">{{ number_format($contract->deposit_amount, 0, ',', '.') }}đ</p><p class="mt-1 text-xs text-slate-500">{{ $depositStatuses[$contract->deposit_status] ?? 'Chưa cập nhật' }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Số người</p><p class="mt-2 text-xl font-bold">{{ $contract->number_of_people }} người</p></div>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-slate-950">Thời hạn hợp đồng</h3><div class="mt-4 grid gap-4 sm:grid-cols-3"><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Ngày bắt đầu</p><p class="mt-1 font-bold">{{ $contract->start_date->format('d/m/Y') }}</p></div><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Ngày kết thúc</p><p class="mt-1 font-bold">{{ $effectiveEnd->format('d/m/Y') }}</p></div><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Ngày ký</p><p class="mt-1 font-bold">{{ $contract->signed_at?->format('d/m/Y') ?? 'Chưa cập nhật' }}</p></div></div>@if($contract->extended_at)<p class="mt-4 text-sm text-slate-600">Hợp đồng đã được gia hạn ngày {{ $contract->extended_at->format('d/m/Y') }}.</p>@endif</section>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5">
                <h3 class="font-semibold text-slate-950">Người ở</h3>
                <p class="mt-1 text-sm text-slate-500">Danh sách người thực tế cư trú, không phụ thuộc người đứng tên thuê. Khai báo mới chỉ có hiệu lực sau khi admin duyệt.</p>
            </div>
            <div class="space-y-3 p-5">
                @forelse($contract->occupants->where('status', '!=', \App\Models\ContractOccupant::STATUS_NON_RESIDENT) as $occupant)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $occupant->full_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $occupant->role === \App\Models\ContractOccupant::ROLE_REPRESENTATIVE ? 'Người đại diện đồng thời là người ở' : 'Người ở' }}</p>
                                @if($occupant->review_note)<p class="mt-2 text-sm text-rose-700">Phản hồi của admin: {{ $occupant->review_note }}</p>@endif
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $occupant->status_label }}</span>
                                @if($occupant->role !== \App\Models\ContractOccupant::ROLE_REPRESENTATIVE && $occupant->status === \App\Models\ContractOccupant::STATUS_PENDING)
                                    <form method="POST" action="{{ route('client.contracts.occupants.withdraw', [$contract, $occupant]) }}">@csrf<button class="text-xs font-semibold text-rose-700">Rút khai báo</button></form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Chưa có hồ sơ cư trú.</p>
                @endforelse
            </div>

            @if(in_array($contract->status, [\App\Models\Contract::STATUS_DRAFT, \App\Models\Contract::STATUS_PENDING_SIGNATURE, \App\Models\Contract::STATUS_PENDING_DEPOSIT, \App\Models\Contract::STATUS_AWAITING_MOVE_IN, \App\Models\Contract::STATUS_ACTIVE, \App\Models\Contract::STATUS_EXPIRED], true))
                @if($occupancyLimitReached)
                    <div class="border-t border-amber-200 bg-amber-50 p-5 text-sm font-semibold text-amber-800">Phòng chỉ chứa tối đa {{ $contract->room->max_people }} người. Đã đạt giới hạn của phòng.</div>
                @else
                <form method="POST" action="{{ route('client.contracts.occupants.store', $contract) }}" enctype="multipart/form-data" class="grid gap-3 border-t border-slate-200 bg-slate-50 p-5 sm:grid-cols-2">
                    @csrf
                    <div class="sm:col-span-2"><h4 class="font-semibold text-slate-900">Khai báo thêm người ở</h4><p class="mt-1 text-xs text-slate-500">Thông tin này tách khỏi hồ sơ tài khoản của bạn và sẽ được lưu lịch sử.</p></div>
                    <input name="full_name" value="{{ old('full_name') }}" required maxlength="150" placeholder="Họ và tên *" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">
                    <input name="identity_number" value="{{ old('identity_number') }}" required inputmode="numeric" minlength="12" maxlength="12" placeholder="CCCD *" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">
                    <input name="phone" value="{{ old('phone') }}" maxlength="30" placeholder="Số điện thoại" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">Ảnh mặt trước CCCD *</label><input type="file" name="identity_front" required accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-slate-200 bg-white text-xs file:mr-2 file:border-0 file:bg-slate-100 file:px-3 file:py-2"></div>
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">Ảnh mặt sau CCCD *</label><input type="file" name="identity_back" required accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-slate-200 bg-white text-xs file:mr-2 file:border-0 file:bg-slate-100 file:px-3 file:py-2"></div>
                    <div class="sm:col-span-2"><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Gửi admin duyệt</button></div>
                </form>
                @endif
            @endif
        </section>

        @if($contract->note)<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold">Ghi chú hợp đồng</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $contract->note }}</p></section>@endif
    </div>
@endsection
