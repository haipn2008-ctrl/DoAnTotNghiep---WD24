@extends('layouts.client.index')

@section('title', 'Chi tiết hợp đồng | Cổng khách thuê')
@section('page_title', 'Chi tiết hợp đồng')

@php
    $statuses = ['draft'=>'Bản nháp','pending_signature'=>'Chờ ký','pending_deposit'=>'Chờ tiền cọc','awaiting_move_in'=>'Chờ nhận phòng','active'=>'Đang ở','expired'=>'Quá hạn vẫn ở','settling'=>'Đang quyết toán','completed'=>'Đã hoàn tất','cancelled'=>'Đã hủy'];
    $depositStatuses = ['pending'=>'Chưa thu đủ','paid'=>'Đã thu','refunded'=>'Đã hoàn','deducted'=>'Đã khấu trừ','retained'=>'Đã giữ lại','not_required'=>'Không yêu cầu'];
    $effectiveEnd = $contract->extend_end_date ?? $contract->end_date;
    $plannedResidentCount = $contract->currentMembers->whereIn('status', [
        \App\Models\ContractTenant::STATUS_PENDING,
        \App\Models\ContractTenant::STATUS_APPROVED,
        \App\Models\ContractTenant::STATUS_CHECKED_IN,
    ])->count();
    $occupancyLimitReached = $plannedResidentCount >= (int) $contract->room->max_people;
    $vehicleTypeLabels = ['motorcycle' => 'Xe máy', 'electric_motorcycle' => 'Xe máy điện', 'bicycle' => 'Xe đạp'];
    $approvedVehicles = $contract->currentMembers
        ->flatMap(fn ($member) => $member->tenant?->vehicles ?? collect())
        ->where('status', \App\Models\Vehicle::STATUS_APPROVED)
        ->unique('id')
        ->values();
    $conditionLabels = [
        'normal' => 'Sử dụng bình thường', 'good' => 'Sử dụng bình thường',
        'worn' => 'Sử dụng bình thường', 'damaged' => 'Có hư hỏng',
    ];
    $canConfirmMoveInDetails = in_array($contract->status, [
        \App\Models\Contract::STATUS_PENDING_SIGNATURE,
        \App\Models\Contract::STATUS_PENDING_DEPOSIT,
        \App\Models\Contract::STATUS_AWAITING_MOVE_IN,
    ], true);
@endphp

@section('content')
    <div class="space-y-5">
        @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end"><div><a href="{{ route('client.contracts.index') }}" class="text-sm font-semibold text-indigo-700">← Hợp đồng của tôi</a><h2 class="mt-2 text-2xl font-bold text-slate-950">{{ $contract->contract_code }}</h2><p class="mt-1 text-sm text-slate-500">{{ $statuses[$contract->status] ?? 'Không xác định' }}</p></div>@if($contract->contractFileExists())<a href="{{ route('client.contracts.file', $contract) }}" target="_blank" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Xem file hợp đồng</a>@elseif($contract->contract_file)<span class="text-sm font-medium text-amber-700">File hợp đồng không còn tồn tại</span>@endif</div>

        @if($canConfirmMoveInDetails && $contract->move_in_inventory_snapshotted_at && ! $contract->move_in_details_confirmed_at)
            <section class="overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm">
                <div class="border-b border-indigo-100 bg-indigo-50 px-5 py-4">
                    <h3 class="font-semibold text-indigo-950">Xác nhận thông tin nhận phòng</h3>
                </div>
                <form method="POST" action="{{ route('client.contracts.move-in-details.confirm', $contract) }}" class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                    @csrf
                    <label class="flex items-start gap-3 text-sm font-medium text-slate-800">
                        <input type="checkbox" name="confirmation" value="1" required class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600">
                        <span>Tôi đã kiểm tra thông tin dịch vụ và tài sản trong phòng.</span>
                    </label>
                    <button class="shrink-0 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Xác nhận thông tin</button>
                </form>
                @error('confirmation')<p class="px-5 pb-4 text-sm text-rose-700">{{ $message }}</p>@enderror
            </section>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Phòng</p><p class="mt-2 text-xl font-bold">{{ $contract->room->room_code ?? '-' }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Tiền thuê/tháng</p><p class="mt-2 text-xl font-bold">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Tiền cọc</p><p class="mt-2 text-xl font-bold">{{ number_format($contract->deposit_amount, 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Số người</p><p class="mt-2 text-xl font-bold">{{ $contract->number_of_people }} người</p></div>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-slate-950">Thời hạn hợp đồng</h3><div class="mt-4 grid gap-4 sm:grid-cols-3"><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Ngày bắt đầu</p><p class="mt-1 font-bold">{{ $contract->start_date->format('d/m/Y') }}</p></div><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Ngày kết thúc</p><p class="mt-1 font-bold">{{ $effectiveEnd->format('d/m/Y') }}</p></div><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Ngày ký</p><p class="mt-1 font-bold">{{ $contract->signed_at?->format('d/m/Y') ?? 'Chưa cập nhật' }}</p></div></div>@if($contract->extended_at)<p class="mt-4 text-sm text-slate-600">Hợp đồng đã được gia hạn ngày {{ $contract->extended_at->format('d/m/Y') }}.</p>@endif</section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div>
                        <h3 class="font-semibold text-slate-950">Thông tin nhận phòng</h3>
                    </div>
                    @if($contract->move_in_details_confirmed_at)
                        <span class="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Đã xác nhận thông tin {{ $contract->move_in_details_confirmed_at->format('d/m/Y H:i') }}</span>
                    @else
                        <span class="w-fit rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Chưa xác nhận thông tin</span>
                    @endif
                </div>
            </div>

            <div class="p-5">
                <h4 class="text-sm font-semibold text-slate-900">Tiện nghi và dịch vụ</h4>
                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4"><p class="text-sm font-semibold text-indigo-950">Internet bắt buộc</p><p class="mt-1 text-xs text-indigo-700">{{ number_format((float) $setting->internet_fee, 0, ',', '.') }}đ/phòng/tháng</p></div>
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4"><p class="text-sm font-semibold text-emerald-950">Máy lạnh</p></div>
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4"><p class="text-sm font-semibold text-indigo-950">Dịch vụ chung bắt buộc</p></div>
                </div>
                <div class="mt-4 flex items-center justify-between gap-3"><p class="text-sm font-semibold text-slate-900">Phương tiện đã duyệt: {{ $approvedVehicles->count() }}</p><a href="{{ route('client.vehicles.index') }}" class="text-sm font-semibold text-indigo-700">Quản lý phương tiện</a></div>
                @if($approvedVehicles->isNotEmpty())<div class="mt-2 flex flex-wrap gap-2">@foreach($approvedVehicles as $vehicle)<span class="rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700">{{ $vehicleTypeLabels[$vehicle->vehicle_type] ?? 'Phương tiện' }} · {{ $vehicle->license_plate ?: 'Không có biển số' }} · {{ $vehicle->tenant->full_name }}</span>@endforeach</div>@endif
            </div>

            <div class="border-t border-slate-200">
                <div class="px-5 py-4"><h4 class="text-sm font-semibold text-slate-900">Tài sản bàn giao</h4></div>
                @if($contract->move_in_inventory_snapshotted_at)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Vật dụng / tài sản</th><th class="px-5 py-3">Số lượng</th><th class="px-5 py-3">Tình trạng</th><th class="px-5 py-3">Ghi chú</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($contract->handoverItems as $item)
                                    <tr><td class="px-5 py-3"><p class="font-semibold text-slate-900">{{ $item->name }}</p></td><td class="px-5 py-3">{{ $item->is_quantifiable ? $item->quantity : 'Có' }}</td><td class="px-5 py-3"><span class="font-medium {{ $item->condition === 'damaged' ? 'text-rose-700' : 'text-emerald-700' }}">{{ $conditionLabels[$item->condition] ?? 'Không xác định' }}</span></td><td class="px-5 py-3 text-slate-600">{{ $item->note ?: '—' }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">Phòng không có tài sản hoặc vật dụng bàn giao được khai báo.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="px-5 pb-5 text-sm text-amber-700">Chưa có danh sách tài sản.</p>
                @endif
            </div>

        </section>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5">
                <h3 class="font-semibold text-slate-950">Người thuê</h3>
            </div>
            <div class="space-y-3 p-5">
                @forelse($contract->currentMembers as $member)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $member->full_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE ? 'Người đại diện đồng thời là người thuê' : 'Người thuê' }}</p>
                                @if($member->review_note)<p class="mt-2 text-sm text-rose-700">Phản hồi: {{ $member->review_note }}</p>@endif
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $member->status_label }}</span>
                                @if($member->role !== \App\Models\ContractTenant::ROLE_REPRESENTATIVE && $member->status === \App\Models\ContractTenant::STATUS_PENDING)
                                    <form method="POST" action="{{ route('client.contracts.members.withdraw', [$contract, $member]) }}">@csrf<button class="text-xs font-semibold text-rose-700">Rút khai báo</button></form>
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
                <form method="POST" action="{{ route('client.contracts.members.store', $contract) }}" enctype="multipart/form-data" class="grid gap-3 border-t border-slate-200 bg-slate-50 p-5 sm:grid-cols-2">
                    @csrf
                    <div class="sm:col-span-2"><h4 class="font-semibold text-slate-900">Thêm người thuê</h4></div>
                    <input name="full_name" value="{{ old('full_name') }}" required maxlength="150" placeholder="Họ và tên *" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" max="{{ now()->subYears(18)->toDateString() }}" required class="h-10 rounded-lg border border-slate-200 px-3 text-sm">
                    <select name="gender" required class="h-10 rounded-lg border border-slate-200 px-3 text-sm"><option value="">Giới tính *</option><option value="male" @selected(old('gender') === 'male')>Nam</option><option value="female" @selected(old('gender') === 'female')>Nữ</option><option value="other" @selected(old('gender') === 'other')>Khác</option></select>
                    <input name="identity_number" value="{{ old('identity_number') }}" required inputmode="numeric" minlength="12" maxlength="12" placeholder="CCCD *" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">
                    <input type="date" name="cccd_issue_date" value="{{ old('cccd_issue_date') }}" max="{{ today()->toDateString() }}" required title="Ngày cấp CCCD" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">
                    <input name="cccd_issue_place" value="{{ old('cccd_issue_place') }}" required maxlength="255" placeholder="Nơi cấp CCCD *" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">
                    <input name="phone" value="{{ old('phone') }}" required minlength="10" maxlength="15" inputmode="tel" placeholder="Số điện thoại *" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">
                    <input type="email" name="email" value="{{ old('email') }}" maxlength="255" placeholder="Email (không bắt buộc)" class="h-10 rounded-lg border border-slate-200 px-3 text-sm">
                    <input name="address" value="{{ old('address') }}" required maxlength="500" placeholder="Địa chỉ thường trú *" class="h-10 rounded-lg border border-slate-200 px-3 text-sm sm:col-span-2">
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">Ảnh mặt trước CCCD *</label><input type="file" name="identity_front" required accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-slate-200 bg-white text-xs file:mr-2 file:border-0 file:bg-slate-100 file:px-3 file:py-2"></div>
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">Ảnh mặt sau CCCD *</label><input type="file" name="identity_back" required accept="image/jpeg,image/png,image/webp" class="block w-full rounded-lg border border-slate-200 bg-white text-xs file:mr-2 file:border-0 file:bg-slate-100 file:px-3 file:py-2"></div>
                    <div class="sm:col-span-2"><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Gửi duyệt</button></div>
                </form>
                @endif
            @endif
        </section>

        @if($contract->note)<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold">Ghi chú hợp đồng</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $contract->note }}</p></section>@endif
    </div>
@endsection
