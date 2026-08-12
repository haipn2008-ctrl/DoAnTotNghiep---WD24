@extends('layouts.admin.index')

@section('title', 'Chi tiết hợp đồng | Quản lý phòng trọ')
@section('page_title', 'Chi tiết hợp đồng')

@php
    $colors = [
        'draft'=>'slate','pending_signature'=>'amber','pending_deposit'=>'orange','awaiting_move_in'=>'sky',
        'active'=>'emerald','expired'=>'rose','settling'=>'violet','completed'=>'green','cancelled'=>'gray',
    ];
    $color = $colors[$contract->status] ?? 'slate';
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
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div><p class="text-sm text-slate-500">{{ $contract->contract_code }}</p><h2 class="text-2xl font-bold text-slate-950">{{ $contract->status_label }}</h2><p class="mt-1 text-sm text-slate-500">Phòng {{ $contract->room?->room_code }} · {{ $contract->tenant?->full_name }}</p></div>
        <div class="flex gap-2">@if($contract->status===\App\Models\Contract::STATUS_DRAFT)<a href="{{ route('admin.contracts.edit',$contract) }}" class="rounded-lg border px-4 py-2 text-sm font-semibold">Sửa bản nháp</a>@endif<a target="_blank" href="{{ route('admin.contracts.print',$contract) }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Bản in</a></div>
    </div>

    @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if($contract->isSignatureOverdue())<div class="rounded-lg border border-amber-300 bg-amber-50 p-4 font-semibold text-amber-900">Quá hạn ký hợp đồng.</div>@endif
    @if($contract->isDepositOverdue())<div class="rounded-lg border border-orange-300 bg-orange-50 p-4 font-semibold text-orange-900">Quá hạn đóng cọc.</div>@endif
    @if($contract->isReservationOverdue())<div class="rounded-lg border border-rose-300 bg-rose-50 p-4 font-semibold text-rose-900">Quá hạn nhận phòng — hệ thống không tự hủy hoặc xử lý cọc.</div>@endif
    @if($contract->status===\App\Models\Contract::STATUS_ACTIVE && $contract->end_date->isBetween(today(), today()->addDays(30)))<div class="rounded-lg border border-amber-300 bg-amber-50 p-4 font-semibold text-amber-900">Hợp đồng sắp hết hạn trong 30 ngày — cần trao đổi gia hạn hoặc chuẩn bị checkout.</div>@endif
    @if($contract->status===\App\Models\Contract::STATUS_EXPIRED)<div class="rounded-lg border border-rose-300 bg-rose-50 p-4 font-semibold text-rose-900">Hợp đồng đã hết hạn nhưng khách vẫn đang ở; phòng vẫn occupied.</div>@endif
    @foreach($contract->lifecycleAlerts as $alert)<div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm"><strong>{{ $alert->title }}:</strong> {{ $alert->message }}</div>@endforeach

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border bg-white p-4"><p class="text-xs uppercase text-slate-500">Trạng thái</p><p class="mt-2 font-bold text-{{ $color }}-700">{{ $contract->status_label }}</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs uppercase text-slate-500">Tiền cọc phải thu</p><p class="mt-2 font-bold">{{ number_format($contract->deposit_amount,0,',','.') }}đ</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs uppercase text-slate-500">Đã thu thành công</p><p class="mt-2 font-bold text-emerald-700">{{ number_format($depositPaid,0,',','.') }}đ</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs uppercase text-slate-500">Cọc còn thiếu</p><p class="mt-2 font-bold text-rose-700">{{ number_format($depositRemaining,0,',','.') }}đ</p></div>
    </div>

    <section class="rounded-xl border bg-white shadow-sm">
        <div class="border-b p-5"><h3 class="font-bold">Bước tiếp theo và hành động quản trị</h3><p class="text-sm text-slate-500">Backend kiểm tra lại toàn bộ điều kiện dù nút không hiển thị.</p></div>
        <div class="grid gap-4 p-5 lg:grid-cols-2">
            @if($contract->status===\App\Models\Contract::STATUS_DRAFT)
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.submit-for-signature',$contract) }}">@csrf<h4 class="font-semibold">Gửi chờ ký</h4><textarea name="reason" placeholder="Ghi chú (không bắt buộc)" class="mt-3 w-full rounded border p-2"></textarea><button class="mt-3 rounded bg-amber-600 px-4 py-2 text-white">Gửi chờ ký</button></form>
            @endif
            @if($contract->status===\App\Models\Contract::STATUS_PENDING_SIGNATURE)
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.mark-signed',$contract) }}">@csrf<h4 class="font-semibold">Xác nhận đã ký</h4><input type="datetime-local" name="signed_at" required class="mt-3 h-10 w-full rounded border px-2"><textarea name="reason" placeholder="Ghi chú" class="mt-2 w-full rounded border p-2"></textarea><button class="mt-3 rounded bg-emerald-600 px-4 py-2 text-white">Xác nhận ký</button></form>
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.return-to-draft',$contract) }}">@csrf<h4 class="font-semibold">Trả lại bản nháp</h4><textarea name="reason" required placeholder="Lý do bắt buộc" class="mt-3 w-full rounded border p-2"></textarea><button class="mt-3 rounded border px-4 py-2">Trả lại nháp</button></form>
                <div class="rounded-lg border border-dashed p-4 text-sm text-slate-600 lg:col-span-2"><strong>Thu cọc và check-in đang bị chặn:</strong> cần Admin xác nhận ngày ký hợp lệ trước.</div>
            @endif
            @if($contract->status===\App\Models\Contract::STATUS_PENDING_DEPOSIT)
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.deposit-invoice.issue',$contract) }}">@csrf<h4 class="font-semibold">Hóa đơn cọc</h4><p class="mt-2 text-sm text-slate-500">Chỉ tạo đúng một hóa đơn; thanh toán thành công đủ tiền mới mở bước nhận phòng.</p><button class="mt-3 rounded bg-orange-600 px-4 py-2 text-white">Phát hành / mở hóa đơn cọc</button></form>
                <div class="rounded-lg border border-dashed p-4 text-sm text-slate-600"><strong>Check-in đang bị chặn:</strong> cần tổng payment success đủ {{ number_format($contract->deposit_amount,0,',','.') }}đ. Payment pending/failed không được tính.</div>
            @endif
            @if($contract->status===\App\Models\Contract::STATUS_AWAITING_MOVE_IN)
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.check-in',$contract) }}">@csrf<h4 class="font-semibold">Xác nhận nhận phòng</h4>@if(!$contract->move_in_details_confirmed_at)<p class="mt-2 rounded bg-amber-50 p-2 text-sm font-medium text-amber-800">Đang chờ khách xem và xác nhận dịch vụ, tài sản bàn giao.</p>@endif<div class="mt-3 grid gap-2 sm:grid-cols-2"><input type="datetime-local" name="actual_move_in_at" required class="h-10 rounded border px-2"><input type="number" min="0" name="handover_electricity" required placeholder="Điện bàn giao" class="h-10 rounded border px-2"><input type="number" min="0" name="handover_water" required placeholder="Nước bàn giao" class="h-10 rounded border px-2"><input name="schedule_variance_reason" placeholder="Lý do nếu sớm/muộn" class="h-10 rounded border px-2"></div><label class="mt-3 flex gap-2 text-sm"><input type="checkbox" name="handover_confirmed" value="1" required> Admin đã đối chiếu phiếu và xác nhận bàn giao thực tế</label><button @disabled(!$contract->move_in_details_confirmed_at) class="mt-3 rounded bg-emerald-600 px-4 py-2 text-white disabled:cursor-not-allowed disabled:bg-slate-300">Check-in</button></form>
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.extend-move-in-deadline',$contract) }}">@csrf<h4 class="font-semibold">Gia hạn giữ phòng</h4><input type="datetime-local" name="reservation_expires_at" required class="mt-3 h-10 w-full rounded border px-2"><textarea name="reason" required placeholder="Lý do bắt buộc" class="mt-2 w-full rounded border p-2"></textarea><button class="mt-3 rounded border px-4 py-2">Gia hạn deadline</button></form>
            @endif
            @if(in_array($contract->status,\App\Models\Contract::OPEN_OCCUPANCY_STATUSES,true))
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.check-out',$contract) }}">@csrf<h4 class="font-semibold">Xác nhận trả phòng</h4><div class="mt-3 grid gap-2 sm:grid-cols-2"><input type="datetime-local" name="actual_move_out_at" required class="h-10 rounded border px-2"><input type="number" min="0" name="checkout_electricity" value="{{ $latestReading?->electricity_new }}" required placeholder="Điện cuối" class="h-10 rounded border px-2"><input type="number" min="0" name="checkout_water" value="{{ $latestReading?->water_new }}" required placeholder="Nước cuối" class="h-10 rounded border px-2"><input type="number" min="0" name="settlement_amount" placeholder="Phí hư hỏng/quyết toán" class="h-10 rounded border px-2"></div><textarea name="checkout_reason" required placeholder="Lý do trả phòng bắt buộc" class="mt-2 w-full rounded border p-2"></textarea><input name="settlement_description" placeholder="Mô tả khoản quyết toán nếu có" class="mt-2 h-10 w-full rounded border px-2"><button class="mt-3 rounded bg-violet-600 px-4 py-2 text-white">Checkout</button></form>
                <form class="lifecycle-form rounded-lg border p-4" method="POST" action="{{ route('admin.contracts.extend',$contract) }}">@csrf<h4 class="font-semibold">Gia hạn hợp đồng</h4><input type="date" name="new_end_date" required class="mt-3 h-10 w-full rounded border px-2"><textarea name="reason" required placeholder="Lý do bắt buộc" class="mt-2 w-full rounded border p-2"></textarea><button class="mt-3 rounded border px-4 py-2">Gia hạn</button></form>
                <div class="rounded-lg border border-dashed p-4 text-sm text-slate-600 lg:col-span-2"><strong>Hoàn tất đang bị chặn:</strong> phải checkout để ghi chỉ số cuối và chuyển sang quyết toán.</div>
            @endif
            @if($contract->status===\App\Models\Contract::STATUS_SETTLING)
                <div class="rounded-lg border border-dashed p-4 text-sm text-slate-600 lg:col-span-2"><strong>Điều kiện hoàn tất:</strong> không còn hóa đơn unpaid/partial (hoặc write-off có lý do) và tiền cọc phải được hoàn, khấu trừ hoặc giữ lại có chứng từ.</div>
                <form class="lifecycle-form rounded-lg border p-4 lg:col-span-2" method="POST" action="{{ route('admin.contracts.complete-settlement',$contract) }}">@csrf<h4 class="font-semibold">Hoàn tất quyết toán</h4><div class="mt-3 grid gap-2 md:grid-cols-2"><select name="deposit_resolution" class="h-10 rounded border px-2"><option value="">Chọn xử lý cọc</option><option value="refunded">Đã hoàn cọc</option><option value="deducted">Đã khấu trừ</option><option value="retained">Giữ cọc có chứng từ</option></select><textarea name="settlement_note" placeholder="Ghi chú/chứng từ xử lý cọc" class="rounded border p-2"></textarea></div><label class="mt-3 flex gap-2 text-sm"><input type="checkbox" name="write_off_outstanding" value="1"> Xóa phần công nợ còn lại (quyền admin, bắt buộc lý do)</label><textarea name="write_off_reason" placeholder="Lý do xóa nợ" class="mt-2 w-full rounded border p-2"></textarea><label class="mt-3 flex gap-2 text-sm"><input type="checkbox" name="confirm_complete" value="1" required> Tôi xác nhận đã kiểm tra toàn bộ quyết toán</label><button class="mt-3 rounded bg-emerald-700 px-4 py-2 text-white">Hoàn tất hợp đồng</button></form>
            @endif
            @if(in_array($contract->status,[\App\Models\Contract::STATUS_DRAFT,\App\Models\Contract::STATUS_PENDING_SIGNATURE,\App\Models\Contract::STATUS_PENDING_DEPOSIT,\App\Models\Contract::STATUS_AWAITING_MOVE_IN],true))
                <form class="lifecycle-form rounded-lg border border-rose-200 bg-rose-50 p-4" method="POST" action="{{ route('admin.contracts.cancel',$contract) }}">@csrf<h4 class="font-semibold text-rose-800">Hủy hợp đồng</h4><textarea name="cancel_reason" required placeholder="Lý do hủy bắt buộc" class="mt-3 w-full rounded border p-2"></textarea><button class="mt-3 rounded bg-rose-700 px-4 py-2 text-white">Hủy và giữ lịch sử</button></form>
            @endif
            @if(in_array($contract->status,[\App\Models\Contract::STATUS_COMPLETED,\App\Models\Contract::STATUS_CANCELLED],true))<div class="rounded-lg border border-dashed p-5 text-slate-600">Trạng thái cuối không thể quay lại bước trước. Không có hành động vòng đời nào được phép.</div>@endif
        </div>
    </section>

    <section class="rounded-xl border bg-white shadow-sm">
        <div class="border-b p-5"><h3 class="font-bold">Người thuê và danh sách cư trú</h3><p class="mt-1 text-sm text-slate-500">Người đứng tên hợp đồng không mặc định là người ở. Hồ sơ cư trú được lưu độc lập và không bị xóa khỏi lịch sử.</p></div>
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
                    @if($occupant->histories->isNotEmpty())<details class="mt-3 text-xs text-slate-500"><summary class="cursor-pointer font-semibold">Lịch sử ({{ $occupant->histories->count() }})</summary><div class="mt-2 space-y-1">@foreach($occupant->histories as $history)<p>{{ $history->performed_at?->format('d/m/Y H:i') }} · {{ $history->from_status ?: 'Khởi tạo' }} → {{ $history->to_status }} · {{ $history->performer?->name ?? 'Hệ thống' }}</p>@endforeach</div></details>@endif
                </article>
            @empty
                <p class="text-sm text-slate-500 lg:col-span-2">Chưa có hồ sơ cư trú.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-xl border bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b p-5 sm:flex-row sm:items-start"><div><h3 class="font-bold">Dịch vụ và tài sản bàn giao</h3><p class="mt-1 text-sm text-slate-500">Snapshot của hợp đồng, không đọc từ danh mục phòng đang thay đổi.</p></div>@if($contract->move_in_details_confirmed_at)<span class="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Khách đã xác nhận {{ $contract->move_in_details_confirmed_at->format('d/m/Y H:i') }}</span>@else<span class="w-fit rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Khách chưa xác nhận</span>@endif</div>
        <div class="grid gap-3 p-5 sm:grid-cols-3"><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold">Internet</p><p class="mt-1 text-xs text-slate-600">{{ $contract->internet_enabled ? 'Đã đăng ký' : 'Không đăng ký' }}</p></div><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold">Dịch vụ chung</p><p class="mt-1 text-xs text-slate-600">{{ $contract->service_enabled ? 'Đã đăng ký' : 'Không đăng ký' }}</p></div><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm font-semibold">Giữ xe</p><p class="mt-1 text-xs text-slate-600">{{ $contract->parking_quantity > 0 ? $contract->parking_quantity.' xe' : 'Không đăng ký' }}</p></div></div>
        <div class="overflow-x-auto border-t"><table class="min-w-full divide-y text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="p-3">Tài sản</th><th class="p-3">Số lượng</th><th class="p-3">Tình trạng</th><th class="p-3">Ghi chú</th></tr></thead><tbody class="divide-y">@forelse($contract->handoverItems as $item)<tr><td class="p-3 font-semibold">{{ $item->name }}</td><td class="p-3">{{ $item->is_quantifiable ? $item->quantity : 'Có' }}</td><td class="p-3">{{ $conditionLabels[$item->condition] ?? $item->condition }}</td><td class="p-3 text-slate-600">{{ $item->note ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="p-6 text-center text-slate-500">Không có tài sản bàn giao được khai báo.</td></tr>@endforelse</tbody></table></div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border bg-white p-5"><h3 class="font-bold">Các mốc thời gian</h3><dl class="mt-3 divide-y text-sm">@foreach([
            'Ngày ký'=>$contract->signed_at?->format('d/m/Y H:i'),'Hạn ký'=>$contract->signature_due_at?->format('d/m/Y H:i'),'Ngày dự kiến nhận'=>$contract->scheduled_move_in_date?->format('d/m/Y'),'Hạn giữ phòng'=>$contract->reservation_expires_at?->format('d/m/Y H:i'),'Đã thống nhất lịch nhận'=>$contract->move_in_terms_confirmed_at ? $contract->move_in_terms_confirmed_at->format('d/m/Y H:i').' · '.($contract->moveInTermsConfirmer?->name ?? 'Admin') : null,'Khách xác nhận dịch vụ/tài sản'=>$contract->move_in_details_confirmed_at ? $contract->move_in_details_confirmed_at->format('d/m/Y H:i').' · '.($contract->moveInDetailsConfirmer?->name ?? 'Khách thuê') : null,'Nhận phòng thực tế'=>$contract->actual_move_in_at?->format('d/m/Y H:i'),'Trả phòng thực tế'=>$contract->actual_move_out_at?->format('d/m/Y H:i'),'Hoàn tất'=>$contract->completed_at?->format('d/m/Y H:i')
        ] as $label=>$value)<div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">{{ $label }}</dt><dd class="font-semibold">{{ $value ?: '—' }}</dd></div>@endforeach</dl></section>
        <section class="rounded-xl border bg-white p-5"><h3 class="font-bold">Phòng, kỳ thuê và tài chính</h3><dl class="mt-3 divide-y text-sm"><div class="flex justify-between py-2"><dt>Khoảng thuê</dt><dd>{{ $contract->start_date->format('d/m/Y') }} – {{ $contract->end_date->format('d/m/Y') }}</dd></div><div class="flex justify-between py-2"><dt>Số người</dt><dd>{{ $contract->number_of_people }}/{{ $contract->room?->max_people }}</dd></div><div class="flex justify-between py-2"><dt>Trạng thái vật lý phòng</dt><dd>{{ $contract->room?->status }} · {{ $contract->room?->current_people }} người</dd></div><div class="flex justify-between py-2"><dt>Tổng hóa đơn</dt><dd>{{ number_format($totalInvoiced,0,',','.') }}đ</dd></div><div class="flex justify-between py-2"><dt>Còn phải thu</dt><dd class="font-bold text-rose-700">{{ number_format($totalOutstanding,0,',','.') }}đ</dd></div><div class="flex justify-between py-2"><dt>Xử lý cọc</dt><dd>{{ $contract->deposit_resolution ?: 'Chưa xử lý' }}</dd></div></dl></section>
    </div>

    <section class="rounded-xl border bg-white p-5"><h3 class="font-bold">Chỉ số điện nước</h3><div class="mt-4 grid gap-3 md:grid-cols-3">@foreach([['Bàn giao',$handoverReading],['Gần nhất',$latestReading],['Checkout',$checkoutReading]] as [$label,$reading])<div class="rounded-lg bg-slate-50 p-4"><p class="font-semibold">{{ $label }}</p><p class="mt-2 text-sm">{{ $reading?->record_date?->format('d/m/Y') ?? 'Chưa ghi' }}</p><p class="mt-1 text-sm">Điện {{ $reading?->electricity_new ?? '—' }} · Nước {{ $reading?->water_new ?? '—' }}</p></div>@endforeach</div></section>

    <section class="rounded-xl border bg-white"><div class="border-b p-5"><h3 class="font-bold">Lịch sử trạng thái và người thao tác</h3></div><div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50"><tr><th class="p-3 text-left">Thời điểm</th><th class="p-3 text-left">Từ → Đến</th><th class="p-3 text-left">Hành động</th><th class="p-3 text-left">Người thực hiện</th><th class="p-3 text-left">Lý do</th></tr></thead><tbody class="divide-y">@forelse($contract->statusHistories as $history)<tr><td class="p-3">{{ $history->performed_at?->format('d/m/Y H:i:s') }}</td><td class="p-3">{{ $history->from_status ?? 'Khởi tạo' }} → {{ $history->to_status }}</td><td class="p-3 font-semibold">{{ $history->action }}</td><td class="p-3">{{ $history->performer?->name ?? 'Hệ thống/migration' }}</td><td class="p-3">{{ $history->reason ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="p-6 text-center text-slate-500">Chưa có lịch sử.</td></tr>@endforelse</tbody></table></div></section>
</div>
@endif
@push('scripts')<script>document.querySelectorAll('.lifecycle-form').forEach(form=>form.addEventListener('submit',()=>{const button=form.querySelector('button[type="submit"],button:not([type])');if(button){button.disabled=true;button.textContent='Đang xử lý…';}}));</script>@endpush
@endsection
