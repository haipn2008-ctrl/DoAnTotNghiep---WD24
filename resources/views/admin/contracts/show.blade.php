@extends('layouts.admin.index')

@section('title', 'Chi tiết hợp đồng | Quản lý phòng trọ')
@section('page_title', 'Chi tiết hợp đồng')

@php
    $statusData = [
        'draft' => ['label' => 'Bản nháp', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'],
        'pending_signature' => ['label' => 'Chờ ký', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
        'pending_deposit' => ['label' => 'Chờ tiền cọc', 'class' => 'bg-orange-50 text-orange-700 ring-orange-200', 'dot' => 'bg-orange-500'],
        'awaiting_move_in' => ['label' => 'Chờ nhận phòng', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200', 'dot' => 'bg-sky-500'],
        'active' => ['label' => 'Đang hiệu lực', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'expired' => ['label' => 'Đã hết hạn', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        'settling' => ['label' => 'Đang quyết toán', 'class' => 'bg-violet-50 text-violet-700 ring-violet-200', 'dot' => 'bg-violet-500'],
        'completed' => ['label' => 'Đã hoàn tất', 'class' => 'bg-green-50 text-green-700 ring-green-200', 'dot' => 'bg-green-500'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-gray-50 text-gray-700 ring-gray-200', 'dot' => 'bg-gray-400'],
    ];
    $currentStatus = $statusData[$contract->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'];
    $statusLabels = collect($statusData)->mapWithKeys(fn ($item, $status) => [$status => $item['label']])->all();
    $actionLabels = [
        'create_draft' => 'Tạo bản nháp',
        'update_draft' => 'Cập nhật bản nháp',
        'submit_for_signature' => 'Gửi chờ ký',
        'return_to_draft' => 'Trả lại bản nháp',
        'mark_as_signed' => 'Xác nhận đã ký',
        'deposit_completed' => 'Xác nhận đã thu đủ tiền cọc',
        'deposit_reversed' => 'Điều chỉnh lại tiền cọc',
        'prepare_handover_reading' => 'Lập chỉ số bàn giao',
        'update_handover_reading' => 'Cập nhật chỉ số bàn giao',
        'confirm_move_in_details' => 'Xác nhận thông tin nhận phòng',
        'reopen_move_in_details' => 'Mở lại thông tin nhận phòng',
        'check_in' => 'Xác nhận nhận phòng',
        'extend_move_in_deadline' => 'Gia hạn thời gian giữ phòng',
        'cancel' => 'Hủy hợp đồng',
        'mark_expired' => 'Đánh dấu hết hạn',
        'check_out' => 'Xác nhận trả phòng',
        'complete_settlement' => 'Hoàn tất quyết toán',
        'extend_contract' => 'Gia hạn hợp đồng',
    ];
    $memberStatusLabels = [
        'pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối',
        'checked_in' => 'Đang ở', 'moved_out' => 'Đã rời phòng', 'withdrawn' => 'Đã rút khai báo',
    ];
    $roomStatusLabels = ['available' => 'Còn trống', 'occupied' => 'Đang có người thuê', 'maintenance' => 'Đang bảo trì', 'retired' => 'Ngừng khai thác'];
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
@endphp

@section('content')
@if($contract->status === \App\Models\Contract::STATUS_DRAFT)
    @include('admin.contracts.partials.draft-detail')
@else
<div class="space-y-4">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-medium text-slate-500">{{ $contract->contract_code }}</p>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $currentStatus['class'] }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $currentStatus['dot'] }}"></span>
                    {{ $currentStatus['label'] }}
                </span>
            </div>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Chi tiết hợp đồng</h2>
            <p class="mt-1 text-sm text-slate-500">Phòng {{ $contract->room?->room_code ?? 'Không có' }} · {{ $contract->tenant?->full_name ?? 'Chưa có khách thuê' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($contract->status === \App\Models\Contract::STATUS_ACTIVE)
                <a href="{{ route('admin.invoices.index', ['keyword' => $contract->contract_code]) }}" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50">
                    <i class="bx bx-receipt text-lg"></i>Xem hóa đơn
                </a>
                <a href="{{ route('admin.contracts.extend.form', $contract) }}" class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
                    <i class="bx bx-calendar-plus text-lg"></i>Gia hạn
                </a>
                <a href="{{ route('admin.contracts.check-out.form', $contract) }}" class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-700">
                    <i class="bx bx-log-out-circle text-lg"></i>Trả phòng
                </a>
            @endif
            <a data-contract-print href="{{ route('admin.contracts.print',$contract) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                <i class="bx bx-printer text-lg"></i>
                In hợp đồng
            </a>
            @if($contract->status === \App\Models\Contract::STATUS_PENDING_SIGNATURE)
                <button type="button" onclick="document.getElementById('edit-contract-dialog').showModal()" class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50">
                    <i class="bx bx-edit text-lg"></i>
                    Sửa hợp đồng
                </button>
            @endif
            @if($contract->status === \App\Models\Contract::STATUS_AWAITING_MOVE_IN)
                <button type="button" onclick="document.getElementById('extend-move-in-dialog').showModal()" class="inline-flex items-center gap-2 rounded-lg border border-sky-200 bg-white px-4 py-2.5 text-sm font-semibold text-sky-700 shadow-sm hover:bg-sky-50">
                    <i class="bx bx-calendar-plus text-lg"></i>
                    Gia hạn giữ phòng
                </button>
            @endif
            @if(in_array($contract->status, [\App\Models\Contract::STATUS_PENDING_SIGNATURE, \App\Models\Contract::STATUS_PENDING_DEPOSIT, \App\Models\Contract::STATUS_AWAITING_MOVE_IN], true))
                <button type="button" onclick="document.getElementById('cancel-contract-dialog').showModal()" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm hover:bg-rose-50">
                    <i class="bx bx-x-circle text-lg"></i>
                    Hủy hợp đồng
                </button>
            @endif
            <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i class="bx bx-arrow-back text-lg"></i>
                Quay lại
            </a>
        </div>
    </div>

    @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if($contract->approvedTerminationRequest && $contract->scheduled_move_out_at)
        <div class="rounded-lg border border-violet-200 bg-violet-50 p-4 text-sm text-violet-900">
            <p class="font-bold">Lịch bàn giao đã duyệt: {{ $contract->scheduled_move_out_at->format('H:i d/m/Y') }}</p>
            <p class="mt-1">{{ $contract->approvedTerminationRequest->type_label }} · Ngày kết thúc được duyệt {{ $contract->approvedTerminationRequest->approved_end_date?->format('d/m/Y') }}@if($contract->approvedTerminationRequest->admin_note) · {{ $contract->approvedTerminationRequest->admin_note }}@endif</p>
        </div>
    @endif
    @if($contract->isSignatureOverdue())<div class="rounded-lg border border-amber-300 bg-amber-50 p-4 font-semibold text-amber-900">Quá hạn ký hợp đồng.</div>@endif
    @if($contract->isDepositOverdue())<div class="rounded-lg border border-orange-300 bg-orange-50 p-4 font-semibold text-orange-900">Quá hạn đóng tiền cọc.</div>@endif
    @if($contract->isReservationOverdue())<div class="rounded-lg border border-rose-300 bg-rose-50 p-4 font-semibold text-rose-900">Quá hạn nhận phòng — hệ thống không tự hủy hợp đồng.</div>@endif
    @if($contract->status===\App\Models\Contract::STATUS_ACTIVE && $contract->end_date->isBetween(today(), today()->addMonthNoOverflow()) && $contract->lifecycleAlerts->isEmpty())<div class="rounded-lg border border-amber-300 bg-amber-50 p-4 font-semibold text-amber-900">Hợp đồng đang ở tháng cuối — cần trao đổi gia hạn hoặc chuẩn bị trả phòng.</div>@endif
    @if($contract->status===\App\Models\Contract::STATUS_EXPIRED)<div class="rounded-lg border border-rose-300 bg-rose-50 p-4 font-semibold text-rose-900">Hợp đồng đã hết hạn nhưng khách vẫn đang ở; phòng vẫn được ghi nhận là đang có người thuê.</div>@endif
    @foreach($contract->lifecycleAlerts as $alert)<div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm"><strong>{{ $alert->title }}</strong>@if(filled($alert->message) && !str_contains($alert->message, 'EndOfContractTestSeeder'))<span>: {{ $alert->message }}</span>@endif</div>@endforeach

    @include('admin.contracts.appendices._contract-section')

    @if($contract->status === \App\Models\Contract::STATUS_PENDING_SIGNATURE)
        @include('admin.contracts.partials.pending-signature-detail')
    @elseif($contract->status === \App\Models\Contract::STATUS_PENDING_DEPOSIT)
        @include('admin.contracts.partials.pending-deposit-detail')
    @elseif($contract->status === \App\Models\Contract::STATUS_AWAITING_MOVE_IN)
        @include('admin.contracts.partials.awaiting-move-in-detail')
    @elseif($contract->status === \App\Models\Contract::STATUS_ACTIVE)
        @include('admin.contracts.partials.active-detail')
    @else
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Trạng thái</p><span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><i class="bx bx-pulse text-xl"></i></span></div><p class="mt-4 font-bold text-slate-950">{{ $currentStatus['label'] }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Tiền cọc</p><p class="mt-4 text-xl font-bold text-slate-950">{{ number_format($contract->deposit_amount,0,',','.') }}đ</p><p class="mt-1 text-xs text-slate-500">Đã thu {{ number_format($depositPaid,0,',','.') }}đ</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Cọc còn thiếu</p><p class="mt-4 text-xl font-bold text-rose-700">{{ number_format($depositRemaining,0,',','.') }}đ</p></div>
    </div>

    @unless(in_array($contract->status, [\App\Models\Contract::STATUS_COMPLETED, \App\Models\Contract::STATUS_CANCELLED], true))
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Bước tiếp theo</h3></div>
        <div class="grid gap-4 p-5 lg:grid-cols-2">
            @if($contract->status===\App\Models\Contract::STATUS_DRAFT)
                <form class="lifecycle-form rounded-xl border border-amber-200 bg-amber-50/40 p-4" method="POST" action="{{ route('admin.contracts.submit-for-signature',$contract) }}">@csrf<h4 class="font-semibold text-slate-950">Gửi chờ ký</h4><textarea name="reason" rows="2" placeholder="Ghi chú (không bắt buộc)" class="mt-3 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-100"></textarea><button class="mt-3 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">Gửi chờ ký</button></form>
            @endif
            @if(in_array($contract->status,\App\Models\Contract::OPEN_OCCUPANCY_STATUSES,true))
                <form class="lifecycle-form rounded-xl border border-violet-200 bg-violet-50/40 p-4 lg:col-span-2" method="POST" action="{{ route('admin.contracts.check-out',$contract) }}" enctype="multipart/form-data">
                    @csrf
                    <div><h4 class="font-semibold text-slate-950">Biên bản bàn giao và xác nhận trả phòng</h4><p class="mt-1 text-xs text-slate-500">Chốt chỉ số, chìa khóa, tài sản và ảnh hiện trạng trước khi chuyển sang quyết toán.</p></div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="text-xs font-semibold text-slate-600">Thời điểm trả phòng<input type="datetime-local" name="actual_move_out_at" required class="mt-1 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm"></label>
                        <label class="text-xs font-semibold text-slate-600">Chỉ số điện cuối<input type="number" min="0" name="checkout_electricity" value="{{ $latestReading?->electricity_new }}" required class="mt-1 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm"></label>
                        <label class="text-xs font-semibold text-slate-600">Chỉ số nước cuối<input type="number" min="0" name="checkout_water" value="{{ $latestReading?->water_new }}" required class="mt-1 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm"></label>
                        <label class="text-xs font-semibold text-slate-600">Số chìa khóa đã trả<input type="number" min="0" max="100" name="checkout_key_count" value="0" required class="mt-1 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm"></label>
                    </div>
                    @if($contract->handoverItems->isNotEmpty())
                        <div class="mt-4 overflow-hidden rounded-xl border border-violet-100 bg-white"><div class="border-b border-violet-100 px-4 py-3 text-sm font-semibold text-slate-800">Đối chiếu tài sản bàn giao</div><div class="divide-y divide-slate-100">@foreach($contract->handoverItems as $item)<div class="grid gap-2 px-4 py-3 sm:grid-cols-[1fr_170px_1fr]"><div><p class="text-sm font-semibold text-slate-800">{{ $item->name }}</p><p class="text-xs text-slate-500">Số lượng: {{ $item->quantity }}</p></div><select name="asset_conditions[{{ $item->id }}][condition]" required class="h-10 rounded-lg border border-slate-200 px-2 text-sm"><option value="good">Tốt</option><option value="worn">Hao mòn</option><option value="damaged">Hư hỏng</option><option value="missing">Thất lạc</option></select><input name="asset_conditions[{{ $item->id }}][note]" maxlength="500" placeholder="Ghi chú tình trạng" class="h-10 rounded-lg border border-slate-200 px-3 text-sm"></div>@endforeach</div></div>
                    @endif
                    <div class="mt-3 grid gap-3 md:grid-cols-2"><textarea name="checkout_reason" rows="2" required placeholder="Lý do trả phòng" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></textarea><textarea name="checkout_damage_note" rows="2" placeholder="Mô tả hư hỏng/thất lạc nếu có" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></textarea><input type="number" min="0" name="settlement_amount" placeholder="Khoản bồi thường/điều chỉnh" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm"><input name="settlement_description" placeholder="Nội dung khoản bồi thường/điều chỉnh" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm"></div>
                    <label class="mt-3 block text-xs font-semibold text-slate-600">Ảnh hiện trạng khi bàn giao (tối đa 10 ảnh)<input type="file" name="checkout_photos[]" multiple accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                    <label class="mt-3 flex items-start gap-2 text-sm text-slate-700"><input type="checkbox" name="handover_confirmed" value="1" required class="mt-0.5 rounded border-slate-300 text-violet-600"><span>Xác nhận ban quản lý và người thuê đại diện đã cùng đối chiếu biên bản bàn giao.</span></label>
                    <button class="mt-3 rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700">Xác nhận trả phòng và lập quyết toán</button>
                </form>
                <div class="rounded-xl border border-sky-200 bg-sky-50/40 p-4"><h4 class="font-semibold text-slate-950">Gia hạn hợp đồng</h4><p class="mt-1 text-xs leading-5 text-slate-500">Cập nhật thời hạn và giá thuê sau khi hai bên đã thỏa thuận.</p><a href="{{ route('admin.contracts.extend.form', $contract) }}" class="mt-3 inline-flex rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Gia hạn hợp đồng</a></div>
            @endif
            @if($contract->status===\App\Models\Contract::STATUS_SETTLING)
                @include('admin.contracts.partials.departure-progress', ['progressClass' => 'lg:col-span-2', 'showCheckoutLink' => true])
                <div class="rounded-xl border border-violet-200 bg-violet-50/50 p-5 lg:col-span-2">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div><h4 class="font-bold text-slate-950">Quy trình trả phòng đang xử lý</h4><p class="mt-1 text-sm text-slate-600">Mở trang quy trình để xử lý công nợ, tiền cọc và hoàn tất hợp đồng trên cùng một giao diện.</p></div>
                        <a href="{{ route('admin.contracts.check-out.form', $contract) }}" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-violet-700 px-5 text-sm font-bold text-white hover:bg-violet-800"><span>Tiếp tục xử lý</span><i class="bx bx-right-arrow-alt text-xl"></i></a>
                    </div>
                </div>
            @endif
            @if(in_array($contract->status,[\App\Models\Contract::STATUS_DRAFT,\App\Models\Contract::STATUS_PENDING_SIGNATURE,\App\Models\Contract::STATUS_PENDING_DEPOSIT,\App\Models\Contract::STATUS_AWAITING_MOVE_IN],true))
                <form class="lifecycle-form rounded-lg border border-rose-200 bg-rose-50 p-4 lg:col-span-2" method="POST" action="{{ route('admin.contracts.cancel',$contract) }}">@csrf<h4 class="font-semibold text-rose-800">Hủy hợp đồng</h4><textarea name="cancel_reason" rows="2" required placeholder="Nhập lý do hủy" class="mt-3 w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"></textarea><button class="mt-3 inline-flex items-center gap-2 rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-800"><i class="bx bx-x-circle text-lg"></i>Hủy hợp đồng</button></form>
            @endif
        </div>
    </section>
    @endunless

    @if($contract->actual_move_out_at && $contract->checkout_handover_confirmed_at)
    @php($checkoutConditionLabels = ['good' => 'Tốt', 'worn' => 'Hao mòn', 'damaged' => 'Hư hỏng', 'missing' => 'Thất lạc'])
    <section class="overflow-hidden rounded-xl border border-violet-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-violet-100 bg-violet-50/50 px-5 py-4"><div><h3 class="font-semibold text-slate-950">Biên bản bàn giao trả phòng</h3><p class="mt-1 text-xs text-slate-500">Đối chiếu lúc {{ $contract->checkout_handover_confirmed_at->format('H:i d/m/Y') }} · Đã trả {{ $contract->checkout_key_count }} chìa khóa</p></div><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Hai bên đã đối chiếu</span></div>
        @if(filled($contract->checkout_asset_report))<div class="divide-y divide-slate-100">@foreach($contract->checkout_asset_report as $asset)<div class="flex flex-wrap items-start justify-between gap-3 px-5 py-3 text-sm"><div><p class="font-semibold text-slate-900">{{ $asset['name'] }}</p><p class="text-xs text-slate-500">{{ $asset['note'] ?: 'Không có ghi chú' }}</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $checkoutConditionLabels[$asset['condition']] ?? $asset['condition'] }}</span></div>@endforeach</div>@endif
        @if($contract->checkout_damage_note)<p class="border-t border-slate-100 px-5 py-3 text-sm text-rose-700"><strong>Hư hỏng/thất lạc:</strong> {{ $contract->checkout_damage_note }}</p>@endif
        @if(filled($contract->checkout_photo_paths))<div class="flex flex-wrap gap-2 border-t border-slate-100 px-5 py-4">@foreach($contract->checkout_photo_paths as $index => $path)<a href="{{ route('admin.contracts.checkout-photos.show', [$contract, $index]) }}" data-image-modal data-image-title="Ảnh bàn giao trả phòng {{ $index + 1 }}" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-700">Xem ảnh {{ $index + 1 }}</a>@endforeach</div>@endif
    </section>
    @endif

    @if($contract->settlementStatement)
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div><h3 class="font-semibold text-slate-950">Bảng quyết toán cuối hợp đồng</h3><p class="mt-1 text-xs text-slate-500">Tính lúc {{ $contract->settlementStatement->calculated_at?->format('H:i d/m/Y') }}</p></div>
            @if($contract->settlementStatement->invoice)<a href="{{ route('admin.invoices.show', $contract->settlementStatement->invoice) }}" class="text-sm font-semibold text-indigo-700">Mở hóa đơn quyết toán</a>@endif
        </div>
        <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Khoản mục</th><th class="px-5 py-3 text-right">Số lượng</th><th class="px-5 py-3 text-right">Đơn giá</th><th class="px-5 py-3 text-right">Thành tiền</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($contract->settlementStatement->items as $item)<tr><td class="px-5 py-3"><p class="font-medium text-slate-900">{{ $item->name }}</p><p class="text-xs text-slate-500">{{ $item->note }}</p></td><td class="px-5 py-3 text-right">{{ number_format((float)$item->quantity, 2, ',', '.') }} {{ $item->unit }}</td><td class="px-5 py-3 text-right">{{ number_format((float)$item->unit_price, 0, ',', '.') }}đ</td><td class="px-5 py-3 text-right font-semibold">{{ number_format((float)$item->amount, 0, ',', '.') }}đ</td></tr>@endforeach</tbody></table></div>
        <div class="grid gap-3 border-t border-slate-200 bg-slate-50 p-5 sm:grid-cols-2 lg:grid-cols-5"><div><p class="text-xs text-slate-500">Phí cuối kỳ</p><p class="font-bold">{{ number_format((float)$contract->settlementStatement->final_charge_amount,0,',','.') }}đ</p></div><div><p class="text-xs text-slate-500">Công nợ trước bù cọc</p><p class="font-bold">{{ number_format((float)$contract->settlementStatement->previous_outstanding_amount,0,',','.') }}đ</p></div><div><p class="text-xs text-slate-500">Cọc đã bù công nợ</p><p class="font-bold text-indigo-700">-{{ number_format((float)$contract->deposit_deduction_amount,0,',','.') }}đ</p></div><div><p class="text-xs text-slate-500">Cọc còn hoàn khách</p><p class="font-bold text-emerald-700">{{ number_format((float)$contract->deposit_refund_amount,0,',','.') }}đ</p></div><div><p class="text-xs text-slate-500">Kết quả quyết toán</p><p class="font-bold {{ (float)$contract->settlementStatement->net_amount > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format(abs((float)$contract->settlementStatement->net_amount),0,',','.') }}đ {{ (float)$contract->settlementStatement->net_amount > 0 ? 'khách cần trả' : ((float)$contract->settlementStatement->net_amount < 0 ? 'cần hoàn khách' : 'đã cân bằng') }}</p></div></div>
    </section>
    @endif

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Danh sách người thuê trong phòng</h3><p class="mt-1 text-xs text-slate-500">Mọi người đều là người thuê; chỉ người thuê đại diện có tài khoản và là đầu mối làm việc với quản lý.</p></div>
        <div class="grid gap-4 p-5 lg:grid-cols-2">
            @forelse($contract->currentMembers as $member)
                <article class="rounded-lg border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div><p class="font-semibold text-slate-950">{{ $member->full_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $member->role_label }}{{ $member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE ? ' · Có tài khoản liên hệ' : ' · Không cấp tài khoản riêng' }} · {{ $member->identity_number ?: 'Chưa có giấy tờ' }}</p>@if($member->identity_front_path && $member->identity_back_path)<p class="mt-2 flex gap-3 text-xs font-semibold"><a data-image-modal data-image-title="CCCD mặt trước - {{ $member->full_name }}" class="text-indigo-700" href="{{ route('admin.contract-tenants.identity-document', [$member, 'front']) }}">CCCD mặt trước</a><a data-image-modal data-image-title="CCCD mặt sau - {{ $member->full_name }}" class="text-indigo-700" href="{{ route('admin.contract-tenants.identity-document', [$member, 'back']) }}">CCCD mặt sau</a></p>@endif</div>
                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $member->status_label }}</span>
                    </div>
                    @if($member->review_note)<p class="mt-2 text-sm text-rose-700">Lý do: {{ $member->review_note }}</p>@endif
                    @if($member->status === \App\Models\ContractTenant::STATUS_PENDING)
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <form method="POST" action="{{ route('admin.contract-tenants.approve', $member) }}">@csrf<button class="w-full rounded-lg bg-emerald-600 px-3 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Chấp nhận</button></form>
                            <form method="POST" action="{{ route('admin.contract-tenants.reject', $member) }}" class="flex gap-2">@csrf<input name="reason" required maxlength="1000" placeholder="Lý do từ chối" class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"><button class="rounded-lg bg-rose-700 px-3 py-2.5 text-sm font-semibold text-white hover:bg-rose-800">Từ chối</button></form>
                        </div>
                    @elseif($member->status === \App\Models\ContractTenant::STATUS_CHECKED_IN)
                        <form method="POST" action="{{ route('admin.contract-tenants.move-out', $member) }}" class="mt-3 grid gap-2 sm:grid-cols-[1fr_1fr_auto]">@csrf<input type="datetime-local" name="actual_move_out_at" required class="h-10 rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"><input name="reason" required maxlength="1000" placeholder="Lý do rời phòng" class="h-10 rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"><button class="rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Xác nhận rời</button></form>
                    @endif
                    @if($member->histories->isNotEmpty())<details class="mt-3 text-xs text-slate-500"><summary class="cursor-pointer font-semibold">Lịch sử ({{ $member->histories->count() }})</summary><div class="mt-2 space-y-1">@foreach($member->histories as $history)<p>{{ $history->performed_at?->format('d/m/Y H:i') }} · {{ $history->from_status ? ($memberStatusLabels[$history->from_status] ?? 'Không xác định') : 'Khởi tạo' }} → {{ $memberStatusLabels[$history->to_status] ?? 'Không xác định' }} · {{ $history->performer?->name ?? 'Hệ thống' }}</p>@endforeach</div></details>@endif
                </article>
            @empty
                <p class="text-sm text-slate-500 lg:col-span-2">Chưa có hồ sơ cư trú.</p>
            @endforelse
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-start"><h3 class="font-semibold text-slate-950">Dịch vụ và tài sản bàn giao</h3>@if($contract->move_in_details_confirmed_at)<span class="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Đã xác nhận {{ $contract->move_in_details_confirmed_at->format('d/m/Y H:i') }}</span>@else<span class="w-fit rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Chưa xác nhận</span>@endif</div>
        <div class="grid gap-3 p-5 sm:grid-cols-3"><div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4"><p class="text-sm font-semibold text-indigo-950">Internet bắt buộc</p><p class="mt-1 text-xs text-indigo-700">{{ number_format((float) $setting->internet_fee, 0, ',', '.') }}đ/phòng/tháng</p></div><div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4"><p class="text-sm font-semibold text-emerald-950">Máy lạnh</p></div><div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4"><p class="text-sm font-semibold text-indigo-950">Dịch vụ chung bắt buộc</p><p class="mt-1 text-xs text-indigo-700">{{ number_format((float) $setting->service_fee, 0, ',', '.') }}đ/tháng</p></div></div>
        <div class="border-t border-slate-200 px-5 py-4"><div class="flex items-center justify-between gap-3"><h4 class="text-sm font-semibold text-slate-900">Phương tiện đã duyệt</h4><span class="text-xs font-semibold text-slate-500">{{ $approvedVehicles->count() }} xe</span></div>@if($approvedVehicles->isNotEmpty())<div class="mt-3 flex flex-wrap gap-2">@foreach($approvedVehicles as $vehicle)<span class="rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700">{{ $vehicleTypeLabels[$vehicle->vehicle_type] ?? 'Phương tiện' }} · {{ $vehicle->license_plate ?: 'Không có biển số' }} · {{ $vehicle->tenant->full_name }}</span>@endforeach</div>@else<p class="mt-2 text-sm text-slate-500">Chưa có phương tiện được duyệt.</p>@endif</div>
        <div class="overflow-x-auto border-t border-slate-200"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Tài sản</th><th class="px-5 py-3">Số lượng</th><th class="px-5 py-3">Tình trạng</th><th class="px-5 py-3">Ghi chú</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($contract->handoverItems as $item)<tr><td class="px-5 py-4 font-semibold text-slate-950">{{ $item->name }}</td><td class="px-5 py-4">{{ $item->is_quantifiable ? $item->quantity : 'Có' }}</td><td class="px-5 py-4">{{ $conditionLabels[$item->condition] ?? 'Không xác định' }}</td><td class="px-5 py-4 text-slate-600">{{ $item->note ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">Không có tài sản bàn giao được khai báo.</td></tr>@endforelse</tbody></table></div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-slate-950">Các mốc thời gian</h3><dl class="mt-3 divide-y divide-slate-100 text-sm">@foreach([
            'Ngày ký'=>$contract->signed_at?->format('d/m/Y H:i'),'Hạn ký'=>$contract->signature_due_at?->format('d/m/Y H:i'),'Ngày dự kiến nhận'=>$contract->scheduled_move_in_date?->format('d/m/Y'),'Hạn giữ phòng'=>$contract->reservation_expires_at?->format('d/m/Y H:i'),'Đã thống nhất lịch nhận'=>$contract->move_in_terms_confirmed_at ? $contract->move_in_terms_confirmed_at->format('d/m/Y H:i').' · '.($contract->moveInTermsConfirmer?->name ?? 'Quản trị viên') : null,'Khách xác nhận dịch vụ tính phí/tài sản'=>$contract->move_in_details_confirmed_at ? $contract->move_in_details_confirmed_at->format('d/m/Y H:i').' · '.($contract->moveInDetailsConfirmer?->name ?? 'Khách thuê') : null,'Nhận phòng thực tế'=>$contract->actual_move_in_at?->format('d/m/Y H:i'),'Trả phòng thực tế'=>$contract->actual_move_out_at?->format('d/m/Y H:i'),'Hoàn tất'=>$contract->completed_at?->format('d/m/Y H:i')
        ] as $label=>$value)<div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">{{ $label }}</dt><dd class="font-semibold">{{ $value ?: '—' }}</dd></div>@endforeach</dl></section>
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-slate-950">Phòng, kỳ thuê và tài chính</h3><dl class="mt-3 divide-y divide-slate-100 text-sm"><div class="flex justify-between py-2"><dt class="text-slate-500">Khoảng thuê</dt><dd class="font-semibold text-slate-950">{{ $contract->start_date->format('d/m/Y') }} – {{ $contract->end_date->format('d/m/Y') }}</dd></div><div class="flex justify-between py-2"><dt class="text-slate-500">Số người</dt><dd class="font-semibold text-slate-950">{{ $contract->number_of_people }}/{{ $contract->room?->max_people }}</dd></div><div class="flex justify-between py-2"><dt class="text-slate-500">Trạng thái phòng</dt><dd class="font-semibold text-slate-950">{{ $roomStatusLabels[$contract->room?->status] ?? 'Không xác định' }} · {{ $contract->room?->current_people }} người</dd></div><div class="flex justify-between py-2"><dt class="text-slate-500">Tổng hóa đơn</dt><dd class="font-semibold text-slate-950">{{ number_format($totalInvoiced,0,',','.') }}đ</dd></div><div class="flex justify-between py-2"><dt class="text-slate-500">Còn phải thu</dt><dd class="font-bold text-rose-700">{{ number_format($totalOutstanding,0,',','.') }}đ</dd></div><div class="flex justify-between py-2"><dt class="text-slate-500">Tiền cọc</dt><dd class="font-semibold text-slate-950">Giữ riêng để hoàn/khấu trừ khi quyết toán</dd></div></dl></section>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-slate-950">Chỉ số điện nước</h3><div class="mt-4 grid gap-3 md:grid-cols-4">@foreach([['Ban đầu',$baselineReading],['Bàn giao',$handoverReading],['Gần nhất',$latestReading],['Trả phòng',$checkoutReading]] as [$label,$reading])<div class="rounded-lg bg-slate-50 p-4"><p class="font-semibold">{{ $label }}</p><p class="mt-2 text-sm">{{ $reading?->record_date?->format('d/m/Y') ?? 'Chưa ghi' }}</p><p class="mt-1 text-sm">Điện {{ $reading?->electricity_new ?? '—' }} · Nước {{ $reading?->water_new ?? '—' }}</p></div>@endforeach</div></section>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Lịch sử hợp đồng</h3></div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Thời điểm</th><th class="px-5 py-3">Trạng thái</th><th class="px-5 py-3">Thao tác</th><th class="px-5 py-3">Người thực hiện</th><th class="px-5 py-3">Lý do</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($contract->statusHistories as $history)<tr><td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $history->performed_at?->format('d/m/Y H:i:s') }}</td><td class="whitespace-nowrap px-5 py-4"><span class="text-slate-600">{{ $history->from_status ? ($statusLabels[$history->from_status] ?? 'Không xác định') : 'Khởi tạo' }}</span> <span class="mx-1 text-slate-400">→</span> <span class="font-semibold text-slate-950">{{ $statusLabels[$history->to_status] ?? 'Không xác định' }}</span></td><td class="px-5 py-4 font-semibold text-slate-950">{{ $actionLabels[$history->action] ?? 'Cập nhật hợp đồng' }}</td><td class="px-5 py-4 text-slate-600">{{ $history->performer?->name ?? 'Hệ thống' }}</td><td class="px-5 py-4 text-slate-600">{{ $history->reason ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Chưa có lịch sử.</td></tr>@endforelse</tbody></table></div></section>
    @endif
</div>
@endif
@push('scripts')<script>document.querySelectorAll('.lifecycle-form').forEach(form=>form.addEventListener('submit',()=>{const button=form.querySelector('button[type="submit"],button:not([type])');if(button){button.disabled=true;button.textContent='Đang xử lý…';}}));</script>@endpush
@endsection
