@extends('layouts.client.index')

@section('title', 'Chi tiết hợp đồng | Cổng khách thuê')
@section('page_title', 'Chi tiết hợp đồng')

@php
    $statuses = ['draft'=>'Bản nháp','pending_signature'=>'Chờ ký','pending_deposit'=>'Chờ tiền cọc','awaiting_move_in'=>'Chờ nhận phòng','active'=>'Đang ở','expired'=>'Hết hạn - chờ xử lý','settling'=>'Đang quyết toán','completed'=>'Đã hoàn tất','cancelled'=>'Đã hủy'];
    $depositStatuses = ['pending'=>'Chưa thu đủ','paid'=>'Đã thu','refunded'=>'Đã hoàn','deducted'=>'Đã khấu trừ','retained'=>'Đã giữ lại','not_required'=>'Không yêu cầu'];
    $effectiveEnd = $contract->extend_end_date ?? $contract->end_date;
    $currentOccupancy = $contract->capacityOccupancyCount();
    $occupancyLimitReached = $currentOccupancy >= (int) $contract->room->max_people;
    $canAddTenant = ! $occupancyLimitReached && in_array($contract->status, [
        \App\Models\Contract::STATUS_DRAFT,
        \App\Models\Contract::STATUS_PENDING_SIGNATURE,
        \App\Models\Contract::STATUS_PENDING_DEPOSIT,
        \App\Models\Contract::STATUS_AWAITING_MOVE_IN,
        \App\Models\Contract::STATUS_ACTIVE,
        \App\Models\Contract::STATUS_EXPIRED,
    ], true);
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
    $canConfirmMoveInDetails = $contract->status === \App\Models\Contract::STATUS_AWAITING_MOVE_IN;
    $incompleteMoveInMembers = $contract->currentMembers
        ->filter(fn ($member) => ! $member->hasCompleteMoveInProfile())
        ->values();
    $moveInProfilesReady = $incompleteMoveInMembers->isEmpty()
        && ! $contract->currentMembers->contains('status', \App\Models\ContractTenant::STATUS_PENDING);
    $handoverImagesReady = $handoverReading?->meterImageExists('electricity')
        && $handoverReading?->meterImageExists('water');
    $signedContractFileExists = $contract->contractFileExists();
    $signedContractMediaType = strtolower(pathinfo((string) $contract->contract_file, PATHINFO_EXTENSION)) === 'pdf'
        ? 'pdf'
        : 'image';
    $isExpiringSoon = $contract->isExpiringSoon();
    $needsEndOfTermDecision = $isExpiringSoon || $contract->status === 'expired';
    $openExtensionRequest = $contract->extensionRequests->first(fn ($request) => in_array($request->status, ['pending', 'awaiting_confirmation'], true));
    $openTerminationRequest = $contract->terminationRequests->first(fn ($request) => in_array($request->status, ['pending', 'approved'], true));
    $statusStyles = [
        'draft' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'pending_signature' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'pending_deposit' => 'bg-orange-50 text-orange-700 ring-orange-200',
        'awaiting_move_in' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'expired' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'settling' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'completed' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];
    $roomAssetsByAmenityId = $contract->room?->amenities?->keyBy('id') ?? collect();
@endphp

@section('content')
    <div class="contract-detail-page space-y-5">
        @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @if($canConfirmMoveInDetails && ! $moveInProfilesReady)
            <section class="rounded-xl border border-amber-300 bg-amber-50 p-5 text-amber-950 shadow-sm">
                <h3 class="font-bold">Cần hoàn thiện danh sách người nhận phòng</h3>
                @if($incompleteMoveInMembers->isNotEmpty())
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach($incompleteMoveInMembers as $member)
                            <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 ring-1 ring-amber-200">
                                <span><strong>{{ $member->full_name }}</strong> · thiếu {{ implode(', ', $member->missingMoveInProfileFields()) }}</span>
                                @if($member->role !== \App\Models\ContractTenant::ROLE_REPRESENTATIVE)
                                    <a href="{{ route('client.contracts.members.edit', [$contract, $member]) }}" class="shrink-0 font-semibold text-indigo-700">Bổ sung ngay →</a>
                                @else
                                    <span class="shrink-0 font-semibold text-amber-800">Liên hệ ban quản lý</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif
        @if(auth()->user()->status === \App\Models\User::STATUS_FORMER)
            <div class="rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm font-semibold text-sky-900">Hợp đồng đã hoàn tất</div>
        @endif
        <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
            <div class="min-w-0">
                <a href="{{ route('client.contracts.index') }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"><i class="bx bxs-left-arrow-alt text-lg"></i>Quay lại</a>
                <div class="mt-4 flex flex-wrap items-center gap-3"><h2 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ $contract->contract_code }}</h2><span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset {{ $statusStyles[$contract->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $isExpiringSoon ? 'Sắp hết hạn' : ($statuses[$contract->status] ?? 'Không xác định') }}</span></div>
                <p class="mt-2 text-sm text-slate-500">Phòng {{ $contract->room->room_code ?? '—' }} · Từ {{ $contract->start_date?->format('d/m/Y') }} đến {{ $effectiveEnd?->format('d/m/Y') }}</p>
            </div>
            <div class="flex flex-wrap gap-2 lg:justify-end">
                @if($signedContractFileExists)
                    <a href="{{ route('client.contracts.file', $contract) }}" data-image-modal data-media-type="{{ $signedContractMediaType }}" data-image-title="{{ $contract->signed_at ? 'Bản hợp đồng đã ký' : 'File hợp đồng' }} - {{ $contract->contract_code }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 hover:shadow-md"><i class="bx bxs-file-find text-lg"></i>{{ $contract->signed_at ? 'Xem bản đã ký' : 'Xem file hợp đồng' }}</a>
                @elseif($contract->contract_file)
                    <span class="inline-flex items-center text-sm font-medium text-amber-700">File hợp đồng không còn tồn tại</span>
                @elseif($contract->signed_at)
                    <span class="inline-flex items-center text-sm font-medium text-amber-700">Chưa có file bản hợp đồng đã ký</span>
                @endif
                <a href="{{ route('client.contracts.appendices.index', $contract) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"><i class="bx bxs-file text-lg"></i>Phụ lục</a>
                @if($canAddTenant)
                    <a href="{{ route('client.contracts.tenants.create', $contract) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100"><i class="bx bxs-user-plus text-lg"></i>Thêm người thuê</a>
                @endif
            </div></div>
        </header>

        @if($needsEndOfTermDecision)
            <section class="overflow-hidden rounded-xl border {{ $contract->status === \App\Models\Contract::STATUS_EXPIRED ? 'border-rose-200' : 'border-amber-200' }} bg-white shadow-sm">
                <div class="border-b px-5 py-4 {{ $contract->status === \App\Models\Contract::STATUS_EXPIRED ? 'border-rose-100 bg-rose-50' : 'border-amber-100 bg-amber-50' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-slate-950">{{ $contract->status === \App\Models\Contract::STATUS_EXPIRED ? 'Hợp đồng đã hết hạn - chờ xử lý' : 'Hợp đồng sắp hết hạn' }}</h3>
                            <p class="mt-1 text-sm text-slate-600">Kết thúc: <strong>{{ $effectiveEnd?->format('d/m/Y') }}</strong></p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $contract->status === \App\Models\Contract::STATUS_EXPIRED ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">{{ $contract->status === \App\Models\Contract::STATUS_EXPIRED ? 'Bước 2/3' : 'Bước 1/3' }}</span>
                    </div>
                </div>
                <div class="p-5">
                    @if($openExtensionRequest)
                        <div class="flex flex-col justify-between gap-3 rounded-xl border border-sky-200 bg-sky-50 p-4 sm:flex-row sm:items-center">
                            <div><p class="font-bold text-sky-900">Đã chọn gia hạn</p><p class="mt-1 text-sm text-sky-700">Yêu cầu đang chờ ban quản lý xử lý.</p></div>
                            <a href="{{ route('client.extension-requests.index') }}" class="shrink-0 rounded-lg bg-sky-700 px-4 py-2.5 text-sm font-semibold text-white">Xem yêu cầu</a>
                        </div>
                    @elseif($openTerminationRequest)
                        <div class="flex flex-col justify-between gap-3 rounded-xl border border-violet-200 bg-violet-50 p-4 sm:flex-row sm:items-center">
                            <div><p class="font-bold text-violet-900">{{ $openTerminationRequest->status === 'approved' ? 'Đã chốt trả phòng - chờ bàn giao' : 'Đã chọn trả phòng' }}</p><p class="mt-1 text-sm text-violet-700">{{ $openTerminationRequest->status === 'approved' ? 'Thực hiện bàn giao theo lịch đã được duyệt.' : 'Yêu cầu đang chờ ban quản lý duyệt.' }}</p></div>
                            <a href="{{ route('client.termination-requests.index') }}" class="shrink-0 rounded-lg bg-violet-700 px-4 py-2.5 text-sm font-semibold text-white">Xem yêu cầu</a>
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-2">
                            <a href="{{ route('client.extension-requests.index') }}" class="rounded-xl border border-sky-200 bg-sky-50 p-4 transition hover:bg-sky-100">
                                <p class="font-bold text-sky-900">Gia hạn hợp đồng</p>
                                <p class="mt-1 text-sm text-sky-700">Gửi thời hạn mong muốn để ban quản lý xem xét.</p>
                            </a>
                            <a href="{{ route('client.termination-requests.index') }}" class="rounded-xl border border-violet-200 bg-violet-50 p-4 transition hover:bg-violet-100">
                                <p class="font-bold text-violet-900">Trả phòng</p>
                                <p class="mt-1 text-sm text-violet-700">Đăng ký ngày bàn giao phòng và chờ xác nhận.</p>
                            </a>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if($canConfirmMoveInDetails && $moveInProfilesReady && $contract->move_in_inventory_snapshotted_at && ! $contract->move_in_details_confirmed_at && $handoverReading && $handoverImagesReady)
            <section class="overflow-hidden rounded-lg border border-indigo-200 bg-white shadow-sm">
                <div class="border-b border-indigo-100 bg-indigo-50 px-5 py-4">
                    <h3 class="font-semibold text-indigo-950">Xác nhận thông tin nhận phòng</h3>
                </div>
                <form method="POST" action="{{ route('client.contracts.move-in-details.confirm', $contract) }}" class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                    @csrf
                    <label class="flex items-start gap-3 text-sm font-medium text-slate-800">
                        <input type="checkbox" name="confirmation" value="1" required class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600">
                        <span>Tôi đã đối chiếu ảnh đồng hồ với chỉ số điện <strong>{{ $handoverReading->electricity_new }} kWh</strong>, chỉ số nước <strong>{{ $handoverReading->water_new }} m³</strong>, dịch vụ và tài sản trong phòng.</span>
                    </label>
                    <button class="shrink-0 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Xác nhận thông tin</button>
                </form>
                @error('confirmation')<p class="px-5 pb-4 text-sm text-rose-700">{{ $message }}</p>@enderror
            </section>
        @elseif($canConfirmMoveInDetails && $moveInProfilesReady && ! $contract->move_in_details_confirmed_at && (! $handoverReading || ! $handoverImagesReady))
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">Ban quản lý chưa cung cấp đầy đủ chỉ số và ảnh đồng hồ điện nước bàn giao. Bạn sẽ có nút xác nhận sau khi thông tin được cập nhật.</div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-500">Phòng thuê</p><p class="mt-1 text-lg font-bold text-slate-950">{{ $contract->room->room_code ?? '-' }}</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-500">Tiền thuê mỗi tháng</p><p class="mt-1 text-lg font-bold text-slate-950">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-500">Tiền đặt cọc</p><p class="mt-1 text-lg font-bold text-slate-950">{{ number_format($contract->deposit_amount, 0, ',', '.') }}đ</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-500">Người đang ở</p><p class="mt-1 text-lg font-bold text-slate-950">{{ $contract->number_of_people }}/{{ $contract->room->max_people }} người</p></div>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><i class="bx bx-calendar text-xl"></i></span><div><h3 class="font-bold text-slate-950">Thời hạn hợp đồng</h3><p class="mt-0.5 text-xs text-slate-500">Các mốc thời gian có hiệu lực</p></div></div><div class="mt-5 grid gap-3 sm:grid-cols-3"><div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="text-xs text-slate-500">Ngày bắt đầu</p><p class="mt-1 font-bold text-slate-950">{{ $contract->start_date->format('d/m/Y') }}</p></div><div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="text-xs text-slate-500">Ngày kết thúc</p><p class="mt-1 font-bold text-slate-950">{{ $effectiveEnd->format('d/m/Y') }}</p></div><div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="text-xs text-slate-500">Ngày ký xác nhận</p><p class="mt-1 font-bold text-slate-950">{{ $contract->signed_at?->format('d/m/Y') ?? 'Chưa cập nhật' }}</p></div></div>@if($contract->extended_at)<p class="mt-4 rounded-lg bg-indigo-50 px-3 py-2 text-sm text-indigo-700">Hợp đồng đã được gia hạn ngày {{ $contract->extended_at->format('d/m/Y') }}.</p>@endif</section>

        @if($contract->approvedTerminationRequest && $contract->scheduled_move_out_at)
            <section class="overflow-hidden rounded-lg border border-violet-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-violet-100 bg-violet-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-violet-950">Lịch kết thúc và bàn giao phòng</h3>
                        <p class="mt-1 text-xs text-violet-700">Thông tin đã được ban quản lý xác nhận</p>
                    </div>
                    <span class="w-fit rounded-full bg-white px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-200">{{ $contract->approvedTerminationRequest->type_label }}</span>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Ngày kết thúc được duyệt</p><p class="mt-1 font-bold text-slate-950">{{ $contract->approvedTerminationRequest->approved_end_date?->format('d/m/Y') ?? 'Chưa cập nhật' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Ngày bàn giao</p><p class="mt-1 font-bold text-violet-800">{{ ($contract->approvedTerminationRequest->approved_end_date ?? $contract->scheduled_move_out_at)->format('d/m/Y') }}</p><p class="mt-1 text-xs text-slate-500">Trong giờ hành chính 08:00–17:00</p></div>
                    <div class="rounded-lg bg-slate-50 p-4 sm:col-span-2 lg:col-span-1"><p class="text-sm text-slate-500">Lý do</p><p class="mt-1 font-semibold text-slate-950">{{ $contract->approvedTerminationRequest->reason ?: 'Không có ghi chú' }}</p></div>
                </div>
                @if($contract->approvedTerminationRequest->admin_note)
                    <div class="border-t border-violet-100 bg-violet-50/40 px-5 py-4 text-sm text-violet-900"><strong>Lưu ý từ ban quản lý:</strong> {{ $contract->approvedTerminationRequest->admin_note }}</div>
                @endif
            </section>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50/60 p-5 sm:px-6">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700"><i class="bx bx-log-in-circle text-xl"></i></span><div><h3 class="font-bold text-slate-950">Thông tin nhận phòng</h3><p class="mt-0.5 text-xs text-slate-500">Chỉ số, dịch vụ và tài sản tại thời điểm bàn giao</p></div></div>
                    @if($contract->move_in_details_confirmed_at)
                        <span class="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Đã xác nhận thông tin {{ $contract->move_in_details_confirmed_at->format('d/m/Y H:i') }}</span>
                    @else
                        <span class="w-fit rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Chưa xác nhận thông tin</span>
                    @endif
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <h4 class="flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bx bx-tachometer text-lg text-indigo-600"></i>Chỉ số điện nước bàn giao</h4>
                @if($handoverReading)
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div class="overflow-hidden rounded-xl border border-amber-200 bg-amber-50">
                            @if($handoverReading->meterImageExists('electricity'))
                                <a href="{{ route('client.contracts.handover-meter-image', [$contract, 'electricity']) }}" data-image-modal data-image-title="Ảnh đồng hồ điện bàn giao">
                                    <img src="{{ route('client.contracts.handover-meter-image', [$contract, 'electricity']) }}" alt="Ảnh đồng hồ điện bàn giao" class="h-48 w-full bg-white object-contain">
                                </a>
                            @endif
                            <div class="p-4"><p class="text-xs font-medium text-amber-700">Điện</p><p class="mt-1 text-xl font-bold text-amber-950">{{ $handoverReading->electricity_new }} kWh</p></div>
                        </div>
                        <div class="overflow-hidden rounded-xl border border-sky-200 bg-sky-50">
                            @if($handoverReading->meterImageExists('water'))
                                <a href="{{ route('client.contracts.handover-meter-image', [$contract, 'water']) }}" data-image-modal data-image-title="Ảnh đồng hồ nước bàn giao">
                                    <img src="{{ route('client.contracts.handover-meter-image', [$contract, 'water']) }}" alt="Ảnh đồng hồ nước bàn giao" class="h-48 w-full bg-white object-contain">
                                </a>
                            @endif
                            <div class="p-4"><p class="text-xs font-medium text-sky-700">Nước</p><p class="mt-1 text-xl font-bold text-sky-950">{{ $handoverReading->water_new }} m³</p></div>
                        </div>
                    </div>
                @else
                    <p class="mt-2 text-sm text-amber-700">Chưa có chỉ số bàn giao từ ban quản lý.</p>
                @endif
                <h4 class="mt-6 flex items-center gap-2 border-t border-slate-100 pt-5 text-sm font-bold text-slate-900"><i class="bx bx-grid-alt text-lg text-indigo-600"></i>Tiện nghi và dịch vụ</h4>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div class="flex items-center gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-indigo-600 shadow-sm"><i class="bx bx-wifi text-xl"></i></span><div><p class="text-sm font-bold text-indigo-950">Internet</p><p class="mt-0.5 text-xs text-indigo-700">{{ number_format((float) $setting->internet_fee, 0, ',', '.') }}đ/người/tháng</p></div></div>
                    <div class="flex items-center gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-indigo-600 shadow-sm"><i class="bx bx-home-heart text-xl"></i></span><div><p class="text-sm font-bold text-indigo-950">Dịch vụ chung</p><p class="mt-0.5 text-xs text-indigo-700">{{ number_format((float) $setting->service_fee, 0, ',', '.') }}đ/người/tháng</p></div></div>
                </div>
                <div class="mt-4 flex items-center justify-between gap-3"><p class="text-sm font-semibold text-slate-900">Phương tiện đã duyệt: {{ $approvedVehicles->count() }}</p><a href="{{ route('client.vehicles.index') }}" class="text-sm font-semibold text-indigo-700">Quản lý phương tiện</a></div>
                @if($approvedVehicles->isNotEmpty())<div class="mt-2 flex flex-wrap gap-2">@foreach($approvedVehicles as $vehicle)<span class="rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700">{{ $vehicleTypeLabels[$vehicle->vehicle_type] ?? 'Phương tiện' }} · {{ $vehicle->license_plate ?: 'Không có biển số' }} · {{ $vehicle->tenant->full_name }}</span>@endforeach</div>@endif
            </div>

            <div class="border-t border-slate-200">
                <div class="flex items-center justify-between gap-3 px-5 py-4 sm:px-6"><div><h4 class="font-bold text-slate-900">Tài sản bàn giao</h4><p class="mt-0.5 text-xs text-slate-500">Danh sách đã được chốt tại thời điểm nhận phòng</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $contract->handoverItems->count() }} tài sản</span></div>
                @if($contract->move_in_inventory_snapshotted_at)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Vật dụng / tài sản</th><th class="px-5 py-3">Số lượng</th><th class="px-5 py-3">Tình trạng</th><th class="px-5 py-3">Ghi chú</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($contract->handoverItems as $item)
                                    @php($roomAsset = $item->amenity_id ? $roomAssetsByAmenityId->get($item->amenity_id) : null)
                                    <tr><td class="px-5 py-3"><div class="flex items-center gap-3">@if($roomAsset?->pivot?->image_path)<a href="{{ route('client.room.assets.image', [$contract->room, $roomAsset]) }}" data-image-modal data-image-title="{{ $item->name }} · Phòng {{ $contract->room->room_code }}"><img src="{{ route('client.room.assets.image', [$contract->room, $roomAsset]) }}" alt="{{ $item->name }}" class="h-11 w-14 rounded-lg object-cover ring-1 ring-slate-200"></a>@else<span class="flex h-11 w-14 items-center justify-center rounded-lg bg-indigo-50 text-indigo-400"><i class="bx bx-package text-xl"></i></span>@endif<p class="font-semibold text-slate-900">{{ $item->name }}</p></div></td><td class="px-5 py-3 font-semibold text-slate-700">{{ $item->is_quantifiable ? $item->quantity : 'Có' }}</td><td class="px-5 py-3"><span class="inline-flex items-center gap-1 font-medium {{ $item->condition === 'damaged' ? 'text-rose-700' : 'text-emerald-700' }}"><i class="bx {{ $item->condition === 'damaged' ? 'bx-error-circle' : 'bx-check-circle' }} text-base"></i>{{ $conditionLabels[$item->condition] ?? 'Không xác định' }}</span></td><td class="px-5 py-3 text-slate-600">{{ $item->note ?: '—' }}</td></tr>
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

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50/60 p-5 sm:px-6">
                <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-violet-700"><i class="bx bx-group text-xl"></i></span><div><h3 class="font-bold text-slate-950">Người thuê trong phòng</h3><p class="mt-0.5 text-xs text-slate-500">Thành viên đang được ghi nhận trong hợp đồng</p></div></div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $currentOccupancy }}/{{ $contract->room->max_people }} người</span>
            </div>
            <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6">
                @forelse($contract->currentMembers as $member)
                    <div class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-indigo-200 hover:shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $member->full_name }}</p>
                                <p class="mt-1 text-xs font-medium {{ $member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE ? 'text-indigo-700' : 'text-slate-500' }}">{{ $member->role_label }}</p>
                                @if($member->review_note)<p class="mt-2 text-sm text-rose-700">Phản hồi: {{ $member->review_note }}</p>@endif
                            </div>
                            <div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $member->status_label }}</span>
                            </div>
                        </div>
                        @if($canConfirmMoveInDetails && ! $member->hasCompleteMoveInProfile())
                            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">Còn thiếu: {{ implode(', ', $member->missingMoveInProfileFields()) }}</p>
                        @endif
                        @if($member->role !== \App\Models\ContractTenant::ROLE_REPRESENTATIVE && in_array($member->status, [\App\Models\ContractTenant::STATUS_PENDING, \App\Models\ContractTenant::STATUS_APPROVED], true))
                            <div class="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                                @if($canConfirmMoveInDetails)
                                    <a href="{{ route('client.contracts.members.edit', [$contract, $member]) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-xs font-bold text-indigo-700 shadow-sm hover:border-indigo-300 hover:bg-indigo-100">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.5 5.5 4 4M5 19l1-4 9.5-9.5a2 2 0 0 1 3 3L9 18l-4 1Z" /></svg>
                                        {{ $member->hasCompleteMoveInProfile() ? 'Chỉnh sửa hồ sơ' : 'Bổ sung hồ sơ' }}
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('client.contracts.members.withdraw', [$contract, $member]) }}" data-confirm="Người này sẽ được rút khỏi danh sách nhận phòng." data-confirm-label="Xác nhận rút hồ sơ">
                                    @csrf
                                    <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-4 text-xs font-bold text-rose-700 hover:border-rose-300 hover:bg-rose-50 sm:w-auto">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7 8 14m0-7 7 7M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" /></svg>
                                        {{ $member->status === \App\Models\ContractTenant::STATUS_APPROVED ? 'Không nhận phòng' : 'Rút hồ sơ' }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Chưa có danh sách người thuê.</p>
                @endforelse
            </div>

        </section>

        @if($contract->actual_move_out_at && $contract->checkout_handover_confirmed_at)
            @php($checkoutConditionLabels = ['good' => 'Tốt', 'worn' => 'Hao mòn', 'damaged' => 'Hư hỏng', 'missing' => 'Thất lạc'])
            <section class="overflow-hidden rounded-xl border border-violet-200 bg-white shadow-sm"><div class="border-b border-violet-100 bg-violet-50/60 px-5 py-4"><h3 class="font-semibold text-slate-950">Biên bản bàn giao trả phòng</h3><p class="mt-1 text-xs text-slate-500">Đã đối chiếu lúc {{ $contract->checkout_handover_confirmed_at->format('H:i d/m/Y') }}</p></div>@if(filled($contract->checkout_asset_report))<div class="divide-y divide-slate-100">@foreach($contract->checkout_asset_report as $asset)<div class="flex justify-between gap-3 px-5 py-3 text-sm"><div><p class="font-semibold">{{ $asset['name'] }}</p><p class="text-xs text-slate-500">{{ $asset['note'] ?: 'Không có ghi chú' }}</p></div><span class="shrink-0 font-semibold text-slate-700">{{ $checkoutConditionLabels[$asset['condition']] ?? $asset['condition'] }}</span></div>@endforeach</div>@endif @if($contract->checkout_has_damage || filled($contract->checkout_damage_note))<p class="border-t border-slate-100 px-5 py-3 text-sm text-rose-700"><strong>Có hư hỏng/thất lạc:</strong> {{ $contract->checkout_damage_note }}</p>@else<p class="border-t border-slate-100 px-5 py-3 text-sm font-semibold text-emerald-700">Không ghi nhận hư hỏng hoặc thất lạc.</p>@endif @if(filled($contract->checkout_photo_paths))<div class="flex flex-wrap gap-2 border-t border-slate-100 px-5 py-4">@foreach($contract->checkout_photo_paths as $index => $path)<a href="{{ route('client.contracts.checkout-photos.show', [$contract, $index]) }}" data-image-modal data-image-title="Ảnh đồ vật hư hỏng {{ $index + 1 }}" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-700">Xem ảnh hư hỏng {{ $index + 1 }}</a>@endforeach</div>@endif</section>
        @endif

        @if($contract->settlementStatement)
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 p-5"><h3 class="font-semibold text-slate-950">Bảng quyết toán trả phòng</h3><p class="mt-1 text-sm text-slate-500">Các khoản được tính từ ngày sử dụng thực tế và chỉ số chốt khi bàn giao.</p></div>
                <div class="divide-y divide-slate-100">@foreach($contract->settlementStatement->items as $item)<div class="flex items-start justify-between gap-4 px-5 py-3 text-sm"><div><p class="font-medium text-slate-900">{{ $item->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $item->note }}</p></div><p class="shrink-0 font-semibold">{{ number_format((float)$item->amount,0,',','.') }}đ</p></div>@endforeach</div>
                <div class="grid gap-3 border-t border-slate-200 bg-slate-50 p-5 sm:grid-cols-2 lg:grid-cols-5"><div><p class="text-xs text-slate-500">Phí cuối kỳ</p><p class="font-bold">{{ number_format((float)$contract->settlementStatement->final_charge_amount,0,',','.') }}đ</p></div><div><p class="text-xs text-slate-500">Công nợ trước bù cọc</p><p class="font-bold">{{ number_format((float)$contract->settlementStatement->previous_outstanding_amount,0,',','.') }}đ</p></div><div><p class="text-xs text-slate-500">Cọc đã bù công nợ</p><p class="font-bold text-indigo-700">-{{ number_format((float)$contract->deposit_deduction_amount,0,',','.') }}đ</p></div><div><p class="text-xs text-slate-500">Cọc còn được hoàn</p><p class="font-bold text-emerald-700">{{ number_format((float)$contract->deposit_refund_amount,0,',','.') }}đ</p></div><div><p class="text-xs text-slate-500">Kết quả quyết toán</p><p class="font-bold {{ (float)$contract->settlementStatement->net_amount > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format(abs((float)$contract->settlementStatement->net_amount),0,',','.') }}đ {{ (float)$contract->settlementStatement->net_amount > 0 ? 'cần thanh toán' : ((float)$contract->settlementStatement->net_amount < 0 ? 'sẽ được hoàn' : 'đã cân bằng') }}</p></div></div>
                @if($contract->settlementStatement->invoice)<div class="border-t border-slate-200 p-5"><a href="{{ route('client.invoices.show',$contract->settlementStatement->invoice) }}" class="text-sm font-semibold text-indigo-700">Mở hóa đơn quyết toán →</a></div>@endif
            </section>
        @endif

        @if($contract->note)<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold">Ghi chú hợp đồng</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $contract->note }}</p></section>@endif
    </div>

@endsection
