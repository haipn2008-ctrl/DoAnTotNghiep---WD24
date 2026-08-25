@php
    $daysRemaining = (int) today()->diffInDays($contract->end_date->copy()->startOfDay(), false);
    $totalContractDays = max(1, (int) $contract->start_date->diffInDays($contract->end_date));
    $elapsedDays = max(0, min($totalContractDays, (int) $contract->start_date->diffInDays(today())));
    $termProgress = (int) round(($elapsedDays / $totalContractDays) * 100);
    $collectionProgress = $totalInvoiced > 0 ? min(100, (int) round(($totalPaid / $totalInvoiced) * 100)) : 0;
    $representative = $contract->currentMembers->firstWhere('role', \App\Models\ContractTenant::ROLE_REPRESENTATIVE);
    $otherMembers = $contract->currentMembers->where('role', '!=', \App\Models\ContractTenant::ROLE_REPRESENTATIVE);
@endphp

<section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm">
    <div class="border-b border-emerald-100 bg-emerald-50 px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 text-sm text-emerald-700">
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-800">Đang thuê</span>
                    <span>Phòng {{ $contract->room?->room_code }}</span>
                    <span>·</span>
                    <span>{{ $contract->currentMembers->count() }} người thuê</span>
                </div>
                <h3 class="mt-3 text-xl font-bold text-slate-950 sm:text-2xl">{{ $contract->tenant?->full_name }}</h3>
                <p class="mt-1 text-sm text-slate-600">Người thuê đại diện · {{ $contract->tenant?->phone ?: 'Chưa có số điện thoại' }} · {{ $contract->tenant?->user?->email ?: 'Chưa có email' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.invoices.index', ['keyword' => $contract->contract_code]) }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-emerald-200 bg-white px-4 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-100">
                    <i class="bx bx-receipt text-lg"></i>Xem hóa đơn
                </a>
                <a href="{{ route('admin.contracts.extend.form', $contract) }}" class="inline-flex h-11 items-center gap-2 rounded-xl bg-sky-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
                    <i class="bx bx-calendar-plus text-lg"></i>Gia hạn
                </a>
                <button type="button" onclick="document.getElementById('checkout-contract-dialog').showModal()" class="inline-flex h-11 items-center gap-2 rounded-xl border border-violet-700 bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-violet-700">
                    <i class="bx bx-log-out-circle text-lg"></i>Lập biên bản trả phòng
                </button>
            </div>
        </div>
    </div>

    <div class="grid divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-4">
        <div class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Giá thuê mỗi tháng</p>
            <p class="mt-2 text-xl font-bold text-slate-950">{{ number_format((float) $contract->monthly_rent, 0, ',', '.') }}đ</p>
            <p class="mt-1 text-xs text-slate-500">Chưa gồm điện, nước và dịch vụ</p>
        </div>
        <div class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Còn phải thu</p>
            <p class="mt-2 text-xl font-bold {{ $totalOutstanding > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format($totalOutstanding, 0, ',', '.') }}đ</p>
            <p class="mt-1 text-xs text-slate-500">Đã thu {{ number_format($totalPaid, 0, ',', '.') }}đ</p>
        </div>
        <div class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Thời hạn còn lại</p>
            <p class="mt-2 text-xl font-bold {{ $daysRemaining <= 30 ? 'text-amber-700' : 'text-slate-950' }}">{{ max(0, $daysRemaining) }} ngày</p>
            <p class="mt-1 text-xs text-slate-500">Đến {{ $contract->end_date->format('d/m/Y') }}</p>
        </div>
        <div class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tiền cọc đang giữ</p>
            <p class="mt-2 text-xl font-bold text-slate-950">{{ number_format((float) $contract->deposit_amount, 0, ',', '.') }}đ</p>
            <p class="mt-1 text-xs text-emerald-700">Đã thu đủ và giữ đến quyết toán</p>
        </div>
    </div>
</section>

<div id="contract-overview" class="scroll-mt-24 grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,.55fr)]">
    <div class="space-y-6">
        <section id="contract-tenants" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="font-bold text-slate-950">Người thuê trong phòng</h3><p class="mt-1 text-xs text-slate-500">Người đại diện là đầu mối duy nhất có tài khoản; các thành viên còn lại vẫn được ghi trên hợp đồng.</p></div>
                <span class="w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $contract->currentMembers->count() }}/{{ $contract->room?->max_people }} người</span>
            </div>

            @if($representative)
                <div class="border-b border-slate-100 bg-indigo-50/40 p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex min-w-0 gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 font-bold text-white">{{ mb_substr($representative->full_name, 0, 1) }}</span>
                            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><p class="font-bold text-slate-950">{{ $representative->full_name }}</p><span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-700">Người đại diện</span></div><p class="mt-1 text-sm text-slate-600">{{ $representative->phone ?: 'Chưa có số điện thoại' }} · CCCD {{ $representative->identity_number ?: 'chưa cập nhật' }}</p><p class="mt-1 text-xs text-slate-500">Có tài khoản đăng nhập và làm việc trực tiếp với ban quản lý.</p></div>
                        </div>
                        @if($representative->identity_front_path && $representative->identity_back_path)<div class="flex shrink-0 gap-2 text-xs font-semibold"><a target="_blank" class="rounded-lg border border-indigo-200 bg-white px-3 py-2 text-indigo-700" href="{{ route('admin.contract-tenants.identity-document', [$representative, 'front']) }}">CCCD trước</a><a target="_blank" class="rounded-lg border border-indigo-200 bg-white px-3 py-2 text-indigo-700" href="{{ route('admin.contract-tenants.identity-document', [$representative, 'back']) }}">CCCD sau</a></div>@endif
                    </div>
                </div>
            @endif

            <div class="divide-y divide-slate-100">
                @forelse($otherMembers as $member)
                    <article class="px-5 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><p class="font-semibold text-slate-900">{{ $member->full_name }}</p><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $member->status === \App\Models\ContractTenant::STATUS_PENDING ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">{{ $member->status_label }}</span></div><p class="mt-1 text-sm text-slate-500">{{ $member->phone ?: 'Chưa có số điện thoại' }} · CCCD {{ $member->identity_number ?: 'chưa cập nhật' }}</p></div>
                            @if($member->identity_front_path && $member->identity_back_path)<div class="flex shrink-0 gap-3 text-xs font-semibold"><a target="_blank" class="text-indigo-700" href="{{ route('admin.contract-tenants.identity-document', [$member, 'front']) }}">Mặt trước</a><a target="_blank" class="text-indigo-700" href="{{ route('admin.contract-tenants.identity-document', [$member, 'back']) }}">Mặt sau</a></div>@endif
                        </div>
                        @if($member->status === \App\Models\ContractTenant::STATUS_PENDING)
                            <div class="mt-3 grid gap-2 rounded-xl bg-amber-50 p-3 sm:grid-cols-[auto_1fr_auto]">
                                <form method="POST" action="{{ route('admin.contract-tenants.approve', $member) }}">@csrf<button class="h-10 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white">Duyệt người thuê</button></form>
                                <form method="POST" action="{{ route('admin.contract-tenants.reject', $member) }}" class="contents">@csrf<input name="reason" required maxlength="1000" placeholder="Lý do nếu từ chối" class="h-10 min-w-0 rounded-lg border border-amber-200 bg-white px-3 text-sm"><button class="h-10 rounded-lg border border-rose-200 bg-white px-4 text-sm font-semibold text-rose-700">Từ chối</button></form>
                            </div>
                        @elseif($member->status === \App\Models\ContractTenant::STATUS_CHECKED_IN)
                            <div class="mt-3 text-sm"><p class="text-xs font-semibold text-slate-500">Ghi nhận người này rời phòng riêng</p><form method="POST" action="{{ route('admin.contract-tenants.move-out', $member) }}" class="mt-2 grid gap-2 rounded-xl bg-slate-50 p-3 sm:grid-cols-[1fr_1fr_auto]">@csrf<input type="datetime-local" name="actual_move_out_at" required class="h-10 rounded-lg border border-slate-200 px-3 text-sm"><input name="reason" required maxlength="1000" placeholder="Lý do rời phòng" class="h-10 rounded-lg border border-slate-200 px-3 text-sm"><button class="rounded-lg bg-slate-800 px-4 text-sm font-semibold text-white">Xác nhận</button></form></div>
                        @endif
                    </article>
                @empty
                    <p class="px-5 py-6 text-sm text-slate-500">Hiện chỉ có người thuê đại diện trong phòng.</p>
                @endforelse
            </div>
        </section>

        <section id="contract-utilities" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-bold text-slate-950">Điện nước gần nhất</h3><p class="mt-1 text-xs text-slate-500">Đối chiếu nhanh với chỉ số bàn giao ban đầu.</p></div><span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">{{ $latestReading?->record_date?->format('d/m/Y') ?? 'Chưa ghi chỉ số' }}</span></div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><div class="flex items-center justify-between"><span class="text-sm font-semibold text-amber-900">Điện</span><i class="bx bx-bolt-circle text-2xl text-amber-600"></i></div><p class="mt-3 text-2xl font-bold text-amber-950">{{ $latestReading?->electricity_new ?? '—' }} <span class="text-sm font-medium">kWh</span></p><p class="mt-1 text-xs text-amber-700">Bàn giao: {{ $handoverReading?->electricity_new ?? '—' }}</p></div>
                <div class="rounded-xl border border-sky-200 bg-sky-50 p-4"><div class="flex items-center justify-between"><span class="text-sm font-semibold text-sky-900">Nước</span><i class="bx bx-water text-2xl text-sky-600"></i></div><p class="mt-3 text-2xl font-bold text-sky-950">{{ $latestReading?->water_new ?? '—' }} <span class="text-sm font-medium">m³</span></p><p class="mt-1 text-xs text-sky-700">Bàn giao: {{ $handoverReading?->water_new ?? '—' }}</p></div>
            </div>
        </section>

        <section id="contract-assets" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="px-5 py-4"><h3 class="font-bold text-slate-950">Dịch vụ, phương tiện và tài sản bàn giao</h3><p class="mt-1 text-xs text-slate-500">{{ $contract->handoverItems->count() }} tài sản · {{ $approvedVehicles->count() }} phương tiện đã duyệt</p></div>
            <div class="border-t border-slate-200">
                <div class="grid gap-3 p-5 sm:grid-cols-2"><div class="rounded-xl bg-indigo-50 p-4"><p class="font-semibold text-indigo-950">Internet</p><p class="mt-1 text-sm text-indigo-700">{{ number_format((float) $setting->internet_fee, 0, ',', '.') }}đ/phòng/tháng</p></div><div class="rounded-xl bg-emerald-50 p-4"><p class="font-semibold text-emerald-950">Dịch vụ chung</p><p class="mt-1 text-sm text-emerald-700">{{ number_format((float) $setting->service_fee, 0, ',', '.') }}đ/tháng</p></div></div>
                <div class="border-t border-slate-100 px-5 py-4"><p class="text-sm font-semibold text-slate-900">Phương tiện</p>@if($approvedVehicles->isNotEmpty())<div class="mt-2 flex flex-wrap gap-2">@foreach($approvedVehicles as $vehicle)<span class="rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700">{{ $vehicleTypeLabels[$vehicle->vehicle_type] ?? 'Phương tiện' }} · {{ $vehicle->license_plate ?: 'Không biển số' }}</span>@endforeach</div>@else<p class="mt-1 text-sm text-slate-500">Chưa có phương tiện được duyệt.</p>@endif</div>
                <div class="overflow-x-auto border-t border-slate-100"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Tài sản</th><th class="px-5 py-3">Số lượng</th><th class="px-5 py-3">Tình trạng</th><th class="px-5 py-3">Ghi chú</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($contract->handoverItems as $item)<tr><td class="px-5 py-3 font-semibold">{{ $item->name }}</td><td class="px-5 py-3">{{ $item->is_quantifiable ? $item->quantity : 'Có' }}</td><td class="px-5 py-3">{{ $conditionLabels[$item->condition] ?? 'Không xác định' }}</td><td class="px-5 py-3 text-slate-500">{{ $item->note ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-6 text-center text-slate-500">Không có tài sản bàn giao.</td></tr>@endforelse</tbody></table></div>
            </div>
        </section>
    </div>

    <aside class="space-y-5 xl:sticky xl:top-5 xl:self-start">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between"><h3 class="font-bold text-slate-950">Thu tiền hợp đồng</h3><span class="text-sm font-bold {{ $totalOutstanding > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $collectionProgress }}%</span></div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $totalOutstanding > 0 ? 'bg-indigo-600' : 'bg-emerald-500' }}" style="width: {{ $collectionProgress }}%"></div></div>
            <dl class="mt-4 divide-y divide-slate-100 text-sm"><div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">Đã xuất hóa đơn</dt><dd class="font-semibold">{{ number_format($totalInvoiced, 0, ',', '.') }}đ</dd></div><div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">Đã thanh toán</dt><dd class="font-semibold text-emerald-700">{{ number_format($totalPaid, 0, ',', '.') }}đ</dd></div><div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">Còn phải thu</dt><dd class="font-bold text-rose-700">{{ number_format($totalOutstanding, 0, ',', '.') }}đ</dd></div></dl>
            <a href="{{ route('admin.invoices.index', ['keyword' => $contract->contract_code]) }}" class="mt-4 flex h-10 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Mở danh sách hóa đơn</a>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-bold text-slate-950">Thời hạn hợp đồng</h3>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $termProgress }}%"></div></div>
            <div class="mt-3 flex justify-between text-xs text-slate-500"><span>{{ $contract->start_date->format('d/m/Y') }}</span><span>{{ $contract->end_date->format('d/m/Y') }}</span></div>
            <dl class="mt-4 divide-y divide-slate-100 text-sm"><div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">Phòng</dt><dd class="font-semibold">{{ $contract->room?->room_code }}</dd></div><div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">Số người</dt><dd class="font-semibold">{{ $contract->currentMembers->count() }}/{{ $contract->room?->max_people }}</dd></div><div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">Nhận phòng</dt><dd class="font-semibold">{{ $contract->actual_move_in_at?->format('d/m/Y H:i') ?: '—' }}</dd></div></dl>
        </section>

        <section class="rounded-2xl border border-sky-200 bg-sky-50 p-5">
            <h3 class="font-bold text-sky-950">Thao tác cuối hợp đồng</h3>
            <p class="mt-1 text-xs leading-5 text-sky-800">Chọn gia hạn nếu khách tiếp tục ở. Chỉ lập biên bản trả phòng khi hai bên đang bàn giao thực tế.</p>
            <div class="mt-4 grid gap-2"><a href="{{ route('admin.contracts.extend.form', $contract) }}" class="flex h-10 items-center justify-center rounded-lg bg-sky-700 text-sm font-semibold text-white">Lập phụ lục gia hạn</a><a href="{{ route('admin.termination-requests.index') }}" class="flex h-10 items-center justify-center rounded-lg border border-sky-200 bg-white text-sm font-semibold text-sky-700">Xem yêu cầu trả phòng</a><button type="button" onclick="document.getElementById('checkout-contract-dialog').showModal()" class="h-10 rounded-lg border border-violet-200 bg-violet-50 text-sm font-semibold text-violet-700">Lập biên bản trả phòng</button></div>
        </section>
    </aside>
</div>

<section id="contract-history" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="px-5 py-4"><h3 class="font-bold text-slate-950">Lịch sử hợp đồng</h3><p class="mt-1 text-xs text-slate-500">{{ $contract->statusHistories->count() }} lần cập nhật trạng thái</p></div>
    <div class="overflow-x-auto border-t border-slate-200"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Thời điểm</th><th class="px-5 py-3">Trạng thái</th><th class="px-5 py-3">Thao tác</th><th class="px-5 py-3">Người thực hiện</th><th class="px-5 py-3">Lý do</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($contract->statusHistories as $history)<tr><td class="whitespace-nowrap px-5 py-3 text-slate-500">{{ $history->performed_at?->format('d/m/Y H:i') }}</td><td class="whitespace-nowrap px-5 py-3">{{ $history->from_status ? ($statusLabels[$history->from_status] ?? 'Không xác định') : 'Khởi tạo' }} → <strong>{{ $statusLabels[$history->to_status] ?? 'Không xác định' }}</strong></td><td class="px-5 py-3 font-semibold">{{ $actionLabels[$history->action] ?? 'Cập nhật hợp đồng' }}</td><td class="px-5 py-3 text-slate-500">{{ $history->performer?->name ?? 'Hệ thống' }}</td><td class="px-5 py-3 text-slate-500">{{ $history->reason ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-6 text-center text-slate-500">Chưa có lịch sử.</td></tr>@endforelse</tbody></table></div>
</section>

<dialog id="checkout-contract-dialog" class="m-auto max-h-[92vh] w-[min(1120px,calc(100%-2rem))] overflow-hidden rounded-2xl bg-white p-0 shadow-2xl backdrop:bg-slate-950/60">
    <form class="lifecycle-form flex max-h-[92vh] flex-col" method="POST" action="{{ route('admin.contracts.check-out', $contract) }}" enctype="multipart/form-data">
        @csrf
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6"><div><p class="text-xs font-semibold uppercase tracking-wide text-violet-600">Phòng {{ $contract->room?->room_code }}</p><h3 class="mt-1 text-lg font-bold text-slate-950">Biên bản bàn giao trả phòng</h3><p class="mt-1 text-sm text-slate-500">Chốt chỉ số, chìa khóa và toàn bộ tài sản trước khi lập quyết toán.</p></div><button type="button" onclick="this.closest('dialog').close()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500 hover:bg-slate-200" aria-label="Đóng">×</button></div>
        <div class="overflow-y-auto p-5 sm:p-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="text-xs font-semibold text-slate-600">Thời điểm trả phòng<input type="datetime-local" name="actual_move_out_at" max="{{ now()->format('Y-m-d\TH:i') }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal"></label>
                <label class="text-xs font-semibold text-slate-600">Chỉ số điện cuối<input type="number" min="{{ $latestReading?->electricity_new ?? 0 }}" name="checkout_electricity" value="{{ $latestReading?->electricity_new }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal"></label>
                <label class="text-xs font-semibold text-slate-600">Chỉ số nước cuối<input type="number" min="{{ $latestReading?->water_new ?? 0 }}" name="checkout_water" value="{{ $latestReading?->water_new }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal"></label>
                <label class="text-xs font-semibold text-slate-600">Số chìa khóa đã trả<input type="number" min="0" max="100" name="checkout_key_count" value="0" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal"></label>
            </div>

            @if($contract->handoverItems->isNotEmpty())
                <div class="mt-5 overflow-hidden rounded-xl border border-slate-200"><div class="border-b border-slate-200 bg-slate-50 px-4 py-3"><h4 class="text-sm font-bold text-slate-900">Đối chiếu tài sản bàn giao</h4><p class="mt-0.5 text-xs text-slate-500">Phải ghi nhận tình trạng của tất cả tài sản.</p></div><div class="divide-y divide-slate-100">@foreach($contract->handoverItems as $item)<div class="grid gap-3 px-4 py-3 sm:grid-cols-[1fr_180px_1fr] sm:items-center"><div><p class="text-sm font-semibold text-slate-800">{{ $item->name }}</p><p class="text-xs text-slate-500">Số lượng: {{ $item->quantity }}</p></div><select name="asset_conditions[{{ $item->id }}][condition]" required class="h-10 rounded-lg border border-slate-200 px-2 text-sm"><option value="good">Tốt</option><option value="worn">Hao mòn</option><option value="damaged">Hư hỏng</option><option value="missing">Thất lạc</option></select><input name="asset_conditions[{{ $item->id }}][note]" maxlength="500" placeholder="Ghi chú tình trạng" class="h-10 rounded-lg border border-slate-200 px-3 text-sm"></div>@endforeach</div></div>
            @endif

            <div class="mt-5 grid gap-4 md:grid-cols-2"><label class="text-xs font-semibold text-slate-600">Lý do trả phòng<textarea name="checkout_reason" rows="3" required placeholder="Ví dụ: Kết thúc hợp đồng đúng hạn" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-normal"></textarea></label><label class="text-xs font-semibold text-slate-600">Hư hỏng hoặc thất lạc<textarea name="checkout_damage_note" rows="3" placeholder="Chỉ nhập khi có hư hỏng/thất lạc" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-normal"></textarea></label><label class="text-xs font-semibold text-slate-600">Khoản bồi thường/điều chỉnh<input type="number" min="0" name="settlement_amount" placeholder="0" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal"></label><label class="text-xs font-semibold text-slate-600">Nội dung khoản điều chỉnh<input name="settlement_description" placeholder="Bắt buộc nếu có khoản điều chỉnh" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal"></label></div>
            <label class="mt-4 block text-xs font-semibold text-slate-600">Ảnh hiện trạng (tối đa 10 ảnh)<input type="file" name="checkout_photos[]" multiple accept="image/jpeg,image/png,image/webp" class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-normal"></label>
            <label class="mt-5 flex items-start gap-3 rounded-xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-950"><input type="checkbox" name="handover_confirmed" value="1" required class="mt-0.5 rounded border-violet-300 text-violet-600"><span><strong>Xác nhận biên bản:</strong> Ban quản lý và người thuê đại diện đã cùng đối chiếu chỉ số, chìa khóa và tài sản.</span></label>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:px-6"><button type="button" onclick="this.closest('dialog').close()" class="h-11 rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700">Đóng</button><button type="submit" class="h-11 rounded-lg border border-violet-700 bg-violet-600 px-5 text-sm font-semibold text-white hover:bg-violet-700">Xác nhận trả phòng và lập quyết toán</button></div>
    </form>
</dialog>
