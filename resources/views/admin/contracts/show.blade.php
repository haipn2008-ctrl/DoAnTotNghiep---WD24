@extends('layouts.admin.index')

@section('title', 'Chi tiết hợp đồng | Quản lý phòng trọ')
@section('page_title', 'Chi tiết hợp đồng')

@php
    $statusData = [
        'draft' => ['label' => 'Bản nháp', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'],
        'pending_signature' => ['label' => 'Chờ ký', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
        'pending_deposit' => ['label' => 'Chờ cọc và tiền tháng đầu', 'class' => 'bg-orange-50 text-orange-700 ring-orange-200', 'dot' => 'bg-orange-500'],
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
        'deposit_completed' => 'Xác nhận đã thu đủ cọc và tiền phòng tháng đầu',
        'deposit_reversed' => 'Điều chỉnh lại khoản thu ban đầu',
        'confirm_move_in_details' => 'Xác nhận thông tin nhận phòng',
        'check_in' => 'Xác nhận nhận phòng',
        'extend_move_in_deadline' => 'Gia hạn thời gian giữ phòng',
        'cancel' => 'Hủy hợp đồng',
        'mark_expired' => 'Đánh dấu hết hạn',
        'check_out' => 'Xác nhận trả phòng',
        'complete_settlement' => 'Hoàn tất quyết toán',
        'extend_contract' => 'Gia hạn hợp đồng',
    ];
    $occupantStatusLabels = [
        'pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối',
        'checked_in' => 'Đang ở', 'moved_out' => 'Đã rời phòng', 'withdrawn' => 'Đã rút khai báo',
        'non_resident' => 'Không cư trú',
    ];
    $roomStatusLabels = ['available' => 'Còn trống', 'occupied' => 'Đang có người ở', 'maintenance' => 'Đang bảo trì'];
    $conditionLabels = [
        'normal' => 'Sử dụng bình thường', 'good' => 'Sử dụng bình thường',
        'worn' => 'Sử dụng bình thường', 'damaged' => 'Có hư hỏng',
    ];
@endphp

@section('content')
@if($contract->status === \App\Models\Contract::STATUS_DRAFT)
    @include('admin.contracts.partials.draft-detail')
@else
<div class="space-y-6">
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
            <a target="_blank" href="{{ route('admin.contracts.print',$contract) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                <i class="bx bx-printer text-lg"></i>
                In hợp đồng
            </a>
            <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i class="bx bx-arrow-back text-lg"></i>
                Quay lại
            </a>
        </div>
    </div>

    @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if($contract->isSignatureOverdue())<div class="rounded-lg border border-amber-300 bg-amber-50 p-4 font-semibold text-amber-900">Quá hạn ký hợp đồng.</div>@endif
    @if($contract->isDepositOverdue())<div class="rounded-lg border border-orange-300 bg-orange-50 p-4 font-semibold text-orange-900">Quá hạn đóng cọc hoặc tiền phòng tháng đầu.</div>@endif
    @if($contract->isReservationOverdue())<div class="rounded-lg border border-rose-300 bg-rose-50 p-4 font-semibold text-rose-900">Quá hạn nhận phòng — hệ thống không tự hủy hợp đồng.</div>@endif
    @if($contract->status===\App\Models\Contract::STATUS_ACTIVE && $contract->end_date->isBetween(today(), today()->addDays(30)))<div class="rounded-lg border border-amber-300 bg-amber-50 p-4 font-semibold text-amber-900">Hợp đồng sắp hết hạn trong 30 ngày — cần trao đổi gia hạn hoặc chuẩn bị trả phòng.</div>@endif
    @if($contract->status===\App\Models\Contract::STATUS_EXPIRED)<div class="rounded-lg border border-rose-300 bg-rose-50 p-4 font-semibold text-rose-900">Hợp đồng đã hết hạn nhưng khách vẫn đang ở; phòng vẫn được ghi nhận là đang có người ở.</div>@endif
    @foreach($contract->lifecycleAlerts as $alert)<div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm"><strong>{{ $alert->title }}:</strong> {{ $alert->message }}</div>@endforeach

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Trạng thái</p><span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><i class="bx bx-pulse text-xl"></i></span></div><p class="mt-4 font-bold text-slate-950">{{ $currentStatus['label'] }}</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Tiền cọc</p><p class="mt-4 text-xl font-bold text-slate-950">{{ number_format($contract->deposit_amount,0,',','.') }}đ</p><p class="mt-1 text-xs text-slate-500">Đã thu {{ number_format($depositPaid,0,',','.') }}đ</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Tiền phòng tháng đầu</p><p class="mt-4 text-xl font-bold text-slate-950">{{ number_format($contract->first_month_rent_amount,0,',','.') }}đ</p><p class="mt-1 text-xs text-slate-500">{{ $contract->first_month_rent_days <= 5 ? 'Được miễn tiền phòng' : $contract->first_month_rent_days.' ngày' }} · đã thu {{ number_format($firstMonthPaid,0,',','.') }}đ</p></div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Tổng ban đầu còn thiếu</p><p class="mt-4 text-xl font-bold text-rose-700">{{ number_format($depositRemaining + $firstMonthRemaining,0,',','.') }}đ</p></div>
    </div>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Bước tiếp theo</h3><p class="mt-1 text-sm text-slate-500">Các thao tác phù hợp với trạng thái hiện tại của hợp đồng.</p></div>
        <div class="grid gap-4 p-5 lg:grid-cols-2">
            @if($contract->status===\App\Models\Contract::STATUS_DRAFT)
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.submit-for-signature',$contract) }}">@csrf<h4 class="font-semibold">Gửi chờ ký</h4><textarea name="reason" placeholder="Ghi chú (không bắt buộc)" class="mt-3 w-full rounded border p-2"></textarea><button class="mt-3 rounded bg-amber-600 px-4 py-2 text-white">Gửi chờ ký</button></form>
            @endif
            @if($contract->status===\App\Models\Contract::STATUS_PENDING_SIGNATURE)
                <form class="lifecycle-form rounded-lg border border-slate-200 p-4" method="POST" enctype="multipart/form-data" action="{{ route('admin.contracts.mark-signed',$contract) }}">@csrf<h4 class="font-semibold text-slate-950">Xác nhận đã ký</h4><label class="mt-3 block text-sm font-semibold text-slate-700">Thời điểm ký</label><input type="datetime-local" name="signed_at" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">@if($contract->tenant?->isOffline())<label class="mt-3 block text-sm font-semibold text-slate-700">Bản hợp đồng giấy đã ký *</label><input type="file" name="signed_contract_file" accept=".pdf,image/jpeg,image/png,image/webp" required class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-white text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2.5"><p class="mt-1 text-xs text-slate-500">Bắt buộc với khách offline; chấp nhận PDF, JPG, PNG hoặc WEBP, tối đa 10 MB.</p>@else<label class="mt-3 block text-sm font-semibold text-slate-700">Bản hợp đồng đã ký (không bắt buộc)</label><input type="file" name="signed_contract_file" accept=".pdf,image/jpeg,image/png,image/webp" class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-white text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2.5">@endif<textarea name="reason" rows="2" placeholder="Ghi chú (không bắt buộc)" class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></textarea><button class="mt-3 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700"><i class="bx bx-check-circle text-lg"></i>Xác nhận đã ký</button></form>
                <form class="lifecycle-form rounded-lg border border-slate-200 p-4" method="POST" action="{{ route('admin.contracts.return-to-draft',$contract) }}">@csrf<h4 class="font-semibold text-slate-950">Trả lại bản nháp</h4><label class="mt-3 block text-sm font-semibold text-slate-700">Lý do trả lại</label><textarea name="reason" rows="4" required placeholder="Nhập lý do" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></textarea><button class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="bx bx-undo text-lg"></i>Trả lại bản nháp</button></form>
                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600 lg:col-span-2"><strong class="text-slate-800">Chưa thể thu cọc, tiền tháng đầu hoặc nhận phòng:</strong> quản trị viên cần xác nhận thời điểm ký hợp lệ trước.</div>
            @endif
            @if($contract->status===\App\Models\Contract::STATUS_PENDING_DEPOSIT)
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.deposit-invoice.issue',$contract) }}">@csrf<h4 class="font-semibold">Cọc và tiền phòng tháng đầu</h4><p class="mt-2 text-sm text-slate-500">Phát hành hai hóa đơn riêng: cọc đủ một tháng và tiền phòng tháng đầu {{ $contract->first_month_rent_days <= 5 ? 'được miễn' : 'tính theo '.$contract->first_month_rent_days.' ngày' }}.</p><button class="mt-3 rounded bg-orange-600 px-4 py-2 text-white">Phát hành / mở hai hóa đơn</button></form>
                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600"><strong class="text-slate-800">Chưa thể nhận phòng:</strong> phải thanh toán đủ cọc {{ number_format($contract->deposit_amount,0,',','.') }}đ và tiền phòng tháng đầu {{ number_format($contract->first_month_rent_amount,0,',','.') }}đ.</div>
            @endif
            @if($contract->status===\App\Models\Contract::STATUS_AWAITING_MOVE_IN)
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.check-in',$contract) }}">@csrf<h4 class="font-semibold">Xác nhận nhận phòng</h4>@if(!$contract->move_in_details_confirmed_at)<p class="mt-2 rounded bg-amber-50 p-2 text-sm font-medium text-amber-800">{{ $contract->tenant?->isOffline() ? 'Khách offline: admin sẽ xác nhận biên bản giấy khi check-in.' : 'Đang chờ khách xem và xác nhận dịch vụ, tài sản bàn giao.' }}</p>@endif<div class="mt-3 grid gap-2 sm:grid-cols-2"><input type="datetime-local" name="actual_move_in_at" required class="h-10 rounded border px-2"><input type="number" min="0" name="handover_electricity" required placeholder="Điện bàn giao" class="h-10 rounded border px-2"><input type="number" min="0" name="handover_water" required placeholder="Nước bàn giao" class="h-10 rounded border px-2"><input name="schedule_variance_reason" placeholder="Lý do nếu sớm/muộn" class="h-10 rounded border px-2"></div><label class="mt-3 flex gap-2 text-sm"><input type="checkbox" name="handover_confirmed" value="1" required> Quản trị viên đã đối chiếu phiếu và xác nhận bàn giao thực tế</label><button @disabled(!$contract->move_in_details_confirmed_at && !$contract->tenant?->isOffline()) class="mt-3 rounded bg-emerald-600 px-4 py-2 text-white disabled:cursor-not-allowed disabled:bg-slate-300">Xác nhận nhận phòng</button></form>
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.extend-move-in-deadline',$contract) }}">@csrf<h4 class="font-semibold">Gia hạn giữ phòng</h4><input type="datetime-local" name="reservation_expires_at" required class="mt-3 h-10 w-full rounded border px-2"><textarea name="reason" required placeholder="Lý do bắt buộc" class="mt-2 w-full rounded border p-2"></textarea><button class="mt-3 rounded border px-4 py-2">Gia hạn thời gian giữ phòng</button></form>
            @endif
            @if(in_array($contract->status,\App\Models\Contract::OPEN_OCCUPANCY_STATUSES,true))
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.check-out',$contract) }}">@csrf<h4 class="font-semibold">Xác nhận trả phòng</h4><div class="mt-3 grid gap-2 sm:grid-cols-2"><input type="datetime-local" name="actual_move_out_at" required class="h-10 rounded border px-2"><input type="number" min="0" name="checkout_electricity" value="{{ $latestReading?->electricity_new }}" required placeholder="Điện cuối" class="h-10 rounded border px-2"><input type="number" min="0" name="checkout_water" value="{{ $latestReading?->water_new }}" required placeholder="Nước cuối" class="h-10 rounded border px-2"><input type="number" min="0" name="settlement_amount" placeholder="Phí hư hỏng/quyết toán" class="h-10 rounded border px-2"></div><textarea name="checkout_reason" required placeholder="Lý do trả phòng bắt buộc" class="mt-2 w-full rounded border p-2"></textarea><input name="settlement_description" placeholder="Mô tả khoản quyết toán nếu có" class="mt-2 h-10 w-full rounded border px-2"><button class="mt-3 rounded bg-violet-600 px-4 py-2 text-white">Xác nhận trả phòng</button></form>
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.extend',$contract) }}">@csrf<h4 class="font-semibold">Gia hạn hợp đồng</h4><input type="date" name="new_end_date" required class="mt-3 h-10 w-full rounded border px-2"><textarea name="reason" required placeholder="Lý do bắt buộc" class="mt-2 w-full rounded border p-2"></textarea><button class="mt-3 rounded border px-4 py-2">Gia hạn</button></form>
                <div class="rounded-lg border border-dashed p-4 text-sm text-slate-600 lg:col-span-2"><strong>Chưa thể hoàn tất:</strong> phải xác nhận trả phòng để ghi chỉ số cuối và chuyển sang quyết toán.</div>
            @endif
            @if($contract->status===\App\Models\Contract::STATUS_SETTLING)
                <div class="rounded-lg border border-dashed p-4 text-sm text-slate-600 lg:col-span-2"><strong>Điều kiện hoàn tất:</strong> không còn công nợ và phải ghi rõ tiền cọc đã hoàn, khấu trừ hoặc giữ lại.</div>
                <form class="lifecycle-form rounded-lg border p-4 lg:col-span-2" method="POST" action="{{ route('admin.contracts.complete-settlement',$contract) }}">@csrf<h4 class="font-semibold">Hoàn tất quyết toán</h4><div class="mt-3 grid gap-2 md:grid-cols-2"><select name="deposit_resolution" class="h-10 rounded border px-2"><option value="">Chọn xử lý cọc</option><option value="refunded">Đã hoàn cọc</option><option value="deducted">Đã khấu trừ</option><option value="retained">Giữ cọc có chứng từ</option></select><textarea name="settlement_note" placeholder="Ghi chú/chứng từ xử lý cọc" class="rounded border p-2"></textarea></div><label class="mt-3 flex gap-2 text-sm"><input type="checkbox" name="write_off_outstanding" value="1"> Xóa phần công nợ còn lại (quyền quản trị viên, bắt buộc có lý do)</label><textarea name="write_off_reason" placeholder="Lý do xóa nợ" class="mt-2 w-full rounded border p-2"></textarea><label class="mt-3 flex gap-2 text-sm"><input type="checkbox" name="confirm_complete" value="1" required> Tôi xác nhận đã kiểm tra toàn bộ quyết toán</label><button class="mt-3 rounded bg-emerald-700 px-4 py-2 text-white">Hoàn tất hợp đồng</button></form>
            @endif
            @if(in_array($contract->status,[\App\Models\Contract::STATUS_DRAFT,\App\Models\Contract::STATUS_PENDING_SIGNATURE,\App\Models\Contract::STATUS_PENDING_DEPOSIT,\App\Models\Contract::STATUS_AWAITING_MOVE_IN],true))
                <form class="lifecycle-form rounded-lg border border-rose-200 bg-rose-50 p-4 lg:col-span-2" method="POST" action="{{ route('admin.contracts.cancel',$contract) }}">@csrf<h4 class="font-semibold text-rose-800">Hủy hợp đồng</h4><p class="mt-1 text-sm text-rose-700">Thao tác này không xóa dữ liệu và toàn bộ lịch sử vẫn được lưu lại.</p><textarea name="cancel_reason" rows="2" required placeholder="Nhập lý do hủy" class="mt-3 w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"></textarea><button class="mt-3 inline-flex items-center gap-2 rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-800"><i class="bx bx-x-circle text-lg"></i>Hủy hợp đồng</button></form>
            @endif
            @if(in_array($contract->status,[\App\Models\Contract::STATUS_COMPLETED,\App\Models\Contract::STATUS_CANCELLED],true))<div class="rounded-lg border border-dashed p-5 text-slate-600">Trạng thái cuối không thể quay lại bước trước. Không có hành động vòng đời nào được phép.</div>@endif
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Người thuê và danh sách cư trú</h3><p class="mt-1 text-sm text-slate-500">Người đứng tên hợp đồng không mặc định là người ở. Hồ sơ cư trú được lưu độc lập và không bị xóa khỏi lịch sử.</p></div>
        <div class="grid gap-4 p-5 lg:grid-cols-2">
            @forelse($contract->occupants as $occupant)
                <article class="rounded-lg border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div><p class="font-semibold text-slate-950">{{ $occupant->full_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $occupant->role === \App\Models\ContractOccupant::ROLE_REPRESENTATIVE ? ($contract->representative_is_occupant ? 'Người đại diện · có cư trú' : 'Người đại diện · không cư trú') : 'Người ở' }} · {{ $occupant->identity_number ?: 'Chưa có giấy tờ' }}</p>@if($occupant->identity_front_path && $occupant->identity_back_path)<p class="mt-2 flex gap-3 text-xs font-semibold"><a target="_blank" class="text-indigo-700" href="{{ route('admin.contract-occupants.identity-document', [$occupant, 'front']) }}">CCCD mặt trước</a><a target="_blank" class="text-indigo-700" href="{{ route('admin.contract-occupants.identity-document', [$occupant, 'back']) }}">CCCD mặt sau</a></p>@endif</div>
                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $occupant->status_label }}</span>
                    </div>
                    @if($occupant->review_note)<p class="mt-2 text-sm text-rose-700">Lý do: {{ $occupant->review_note }}</p>@endif
                    @if($occupant->status === \App\Models\ContractOccupant::STATUS_PENDING)
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <form method="POST" action="{{ route('admin.contract-occupants.approve', $occupant) }}">@csrf<button class="w-full rounded bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">Chấp nhận</button></form>
                            <form method="POST" action="{{ route('admin.contract-occupants.reject', $occupant) }}" class="flex gap-2">@csrf<input name="reason" required maxlength="1000" placeholder="Lý do từ chối" class="min-w-0 flex-1 rounded border px-2 text-sm"><button class="rounded bg-rose-700 px-3 py-2 text-sm font-semibold text-white">Từ chối</button></form>
                        </div>
                    @elseif($occupant->status === \App\Models\ContractOccupant::STATUS_CHECKED_IN)
                        <form method="POST" action="{{ route('admin.contract-occupants.move-out', $occupant) }}" class="mt-3 grid gap-2 sm:grid-cols-[1fr_1fr_auto]">@csrf<input type="datetime-local" name="actual_move_out_at" required class="h-9 rounded border px-2 text-sm"><input name="reason" required maxlength="1000" placeholder="Lý do rời phòng" class="h-9 rounded border px-2 text-sm"><button class="rounded border px-3 text-sm font-semibold">Xác nhận rời</button></form>
                    @endif
                    @if($occupant->histories->isNotEmpty())<details class="mt-3 text-xs text-slate-500"><summary class="cursor-pointer font-semibold">Lịch sử ({{ $occupant->histories->count() }})</summary><div class="mt-2 space-y-1">@foreach($occupant->histories as $history)<p>{{ $history->performed_at?->format('d/m/Y H:i') }} · {{ $history->from_status ? ($occupantStatusLabels[$history->from_status] ?? 'Không xác định') : 'Khởi tạo' }} → {{ $occupantStatusLabels[$history->to_status] ?? 'Không xác định' }} · {{ $history->performer?->name ?? 'Hệ thống' }}</p>@endforeach</div></details>@endif
                </article>
            @empty
                <p class="text-sm text-slate-500 lg:col-span-2">Chưa có hồ sơ cư trú.</p>
            @endforelse
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-start"><div><h3 class="font-semibold text-slate-950">Tiện nghi, dịch vụ và tài sản bàn giao</h3><p class="mt-1 text-sm text-slate-500">Tài sản được chốt tại thời điểm phát hành hợp đồng; Wi-Fi và máy lạnh luôn nằm trong giá thuê.</p></div>@if($contract->move_in_details_confirmed_at)<span class="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Khách đã xác nhận {{ $contract->move_in_details_confirmed_at->format('d/m/Y H:i') }}</span>@else<span class="w-fit rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Khách chưa xác nhận</span>@endif</div>
        <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4"><div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4"><p class="text-sm font-semibold text-emerald-950">Wi-Fi và máy lạnh</p><p class="mt-1 text-xs text-emerald-700">Đã bao gồm, không tính phí riêng</p></div><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold">Dịch vụ chung</p><p class="mt-1 text-xs text-slate-600">{{ $contract->service_enabled ? 'Đã đăng ký tính phí' : 'Không đăng ký' }}</p></div><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold">Trông xe</p><p class="mt-1 text-xs text-slate-600">{{ $contract->parking_quantity > 0 ? ($contract->parking_vehicle_label ?? 'Xe máy').' × '.$contract->parking_quantity : 'Không đăng ký' }}</p></div></div>
        <div class="overflow-x-auto border-t border-slate-200"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Tài sản</th><th class="px-5 py-3">Số lượng</th><th class="px-5 py-3">Tình trạng</th><th class="px-5 py-3">Ghi chú</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($contract->handoverItems as $item)<tr><td class="px-5 py-4 font-semibold text-slate-950">{{ $item->name }}</td><td class="px-5 py-4">{{ $item->is_quantifiable ? $item->quantity : 'Có' }}</td><td class="px-5 py-4">{{ $conditionLabels[$item->condition] ?? 'Không xác định' }}</td><td class="px-5 py-4 text-slate-600">{{ $item->note ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">Không có tài sản bàn giao được khai báo.</td></tr>@endforelse</tbody></table></div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-slate-950">Các mốc thời gian</h3><dl class="mt-3 divide-y divide-slate-100 text-sm">@foreach([
            'Ngày ký'=>$contract->signed_at?->format('d/m/Y H:i'),'Hạn ký'=>$contract->signature_due_at?->format('d/m/Y H:i'),'Ngày dự kiến nhận'=>$contract->scheduled_move_in_date?->format('d/m/Y'),'Hạn giữ phòng'=>$contract->reservation_expires_at?->format('d/m/Y H:i'),'Đã thống nhất lịch nhận'=>$contract->move_in_terms_confirmed_at ? $contract->move_in_terms_confirmed_at->format('d/m/Y H:i').' · '.($contract->moveInTermsConfirmer?->name ?? 'Quản trị viên') : null,'Khách xác nhận dịch vụ tính phí/tài sản'=>$contract->move_in_details_confirmed_at ? $contract->move_in_details_confirmed_at->format('d/m/Y H:i').' · '.($contract->moveInDetailsConfirmer?->name ?? 'Khách thuê') : null,'Nhận phòng thực tế'=>$contract->actual_move_in_at?->format('d/m/Y H:i'),'Trả phòng thực tế'=>$contract->actual_move_out_at?->format('d/m/Y H:i'),'Hoàn tất'=>$contract->completed_at?->format('d/m/Y H:i')
        ] as $label=>$value)<div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">{{ $label }}</dt><dd class="font-semibold">{{ $value ?: '—' }}</dd></div>@endforeach</dl></section>
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-slate-950">Phòng, kỳ thuê và tài chính</h3><dl class="mt-3 divide-y divide-slate-100 text-sm"><div class="flex justify-between py-2"><dt class="text-slate-500">Khoảng thuê</dt><dd class="font-semibold text-slate-950">{{ $contract->start_date->format('d/m/Y') }} – {{ $contract->end_date->format('d/m/Y') }}</dd></div><div class="flex justify-between py-2"><dt class="text-slate-500">Số người</dt><dd class="font-semibold text-slate-950">{{ $contract->number_of_people }}/{{ $contract->room?->max_people }}</dd></div><div class="flex justify-between py-2"><dt class="text-slate-500">Trạng thái phòng</dt><dd class="font-semibold text-slate-950">{{ $roomStatusLabels[$contract->room?->status] ?? 'Không xác định' }} · {{ $contract->room?->current_people }} người</dd></div><div class="flex justify-between py-2"><dt class="text-slate-500">Tổng hóa đơn</dt><dd class="font-semibold text-slate-950">{{ number_format($totalInvoiced,0,',','.') }}đ</dd></div><div class="flex justify-between py-2"><dt class="text-slate-500">Còn phải thu</dt><dd class="font-bold text-rose-700">{{ number_format($totalOutstanding,0,',','.') }}đ</dd></div><div class="flex justify-between py-2"><dt class="text-slate-500">Tiền cọc</dt><dd class="font-semibold text-slate-950">Giữ riêng để hoàn/khấu trừ khi quyết toán</dd></div></dl></section>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-slate-950">Chỉ số điện nước</h3><div class="mt-4 grid gap-3 md:grid-cols-3">@foreach([['Bàn giao',$handoverReading],['Gần nhất',$latestReading],['Trả phòng',$checkoutReading]] as [$label,$reading])<div class="rounded-lg bg-slate-50 p-4"><p class="font-semibold">{{ $label }}</p><p class="mt-2 text-sm">{{ $reading?->record_date?->format('d/m/Y') ?? 'Chưa ghi' }}</p><p class="mt-1 text-sm">Điện {{ $reading?->electricity_new ?? '—' }} · Nước {{ $reading?->water_new ?? '—' }}</p></div>@endforeach</div></section>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Lịch sử trạng thái và người thao tác</h3><p class="mt-1 text-sm text-slate-500">Theo dõi các lần thay đổi trong suốt vòng đời hợp đồng.</p></div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-5 py-3">Thời điểm</th><th class="px-5 py-3">Thay đổi trạng thái</th><th class="px-5 py-3">Hành động</th><th class="px-5 py-3">Người thực hiện</th><th class="px-5 py-3">Lý do</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($contract->statusHistories as $history)<tr><td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $history->performed_at?->format('d/m/Y H:i:s') }}</td><td class="whitespace-nowrap px-5 py-4"><span class="text-slate-600">{{ $history->from_status ? ($statusLabels[$history->from_status] ?? 'Không xác định') : 'Khởi tạo' }}</span> <span class="mx-1 text-slate-400">→</span> <span class="font-semibold text-slate-950">{{ $statusLabels[$history->to_status] ?? 'Không xác định' }}</span></td><td class="px-5 py-4 font-semibold text-slate-950">{{ $actionLabels[$history->action] ?? 'Cập nhật hợp đồng' }}</td><td class="px-5 py-4 text-slate-600">{{ $history->performer?->name ?? 'Hệ thống' }}</td><td class="px-5 py-4 text-slate-600">{{ $history->reason ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Chưa có lịch sử.</td></tr>@endforelse</tbody></table></div></section>
</div>
@endif
@push('scripts')<script>document.querySelectorAll('.lifecycle-form').forEach(form=>form.addEventListener('submit',()=>{const button=form.querySelector('button[type="submit"],button:not([type])');if(button){button.disabled=true;button.textContent='Đang xử lý…';}}));</script>@endpush
@endsection
