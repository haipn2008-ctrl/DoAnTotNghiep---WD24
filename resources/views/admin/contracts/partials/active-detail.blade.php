@php
    $daysRemaining = (int) today()->diffInDays($contract->end_date->copy()->startOfDay(), false);
    $totalContractDays = max(1, (int) $contract->start_date->diffInDays($contract->end_date));
    $elapsedDays = max(0, min($totalContractDays, (int) $contract->start_date->diffInDays(today())));
    $termProgress = (int) round(($elapsedDays / $totalContractDays) * 100);
    $collectionProgress = $totalInvoiced > 0 ? min(100, (int) round(($totalPaid / $totalInvoiced) * 100)) : 0;
    $representative = $contract->currentMembers->firstWhere('role', \App\Models\ContractTenant::ROLE_REPRESENTATIVE);
    $otherMembers = $contract->currentMembers->where('role', '!=', \App\Models\ContractTenant::ROLE_REPRESENTATIVE);
    $departedMembers = $contract->members
        ->where('role', \App\Models\ContractTenant::ROLE_TENANT)
        ->where('status', \App\Models\ContractTenant::STATUS_MOVED_OUT)
        ->sortByDesc('actual_move_out_at');
@endphp

<section class="overflow-hidden rounded-xl border border-emerald-200 bg-white shadow-sm">
    <div class="border-b border-emerald-100 bg-emerald-50 px-5 py-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 text-sm text-emerald-700">
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-800">Đang thuê</span>
                    <span>Phòng {{ $contract->room?->room_code }}</span>
                    <span>·</span>
                    <span>{{ $contract->currentMembers->count() }} người thuê</span>
                </div>
                <h3 class="mt-2 text-xl font-bold text-slate-950">{{ $contract->tenant?->full_name }}</h3>
                <p class="mt-1 text-sm text-slate-600">Người thuê đại diện · {{ $contract->tenant?->phone ?: 'Chưa có số điện thoại' }} · {{ $contract->tenant?->user?->email ?: 'Chưa có email' }}</p>
            </div>
        </div>
    </div>

    <div class="grid divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-4">
        <div class="p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Giá thuê mỗi tháng</p>
            <p class="mt-2 text-xl font-bold text-slate-950">{{ number_format((float) $contract->monthly_rent, 0, ',', '.') }}đ</p>
        </div>
        <div class="p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Còn phải thu</p>
            <p class="mt-2 text-xl font-bold {{ $totalOutstanding > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format($totalOutstanding, 0, ',', '.') }}đ</p>
        </div>
        <div class="p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Thời hạn còn lại</p>
            <p class="mt-2 text-xl font-bold {{ $daysRemaining <= 30 ? 'text-amber-700' : 'text-slate-950' }}">{{ max(0, $daysRemaining) }} ngày</p>
        </div>
        <div class="p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tiền cọc đang giữ</p>
            <p class="mt-2 text-xl font-bold text-slate-950">{{ number_format((float) $contract->deposit_amount, 0, ',', '.') }}đ</p>
        </div>
    </div>
</section>

<div id="contract-overview" class="scroll-mt-24 grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,.55fr)]">
    <div class="space-y-4">
        <section id="contract-tenants" class="scroll-mt-24 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="font-bold text-slate-950">Người thuê trong phòng</h3>
                <div class="flex flex-wrap items-center gap-2">
                    @if($departedMembers->isNotEmpty())
                        <button type="button" onclick="document.getElementById('departure-history-dialog').showModal()" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                            <i class="bx bx-history text-base"></i>
                            Lịch sử rời phòng
                        </button>
                    @endif
                    <span class="w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $contract->currentMembers->count() }}/{{ $contract->room?->max_people }} người</span>
                </div>
            </div>

            @if($representative)
                <div class="border-b border-slate-100 p-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex min-w-0 gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 font-bold text-white">{{ mb_substr($representative->full_name, 0, 1) }}</span>
                            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><p class="font-bold text-slate-950">{{ $representative->full_name }}</p><span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-700">Người đại diện</span></div><p class="mt-1 text-sm text-slate-600">{{ $representative->phone ?: 'Chưa có số điện thoại' }} · CCCD {{ $representative->identity_number ?: 'chưa cập nhật' }}</p></div>
                        </div>
                        @if($representative->identity_front_path && $representative->identity_back_path)<div class="flex shrink-0 gap-2 text-xs font-semibold"><a data-image-modal data-image-title="CCCD mặt trước - {{ $representative->full_name }}" class="rounded-lg border border-indigo-200 bg-white px-3 py-2 text-indigo-700" href="{{ route('admin.contract-tenants.identity-document', [$representative, 'front']) }}">CCCD trước</a><a data-image-modal data-image-title="CCCD mặt sau - {{ $representative->full_name }}" class="rounded-lg border border-indigo-200 bg-white px-3 py-2 text-indigo-700" href="{{ route('admin.contract-tenants.identity-document', [$representative, 'back']) }}">CCCD sau</a></div>@endif
                    </div>

                    @if($otherMembers->where('status', \App\Models\ContractTenant::STATUS_CHECKED_IN)->isNotEmpty())
                        <details class="mt-4 rounded-xl border border-indigo-200 bg-white p-4">
                            <summary class="cursor-pointer text-sm font-semibold text-indigo-700">Ghi nhận đại diện rời phòng và chuyển người đại diện</summary>
                            <form method="POST" action="{{ route('admin.contract-tenants.transfer-representative', $representative) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                                @csrf
                                <label class="text-xs font-semibold text-slate-600">Người đại diện mới
                                    <select name="successor_member_id" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-normal">
                                        <option value="">Chọn người đang thuê</option>
                                        @foreach($otherMembers->where('status', \App\Models\ContractTenant::STATUS_CHECKED_IN) as $candidate)
                                            <option value="{{ $candidate->id }}" @selected(old('successor_member_id') == $candidate->id)>{{ $candidate->full_name }} · {{ $candidate->phone }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="text-xs font-semibold text-slate-600">Thời điểm chuyển giao
                                    <input type="datetime-local" name="effective_at" value="{{ old('effective_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
                                </label>
                                <label class="text-xs font-semibold text-slate-600 sm:col-span-2">Lý do đại diện cũ rời phòng
                                    <input name="reason" value="{{ old('reason') }}" required maxlength="1000" placeholder="Nhập lý do chuyển giao" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
                                </label>
                                <label class="text-xs font-semibold text-slate-600 sm:col-span-2">Email đăng nhập của đại diện mới
                                    <input type="email" name="email" value="{{ old('email') }}" required autocomplete="off" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
                                </label>
                                <label class="text-xs font-semibold text-slate-600">Mật khẩu tạm
                                    <input type="password" name="temporary_password" required minlength="8" autocomplete="new-password" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
                                </label>
                                <label class="text-xs font-semibold text-slate-600">Nhập lại mật khẩu tạm
                                    <input type="password" name="temporary_password_confirmation" required minlength="8" autocomplete="new-password" class="mt-1.5 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm font-normal">
                                </label>
                                <div class="rounded-lg bg-amber-50 p-3 text-xs leading-5 text-amber-900 sm:col-span-2">Thao tác này đồng thời ghi nhận đại diện cũ rời phòng, vô hiệu hóa tài khoản cũ, cấp tài khoản mới và lập phụ lục chuyển giao. Hợp đồng gốc không bị sửa nội dung.</div>
                                <div class="sm:col-span-2"><button class="h-11 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">Chuyển đại diện và ghi nhận rời phòng</button></div>
                            </form>
                        </details>
                    @endif
                </div>
            @endif

            @if($contract->representativeTransfers->isNotEmpty())
                <div class="border-b border-slate-100 bg-emerald-50/50 px-5 py-4">
                    <p class="text-sm font-bold text-emerald-950">Phụ lục chuyển giao người đại diện</p>
                    <div class="mt-2 space-y-2">
                        @foreach($contract->representativeTransfers->sortByDesc('effective_at') as $transfer)
                            <div class="flex flex-col gap-2 rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm sm:flex-row sm:items-center sm:justify-between">
                                <span>{{ data_get($transfer->old_representative_snapshot, 'full_name') }} → {{ data_get($transfer->new_representative_snapshot, 'full_name') }} · {{ $transfer->effective_at->format('d/m/Y H:i') }}</span>
                                <a target="_blank" href="{{ route('admin.representative-transfers.appendix', $transfer) }}" class="font-semibold text-emerald-700 hover:text-emerald-900">In phụ lục chuyển giao</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="divide-y divide-slate-100">
                @forelse($otherMembers as $member)
                    <article class="px-5 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><p class="font-semibold text-slate-900">{{ $member->full_name }}</p><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $member->status === \App\Models\ContractTenant::STATUS_PENDING ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">{{ $member->status_label }}</span></div><p class="mt-1 text-sm text-slate-500">{{ $member->phone ?: 'Chưa có số điện thoại' }} · CCCD {{ $member->identity_number ?: 'chưa cập nhật' }}</p></div>
                            @if($member->identity_front_path && $member->identity_back_path)<div class="flex shrink-0 gap-3 text-xs font-semibold"><a data-image-modal data-image-title="CCCD mặt trước - {{ $member->full_name }}" class="text-indigo-700" href="{{ route('admin.contract-tenants.identity-document', [$member, 'front']) }}">Mặt trước</a><a data-image-modal data-image-title="CCCD mặt sau - {{ $member->full_name }}" class="text-indigo-700" href="{{ route('admin.contract-tenants.identity-document', [$member, 'back']) }}">Mặt sau</a></div>@endif
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
                @endforelse
            </div>

        </section>

        @if($departedMembers->isNotEmpty())
            <dialog id="departure-history-dialog" class="m-auto w-[calc(100%-2rem)] max-w-2xl overflow-hidden rounded-2xl bg-white p-0 text-slate-700 shadow-2xl backdrop:bg-slate-900/50">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="font-bold text-slate-950">Lịch sử rời phòng</h3>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Các lần rời phòng vẫn được lưu để đối chiếu. Chỉ khôi phục khi admin đã nhập nhầm người hoặc ngày rời.</p>
                    </div>
                    <button type="button" onclick="this.closest('dialog').close()" aria-label="Đóng" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i class="bx bx-x text-xl"></i>
                    </button>
                </div>

                <div class="max-h-[70vh] divide-y divide-slate-100 overflow-y-auto">
                    @foreach($departedMembers as $member)
                        @php
                            $moveOutHistory = $member->histories
                                ->where('to_status', \App\Models\ContractTenant::STATUS_MOVED_OUT)
                                ->last();
                            $canRestoreMoveOut = $moveOutHistory?->action === 'tenant_move_out';
                        @endphp
                        <article class="px-5 py-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $member->full_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Rời phòng {{ $member->actual_move_out_at?->format('d/m/Y H:i') ?: 'chưa rõ thời điểm' }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Đã rời phòng</span>
                            </div>
                            @if($moveOutHistory?->reason)
                                <p class="mt-2 text-sm text-slate-600"><span class="font-medium text-slate-700">Lý do:</span> {{ $moveOutHistory->reason }}</p>
                            @endif

                            @if($canRestoreMoveOut)
                                <details class="mt-3 rounded-xl border border-amber-200 bg-amber-50/60 p-3">
                                    <summary class="cursor-pointer text-sm font-semibold text-amber-800">Khôi phục do nhập nhầm</summary>
                                    <form method="POST" action="{{ route('admin.contract-tenants.restore-move-out', $member) }}" class="mt-3 grid gap-2 sm:grid-cols-[1fr_auto]" onsubmit="return confirm('Xác nhận khôi phục người này vào danh sách đang ở?')">
                                        @csrf
                                        <input name="reason" required maxlength="1000" placeholder="Lý do hoàn tác (nhập nhầm người/ngày...)" class="h-10 min-w-0 rounded-lg border border-amber-200 bg-white px-3 text-sm">
                                        <button class="h-10 rounded-lg bg-amber-600 px-4 text-sm font-semibold text-white hover:bg-amber-700">Khôi phục</button>
                                    </form>
                                </details>
                            @endif
                        </article>
                    @endforeach
                </div>

                <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-5 py-3">
                    <button type="button" onclick="this.closest('dialog').close()" class="h-10 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100">Đóng</button>
                </div>
            </dialog>
        @endif

        <section id="contract-utilities" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="font-bold text-slate-950">Điện nước gần nhất</h3><span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">{{ $latestReading?->record_date?->format('d/m/Y') ?? 'Chưa ghi chỉ số' }}</span></div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><div class="flex items-center justify-between"><span class="text-sm font-semibold text-amber-900">Điện</span><i class="bx bx-bolt-circle text-2xl text-amber-600"></i></div><p class="mt-3 text-2xl font-bold text-amber-950">{{ $latestReading?->electricity_new ?? '—' }} <span class="text-sm font-medium">kWh</span></p><p class="mt-1 text-xs text-amber-700">Bàn giao: {{ $handoverReading?->electricity_new ?? '—' }}</p></div>
                <div class="rounded-xl border border-sky-200 bg-sky-50 p-4"><div class="flex items-center justify-between"><span class="text-sm font-semibold text-sky-900">Nước</span><i class="bx bx-water text-2xl text-sky-600"></i></div><p class="mt-3 text-2xl font-bold text-sky-950">{{ $latestReading?->water_new ?? '—' }} <span class="text-sm font-medium">m³</span></p><p class="mt-1 text-xs text-sky-700">Bàn giao: {{ $handoverReading?->water_new ?? '—' }}</p></div>
            </div>
        </section>

        <section id="contract-assets" class="scroll-mt-24 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="px-5 py-4"><h3 class="font-bold text-slate-950">Dịch vụ, phương tiện và tài sản bàn giao</h3></div>
            <div class="border-t border-slate-200">
                <div class="grid gap-3 p-5 sm:grid-cols-2"><div class="rounded-xl bg-indigo-50 p-4"><p class="font-semibold text-indigo-950">Internet</p><p class="mt-1 text-sm text-indigo-700">{{ number_format((float) $setting->internet_fee, 0, ',', '.') }}đ/phòng/tháng</p></div><div class="rounded-xl bg-emerald-50 p-4"><p class="font-semibold text-emerald-950">Dịch vụ chung</p><p class="mt-1 text-sm text-emerald-700">{{ number_format((float) $setting->service_fee, 0, ',', '.') }}đ/tháng</p></div></div>
                <div class="border-t border-slate-100 px-5 py-4"><p class="text-sm font-semibold text-slate-900">Phương tiện</p>@if($approvedVehicles->isNotEmpty())<div class="mt-2 flex flex-wrap gap-2">@foreach($approvedVehicles as $vehicle)<span class="rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700">{{ $vehicleTypeLabels[$vehicle->vehicle_type] ?? 'Phương tiện' }} · {{ $vehicle->license_plate ?: 'Không biển số' }}</span>@endforeach</div>@else<p class="mt-1 text-sm text-slate-500">Chưa có phương tiện được duyệt.</p>@endif</div>
                <div class="overflow-x-auto border-t border-slate-100"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Tài sản</th><th class="px-5 py-3">Số lượng</th><th class="px-5 py-3">Tình trạng</th><th class="px-5 py-3">Ghi chú</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($contract->handoverItems as $item)<tr><td class="px-5 py-3 font-semibold">{{ $item->name }}</td><td class="px-5 py-3">{{ $item->is_quantifiable ? $item->quantity : 'Có' }}</td><td class="px-5 py-3">{{ $conditionLabels[$item->condition] ?? 'Không xác định' }}</td><td class="px-5 py-3 text-slate-500">{{ $item->note ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-6 text-center text-slate-500">Không có tài sản bàn giao.</td></tr>@endforelse</tbody></table></div>
            </div>
        </section>
    </div>

    <aside class="space-y-4 xl:sticky xl:top-5 xl:self-start">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between"><h3 class="font-bold text-slate-950">Thu tiền hợp đồng</h3><span class="text-sm font-bold {{ $totalOutstanding > 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $collectionProgress }}%</span></div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $totalOutstanding > 0 ? 'bg-indigo-600' : 'bg-emerald-500' }}" style="width: {{ $collectionProgress }}%"></div></div>
            <dl class="mt-4 divide-y divide-slate-100 text-sm"><div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">Đã xuất hóa đơn</dt><dd class="font-semibold">{{ number_format($totalInvoiced, 0, ',', '.') }}đ</dd></div><div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">Đã thanh toán</dt><dd class="font-semibold text-emerald-700">{{ number_format($totalPaid, 0, ',', '.') }}đ</dd></div><div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">Còn phải thu</dt><dd class="font-bold text-rose-700">{{ number_format($totalOutstanding, 0, ',', '.') }}đ</dd></div></dl>
            <a href="{{ route('admin.invoices.index', ['keyword' => $contract->contract_code]) }}" class="mt-4 flex h-10 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Mở danh sách hóa đơn</a>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-bold text-slate-950">Thời hạn hợp đồng</h3>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $termProgress }}%"></div></div>
            <div class="mt-3 flex justify-between text-xs text-slate-500"><span>{{ $contract->start_date->format('d/m/Y') }}</span><span>{{ $contract->end_date->format('d/m/Y') }}</span></div>
            <dl class="mt-4 divide-y divide-slate-100 text-sm"><div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">Phòng</dt><dd class="font-semibold">{{ $contract->room?->room_code }}</dd></div><div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">Số người</dt><dd class="font-semibold">{{ $contract->currentMembers->count() }}/{{ $contract->room?->max_people }}</dd></div><div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">Nhận phòng</dt><dd class="font-semibold">{{ $contract->actual_move_in_at?->format('d/m/Y H:i') ?: '—' }}</dd></div></dl>
        </section>

    </aside>
</div>

<section id="contract-history" class="scroll-mt-24 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="px-5 py-4"><h3 class="font-bold text-slate-950">Lịch sử hợp đồng</h3></div>
    <div class="overflow-x-auto border-t border-slate-200"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Thời điểm</th><th class="px-5 py-3">Trạng thái</th><th class="px-5 py-3">Thao tác</th><th class="px-5 py-3">Người thực hiện</th><th class="px-5 py-3">Lý do</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($contract->statusHistories as $history)<tr><td class="whitespace-nowrap px-5 py-3 text-slate-500">{{ $history->performed_at?->format('d/m/Y H:i') }}</td><td class="whitespace-nowrap px-5 py-3">{{ $history->from_status ? ($statusLabels[$history->from_status] ?? 'Không xác định') : 'Khởi tạo' }} → <strong>{{ $statusLabels[$history->to_status] ?? 'Không xác định' }}</strong></td><td class="px-5 py-3 font-semibold">{{ $actionLabels[$history->action] ?? 'Cập nhật hợp đồng' }}</td><td class="px-5 py-3 text-slate-500">{{ $history->performer?->name ?? 'Hệ thống' }}</td><td class="px-5 py-3 text-slate-500">{{ $history->reason ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-6 text-center text-slate-500">Chưa có lịch sử.</td></tr>@endforelse</tbody></table></div>
</section>
