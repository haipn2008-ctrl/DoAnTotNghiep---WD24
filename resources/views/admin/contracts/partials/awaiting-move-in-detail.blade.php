@php
    $representative = $contract->currentMembers->firstWhere('role', \App\Models\ContractTenant::ROLE_REPRESENTATIVE);
    $pendingMembers = $contract->currentMembers->where('status', \App\Models\ContractTenant::STATUS_PENDING);
    $clientConfirmed = filled($contract->move_in_details_confirmed_at) && filled($contract->move_in_inventory_snapshotted_at);
    $canCheckIn = $clientConfirmed && $pendingMembers->isEmpty();
    $requiresVarianceReason = ! $contract->scheduled_move_in_date?->isSameDay(now());
    $minimumExtension = now()->addMinute();
    if ($contract->reservation_expires_at && $contract->reservation_expires_at->gte($minimumExtension)) {
        $minimumExtension = $contract->reservation_expires_at->copy()->addMinute();
    }
    $maximumExtension = $contract->start_date?->copy()->addMonthNoOverflow()->endOfDay();
@endphp

<div class="grid overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm sm:grid-cols-2 lg:grid-cols-4">
    <div class="border-b border-slate-100 px-4 py-3 sm:border-r lg:border-b-0">
        <p class="text-xs font-medium text-slate-500">Phòng</p>
        <p class="mt-1 font-bold text-slate-950">{{ $contract->room?->room_code ?? 'Chưa xác định' }}</p>
    </div>
    <div class="border-b border-slate-100 px-4 py-3 lg:border-b-0 lg:border-r">
        <p class="text-xs font-medium text-slate-500">Người đại diện</p>
        <p class="mt-1 truncate font-bold text-slate-950">{{ $representative?->full_name ?? $contract->tenant?->full_name ?? 'Chưa xác định' }}</p>
    </div>
    <div class="border-b border-slate-100 px-4 py-3 sm:border-b-0 sm:border-r">
        <p class="text-xs font-medium text-slate-500">Ngày dự kiến nhận</p>
        <p class="mt-1 font-bold text-slate-950">{{ $contract->scheduled_move_in_date?->format('d/m/Y') ?? '—' }}</p>
    </div>
    <div class="px-4 py-3">
        <p class="text-xs font-medium text-slate-500">Hạn giữ phòng</p>
        <p class="mt-1 font-bold {{ $contract->isReservationOverdue() ? 'text-rose-700' : 'text-slate-950' }}">{{ $contract->reservation_expires_at?->format('d/m/Y H:i') ?? '—' }}</p>
    </div>
</div>

@if($pendingMembers->isNotEmpty())
    <section class="overflow-hidden rounded-lg border border-amber-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-amber-100 bg-amber-50 px-4 py-3">
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
                            <button class="h-9 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700">Duyệt</button>
                        </form>
                        <form method="POST" action="{{ route('admin.contract-tenants.reject', $member) }}" class="flex min-w-0 gap-2">@csrf
                            <input name="reason" required maxlength="1000" placeholder="Lý do từ chối" class="h-9 min-w-0 rounded-lg border border-slate-200 px-3 text-sm">
                            <button class="h-9 rounded-lg border border-rose-200 px-4 text-sm font-semibold text-rose-700 hover:bg-rose-50">Từ chối</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

<section class="overflow-hidden rounded-lg border border-emerald-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-emerald-100 bg-emerald-50 px-4 py-3">
        <h3 class="font-semibold text-emerald-950">Xác nhận nhận phòng</h3>
        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $clientConfirmed ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
            {{ $clientConfirmed ? 'Khách đã xác nhận thông tin' : 'Chờ khách xác nhận thông tin' }}
        </span>
    </div>
    <form class="lifecycle-form grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-12 xl:items-end" method="POST" action="{{ route('admin.contracts.check-in', $contract) }}">
        @csrf
        <label class="block text-sm font-semibold text-slate-700 xl:col-span-3">
            Thời gian nhận phòng
            <input type="datetime-local" name="actual_move_in_at" value="{{ old('actual_move_in_at', now()->format('Y-m-d\\TH:i')) }}" max="{{ now()->format('Y-m-d\\TH:i') }}" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
        </label>
        <label class="block text-sm font-semibold text-slate-700 xl:col-span-2">
            Chỉ số điện
            <input type="number" min="0" name="handover_electricity" value="{{ old('handover_electricity', $suggestedHandoverReading?->electricity_new) }}" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
        </label>
        <label class="block text-sm font-semibold text-slate-700 xl:col-span-2">
            Chỉ số nước
            <input type="number" min="0" name="handover_water" value="{{ old('handover_water', $suggestedHandoverReading?->water_new) }}" required class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
        </label>
        <label class="block text-sm font-semibold text-slate-700 xl:col-span-3">
            Lý do nhận sớm hoặc muộn
            <input name="schedule_variance_reason" value="{{ old('schedule_variance_reason') }}" @required($requiresVarianceReason) placeholder="{{ $requiresVarianceReason ? 'Bắt buộc' : 'Không bắt buộc' }}" class="mt-1.5 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100">
        </label>
        <button @disabled(!$canCheckIn) class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300 xl:col-span-2">
            <i class="bx bx-log-in-circle text-lg"></i>
            Nhận phòng
        </button>
        <label class="flex items-center gap-2 text-sm font-medium text-slate-700 md:col-span-2 xl:col-span-12">
            <input type="checkbox" name="handover_confirmed" value="1" required class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            Đã đối chiếu chỉ số và tài sản bàn giao
        </label>
    </form>
</section>

<div class="grid gap-4 xl:grid-cols-[minmax(0,1.1fr)_minmax(360px,0.9fr)]">
    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="font-semibold text-slate-950">Tài sản bàn giao</h3>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $contract->handoverItems->count() }} tài sản</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-4 py-2.5">Tài sản</th><th class="px-4 py-2.5">Số lượng</th><th class="px-4 py-2.5">Tình trạng</th><th class="px-4 py-2.5">Ghi chú</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($contract->handoverItems as $item)
                        <tr><td class="px-4 py-2.5 font-semibold text-slate-950">{{ $item->name }}</td><td class="px-4 py-2.5 text-slate-600">{{ $item->is_quantifiable ? $item->quantity : 'Có' }}</td><td class="px-4 py-2.5 text-slate-600">{{ $conditionLabels[$item->condition] ?? 'Không xác định' }}</td><td class="px-4 py-2.5 text-slate-500">{{ $item->note ?: '—' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Không có tài sản bàn giao.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="font-semibold text-slate-950">Người nhận phòng</h3>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $contract->currentMembers->count() }}/{{ $contract->room?->max_people ?? 0 }} người</span>
        </div>
        <div class="grid gap-2 p-4 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
            @foreach($contract->currentMembers as $member)
                <article class="min-w-0 rounded-lg bg-slate-50 px-3 py-2.5 text-sm">
                    <p class="truncate font-semibold text-slate-950">{{ $member->full_name }}</p>
                    @if($member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE)<p class="mt-0.5 text-xs font-medium text-indigo-700">Người đại diện</p>@endif
                    <p class="mt-1 truncate text-xs text-slate-500">{{ $member->identity_number ?: 'Chưa có CCCD' }} · {{ $member->phone ?: 'Chưa có số điện thoại' }}</p>
                </article>
            @endforeach
        </div>
        <div class="border-t border-slate-100 px-4 py-3 text-sm text-slate-600">
            Chỉ số gần nhất: <strong class="text-slate-950">Điện {{ $suggestedHandoverReading?->electricity_new ?? '—' }} · Nước {{ $suggestedHandoverReading?->water_new ?? '—' }}</strong>
        </div>
    </section>
</div>

<section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-4 py-3 font-semibold text-slate-950">Lịch sử hợp đồng</div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-4 py-2.5">Thời điểm</th><th class="px-4 py-2.5">Trạng thái</th><th class="px-4 py-2.5">Thao tác</th><th class="px-4 py-2.5">Người thực hiện</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($contract->statusHistories as $history)
                    <tr><td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $history->performed_at?->format('d/m/Y H:i') }}</td><td class="whitespace-nowrap px-4 py-3">{{ $history->from_status ? ($statusLabels[$history->from_status] ?? 'Không xác định') : 'Khởi tạo' }} → <strong>{{ $statusLabels[$history->to_status] ?? 'Không xác định' }}</strong></td><td class="px-4 py-3">{{ $actionLabels[$history->action] ?? 'Cập nhật hợp đồng' }}</td><td class="px-4 py-3 text-slate-600">{{ $history->performer?->name ?? 'Hệ thống' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Chưa có lịch sử.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<dialog id="extend-move-in-dialog" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-xl bg-white p-0 text-slate-700 shadow-2xl backdrop:bg-slate-900/50">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="font-semibold text-slate-950">Gia hạn giữ phòng</h3>
        <button type="button" onclick="this.closest('dialog').close()" aria-label="Đóng" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="bx bx-x text-xl"></i></button>
    </div>
    <form class="lifecycle-form p-5" method="POST" action="{{ route('admin.contracts.extend-move-in-deadline', $contract) }}">
        @csrf
        <label for="reservation_expires_at" class="block text-sm font-semibold text-slate-700">Hạn giữ phòng mới</label>
        <input id="reservation_expires_at" type="datetime-local" name="reservation_expires_at" value="{{ old('reservation_expires_at', $minimumExtension->format('Y-m-d\\TH:i')) }}" min="{{ $minimumExtension->format('Y-m-d\\TH:i') }}" @if($maximumExtension) max="{{ $maximumExtension->format('Y-m-d\\TH:i') }}" @endif required class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
        <label for="extension_reason" class="mt-4 block text-sm font-semibold text-slate-700">Lý do gia hạn</label>
        <textarea id="extension_reason" name="reason" rows="3" required maxlength="1000" placeholder="Nhập lý do" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100"></textarea>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Đóng</button>
            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Gia hạn</button>
        </div>
    </form>
</dialog>
<dialog id="cancel-contract-dialog" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-xl bg-white p-0 text-slate-700 shadow-2xl backdrop:bg-slate-900/50">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h3 class="font-semibold text-slate-950">Hủy hợp đồng</h3>
        <button type="button" onclick="this.closest('dialog').close()" aria-label="Đóng" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="bx bx-x text-xl"></i></button>
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
