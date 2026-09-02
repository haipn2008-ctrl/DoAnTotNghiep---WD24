@php
    $representative = $contract->currentMembers->firstWhere('role', \App\Models\ContractTenant::ROLE_REPRESENTATIVE);
    $pendingMembers = $contract->currentMembers->where('status', \App\Models\ContractTenant::STATUS_PENDING);
@endphp

<div class="grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm sm:grid-cols-2 lg:grid-cols-4">
    <div class="border-b border-slate-100 p-5 sm:border-r lg:border-b-0"><span class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700"><i class="bx bx-building-house text-lg"></i></span>
        <p class="text-xs font-medium text-slate-500">Phòng</p>
        <p class="mt-1 font-bold text-slate-950">{{ $contract->room?->room_code ?? 'Chưa xác định' }}</p>
    </div>
    <div class="border-b border-slate-100 p-5 lg:border-b-0 lg:border-r"><span class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-violet-100 text-violet-700"><i class="bx bx-user text-lg"></i></span>
        <p class="text-xs font-medium text-slate-500">Người thuê đại diện</p>
        <p class="mt-1 truncate font-bold text-slate-950">{{ $representative?->full_name ?? $contract->tenant?->full_name ?? 'Chưa xác định' }}</p>
    </div>
    <div class="border-b border-slate-100 p-5 sm:border-b-0 sm:border-r"><span class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-700"><i class="bx bx-calendar text-lg"></i></span>
        <p class="text-xs font-medium text-slate-500">Thời hạn thuê</p>
        <p class="mt-1 font-bold text-slate-950">{{ $contract->start_date?->format('d/m/Y') }} – {{ $contract->end_date?->format('d/m/Y') }}</p>
    </div>
    <div class="p-5"><span class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"><i class="bx bx-wallet text-lg"></i></span>
        <p class="text-xs font-medium text-slate-500">Tiền cọc</p>
        <p class="mt-1 font-bold text-slate-950">{{ number_format($contract->deposit_amount, 0, ',', '.') }}đ</p>
    </div>
</div>

@if($pendingMembers->isNotEmpty())
    <section class="overflow-hidden rounded-lg border border-amber-200 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-3 border-b border-amber-100 bg-amber-50 px-4 py-3">
            <h3 class="font-semibold text-amber-950">Người thuê chờ duyệt</h3>
            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-amber-700">{{ $pendingMembers->count() }}</span>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($pendingMembers as $member)
                <div class="grid gap-3 px-4 py-3 lg:grid-cols-[minmax(180px,1fr)_auto] lg:items-center">
                    <div>
                        <p class="font-semibold text-slate-950">{{ $member->full_name }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $member->identity_number ?: 'Chưa có CCCD' }} · {{ $member->phone ?: 'Chưa có số điện thoại' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.contract-tenants.approve', $member) }}">@csrf
                            <button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Duyệt</button>
                        </form>
                        <form method="POST" action="{{ route('admin.contract-tenants.reject', $member) }}" class="flex min-w-0 gap-2">
                            @csrf
                            <input name="reason" required maxlength="1000" placeholder="Lý do từ chối" class="h-9 min-w-0 rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-rose-500 focus:ring-4 focus:ring-rose-100">
                            <button class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Từ chối</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

<section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm">
    <div class="flex items-center gap-3 border-b border-emerald-100 bg-emerald-50/60 px-6 py-5">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white"><i class="bx bx-pen text-xl"></i></span><div><h3 class="font-bold text-emerald-950">Xác nhận hợp đồng đã ký</h3><p class="text-sm text-emerald-700">Ghi nhận thời gian và tải bản ký để chuyển sang bước tiền cọc.</p></div>
    </div>
    <form class="lifecycle-form grid grid-cols-1 gap-5 p-6 xl:grid-cols-12 xl:items-end" method="POST" enctype="multipart/form-data" action="{{ route('admin.contracts.mark-signed', $contract) }}">
        @csrf
        <div class="xl:col-span-3">
            <label for="signed_at" class="block text-sm font-semibold text-slate-700">Thời gian ký</label>
            <input id="signed_at" type="datetime-local" name="signed_at" value="{{ old('signed_at', now()->format('Y-m-d\\TH:i')) }}" max="{{ now()->format('Y-m-d\\TH:i') }}" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
            @error('signed_at')<p class="mt-1 text-xs font-medium leading-5 text-rose-600">{{ $message }}</p>@enderror
        </div>
        <label class="block xl:col-span-4 text-sm font-semibold text-slate-700">
            Bản đã ký
            <input type="file" name="signed_contract_file" accept=".pdf,image/jpeg,image/png,image/webp" class="mt-1.5 block h-10 w-full rounded-lg border border-slate-200 bg-white text-sm file:mr-3 file:h-full file:border-0 file:bg-slate-100 file:px-3">
        </label>
        <label class="block xl:col-span-3 text-sm font-semibold text-slate-700">
            Ghi chú
            <input name="reason" value="{{ old('reason') }}" placeholder="Không bắt buộc" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
        </label>
        <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 xl:col-span-2">
            <i class="bx bx-check-circle text-lg"></i><span>Xác nhận ký</span>
        </button>
    </form>
</section>

<div class="space-y-4">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-5 font-semibold text-slate-800">
            <span class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-50 text-violet-700"><i class="bx bx-group text-xl"></i></span>Thông tin hợp đồng và người thuê</span>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $contract->currentMembers->count() }}/{{ $contract->room?->max_people ?? 0 }} người</span>
        </div>
        <div class="space-y-4 p-6">
            <dl class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3"><dt class="text-xs font-medium text-slate-500">Ngày dự kiến nhận</dt><dd class="mt-1 font-semibold leading-5 text-slate-900">{{ $contract->scheduled_move_in_date?->format('d/m/Y') ?? '—' }}</dd></div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3"><dt class="text-xs font-medium text-slate-500">Hạn giữ phòng</dt><dd class="mt-1 font-semibold leading-5 text-slate-900">{{ $contract->reservation_expires_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3"><dt class="text-xs font-medium text-slate-500">Tiền phòng</dt><dd class="mt-1 font-semibold leading-5 text-slate-900">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ/tháng</dd></div>
                <div class="rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3"><dt class="text-xs font-medium text-amber-700">Hạn ký</dt><dd class="mt-1 font-semibold leading-5 text-amber-950">{{ $contract->signature_due_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            </dl>
            <div class="grid gap-2 md:grid-cols-2">
                @foreach($contract->currentMembers as $member)
                    <article class="min-w-0 rounded-xl border border-slate-200 bg-gradient-to-br from-white to-slate-50 px-4 py-4 text-sm shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                            <p class="min-w-0 break-words font-semibold leading-5 text-slate-950">{{ $member->full_name }}</p>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium leading-5 {{ $member->status === \App\Models\ContractTenant::STATUS_APPROVED ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $member->status === \App\Models\ContractTenant::STATUS_APPROVED ? 'Đã duyệt' : $member->status_label }}
                            </span>
                        </div>
                        @if($member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE)<p class="mt-1 text-xs font-medium leading-5 text-indigo-700">Người thuê đại diện · Tài khoản liên hệ</p>@else<p class="mt-1 text-xs text-slate-500">Người thuê · Không cấp tài khoản riêng</p>@endif
                        <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs leading-5 text-slate-500">
                            <span>CCCD: {{ $member->identity_number ?: 'Chưa cập nhật' }}</span>
                            <span>SĐT: {{ $member->phone ?: 'Chưa cập nhật' }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-5 font-semibold text-slate-800">
            <span class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700"><i class="bx bx-package text-xl"></i></span>Bàn giao và chi phí định kỳ</span>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $contract->handoverItems->count() }} tài sản <span class="mx-1">·</span> {{ $approvedVehicles->count() }} xe</span>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm leading-6 text-slate-700">
                <span>Internet: <strong>{{ number_format((float) $setting->internet_fee, 0, ',', '.') }}đ/người/tháng</strong></span>
                <span>Dịch vụ chung: <strong>{{ number_format((float) $setting->service_fee, 0, ',', '.') }}đ/người/tháng</strong></span>
            </div>
            <div class="mt-3 overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-3 py-2">Tài sản</th><th class="px-3 py-2">SL</th><th class="px-3 py-2">Tình trạng</th><th class="px-3 py-2">Ghi chú</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">@forelse($contract->handoverItems as $item)<tr><td class="px-3 py-2 font-semibold">{{ $item->name }}</td><td class="px-3 py-2">{{ $item->is_quantifiable ? $item->quantity : 'Có' }}</td><td class="px-3 py-2">{{ $conditionLabels[$item->condition] ?? 'Không xác định' }}</td><td class="px-3 py-2 text-slate-500">{{ $item->note ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="px-3 py-5 text-center text-slate-500">Không có tài sản bàn giao.</td></tr>@endforelse</tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5 font-semibold text-slate-800"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600"><i class="bx bx-history text-xl"></i></span>Lịch sử hợp đồng</div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-3 py-2">Thời điểm</th><th class="px-3 py-2">Trạng thái</th><th class="px-3 py-2">Thao tác</th><th class="px-3 py-2">Người thực hiện</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">@forelse($contract->statusHistories as $history)<tr><td class="whitespace-nowrap px-3 py-2 text-slate-600">{{ $history->performed_at?->format('d/m/Y H:i') }}</td><td class="whitespace-nowrap px-3 py-2">{{ $history->from_status ? ($statusLabels[$history->from_status] ?? 'Không xác định') : 'Khởi tạo' }} → <strong>{{ $statusLabels[$history->to_status] ?? 'Không xác định' }}</strong></td><td class="px-3 py-2">{{ $actionLabels[$history->action] ?? 'Cập nhật hợp đồng' }}</td><td class="px-3 py-2 text-slate-600">{{ $history->performer?->name ?? 'Hệ thống' }}</td></tr>@empty<tr><td colspan="4" class="px-3 py-5 text-center text-slate-500">Chưa có lịch sử.</td></tr>@endforelse</tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<dialog id="edit-contract-dialog" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-xl bg-white p-0 text-slate-700 shadow-2xl backdrop:bg-slate-900/50">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="font-semibold text-slate-950">Sửa hợp đồng</h3>
        <button type="button" onclick="this.closest('dialog').close()" aria-label="Đóng" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
            <i class="bx bx-x text-xl"></i>
        </button>
    </div>
    <form class="lifecycle-form p-5" method="POST" action="{{ route('admin.contracts.return-to-draft', $contract) }}">
        @csrf
        <input type="hidden" name="edit_after_return" value="1">
        <label for="edit_reason" class="block text-sm font-semibold text-slate-700">Lý do sửa</label>
        <textarea id="edit_reason" name="reason" rows="3" required maxlength="1000" placeholder="Nhập lý do" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></textarea>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Đóng</button>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Tiếp tục sửa</button>
        </div>
    </form>
</dialog>
<dialog id="cancel-contract-dialog" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-xl bg-white p-0 text-slate-700 shadow-2xl backdrop:bg-slate-900/50">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="font-semibold text-slate-950">Hủy hợp đồng</h3>
        <button type="button" onclick="this.closest('dialog').close()" aria-label="Đóng" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
            <i class="bx bx-x text-xl"></i>
        </button>
    </div>
    <form class="lifecycle-form p-5" method="POST" action="{{ route('admin.contracts.cancel', $contract) }}">
        @csrf
        <label for="cancel_reason" class="block text-sm font-semibold text-slate-700">Lý do hủy</label>
        <textarea id="cancel_reason" name="cancel_reason" rows="3" required maxlength="1000" placeholder="Nhập lý do" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-rose-500 focus:ring-4 focus:ring-rose-100"></textarea>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Đóng</button>
            <button type="submit" class="rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-800">Xác nhận hủy</button>
        </div>
    </form>
</dialog>
