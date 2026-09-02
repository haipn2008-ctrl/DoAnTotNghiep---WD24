@php
    $representative = $contract->currentMembers->firstWhere('role', \App\Models\ContractTenant::ROLE_REPRESENTATIVE);
    $residents = $contract->currentMembers->where('role', \App\Models\ContractTenant::ROLE_TENANT)->values();
    $durationDays = $contract->start_date && $contract->end_date
        ? $contract->start_date->diffInDays($contract->end_date)
        : null;
    $representativeIdentityComplete = $representative?->identity_front_path && $representative?->identity_back_path;
@endphp

<div class="mx-auto max-w-7xl space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-r from-white via-indigo-50/70 to-violet-50 p-6 shadow-sm">
        <div class="relative z-10 flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">Hợp đồng thuê phòng</p>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ $contract->contract_code }}</h2>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                    Bản nháp
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-500">Phòng {{ $contract->room?->room_code }} · Người đại diện {{ $representative?->full_name ?? $contract->tenant?->full_name }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i class="bx bx-arrow-back text-lg"></i>
                Danh sách hợp đồng
            </a>
            <a data-contract-print href="{{ route('admin.contracts.print', $contract) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i class="bx bx-printer text-lg"></i>
                Xem bản in
            </a>
            <a href="{{ route('admin.contracts.edit', $contract) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                <i class="bx bx-edit text-lg"></i>
                Sửa bản nháp
            </a>
        </div>
        </div>
        <span class="absolute -bottom-16 -right-10 h-44 w-44 rounded-full bg-indigo-200/25"></span>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <p class="font-semibold">Không thể thực hiện thao tác. Vui lòng kiểm tra lại:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-slate-200 px-6 py-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"><i class="bx bx-file text-xl"></i></span>
            <div><h3 class="font-bold text-slate-950">Thông tin hợp đồng</h3><p class="text-sm text-slate-500">Thông tin thuê phòng và thời gian dự kiến.</p></div>
        </div>

        <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700"><i class="bx bx-building-house text-lg"></i></span>
                <p class="text-sm font-medium text-slate-500">Phòng thuê</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ $contract->room?->room_code ?? 'Chưa xác định' }}</p>
                <p class="mt-1 text-xs text-slate-500">Sức chứa tối đa {{ $contract->room?->max_people ?? 0 }} người</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-violet-100 text-violet-700"><i class="bx bx-user text-lg"></i></span>
                <p class="text-sm font-medium text-slate-500">Người thuê đại diện · Tài khoản liên hệ</p>
                <p class="mt-2 font-semibold text-slate-950">{{ $representative?->full_name ?? $contract->tenant?->full_name ?? 'Chưa xác định' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $representative?->phone ?? $contract->tenant?->phone ?? 'Chưa có số điện thoại' }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-700"><i class="bx bx-calendar text-lg"></i></span>
                <p class="text-sm font-medium text-slate-500">Thời hạn thuê</p>
                <p class="mt-2 font-semibold text-slate-950">{{ $contract->start_date?->format('d/m/Y') }} – {{ $contract->end_date?->format('d/m/Y') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $durationDays !== null ? $durationDays.' ngày' : 'Chưa xác định' }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"><i class="bx bx-group text-lg"></i></span>
                <p class="text-sm font-medium text-slate-500">Số người dự kiến ở</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ $contract->number_of_people }}/{{ $contract->room?->max_people ?? 0 }} người</p>
            </div>
        </div>

        <div class="grid gap-4 border-t border-slate-100 bg-slate-50/50 p-6 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-sm font-medium text-slate-500">Ngày dự kiến nhận phòng</p>
                <p class="mt-1 font-semibold text-slate-950">{{ $contract->scheduled_move_in_date?->format('d/m/Y') ?? 'Chưa xác định' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-sm font-medium text-slate-500">Hạn cuối phải nhận phòng</p>
                <p class="mt-1 font-semibold text-slate-950">{{ $contract->reservation_expires_at?->format('d/m/Y') ?? 'Chưa xác định' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 sm:col-span-2">
                <p class="text-sm font-medium text-slate-500">Chỉ số tham chiếu trước bàn giao</p>
                <p class="mt-1 font-semibold text-slate-950">Điện {{ $suggestedHandoverReading?->electricity_new ?? '—' }} kWh · Nước {{ $suggestedHandoverReading?->water_new ?? '—' }} m³</p>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-5">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-700"><i class="bx bx-group text-xl"></i></span>
                <div><h3 class="font-bold text-slate-950">Danh sách người thuê</h3><p class="text-sm text-slate-500">Hồ sơ những người dự kiến ở trong phòng.</p></div>
            </div>
            <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">{{ $contract->number_of_people }} người dự kiến ở</span>
        </div>

        <div class="grid gap-4 p-6 lg:grid-cols-2">
            @foreach($contract->currentMembers as $member)
                <article class="rounded-xl border {{ $member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE ? 'border-indigo-200 bg-gradient-to-br from-indigo-50/70 to-white' : 'border-slate-200 bg-white' }} p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex gap-3"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-lg font-bold text-indigo-700">{{ mb_strtoupper(mb_substr($member->full_name, 0, 1)) }}</span><div>
                            <p class="font-bold text-slate-950">{{ $member->full_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE ? 'Người thuê đại diện · Có tài khoản liên hệ' : 'Người thuê · Không cấp tài khoản riêng' }}
                            </p>
                        </div></div>
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $member->identity_number ?: 'Chưa có CCCD' }}</span>
                    </div>

                    <div class="mt-5 grid gap-3 rounded-xl bg-white/80 p-3 text-sm ring-1 ring-slate-100 sm:grid-cols-2">
                        <p><span class="text-slate-500">Ngày sinh:</span> <strong class="text-slate-800">{{ $member->date_of_birth?->format('d/m/Y') ?? 'Chưa cập nhật' }}</strong></p>
                        <p><span class="text-slate-500">Điện thoại:</span> <strong class="text-slate-800">{{ $member->phone ?: 'Chưa cập nhật' }}</strong></p>
                    </div>

                    @if($member->identity_front_path && $member->identity_back_path)
                        <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-200 pt-3">
                            <a data-image-modal data-image-title="CCCD mặt trước - {{ $member->full_name }}" href="{{ route('admin.contract-tenants.identity-document', [$member, 'front']) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-slate-50"><i class="bx bx-id-card"></i> CCCD mặt trước</a>
                            <a data-image-modal data-image-title="CCCD mặt sau - {{ $member->full_name }}" href="{{ route('admin.contract-tenants.identity-document', [$member, 'back']) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-slate-50"><i class="bx bx-id-card"></i> CCCD mặt sau</a>
                        </div>
                    @endif
                </article>
            @endforeach

            @if($contract->currentMembers->isEmpty())
                <div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500 lg:col-span-2">Chưa có hồ sơ người đại diện hoặc người thuê.</div>
            @endif
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700"><i class="bx bx-wallet text-xl"></i></span><h3 class="font-bold text-slate-950">Tài chính dự kiến</h3>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4"><p class="text-sm text-emerald-700">Tiền phòng mỗi tháng</p><p class="mt-2 text-xl font-bold text-emerald-950">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ</p></div>
                <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4"><p class="text-sm text-indigo-700">Tiền cọc trước khi vào ở</p><p class="mt-2 text-xl font-bold text-indigo-950">{{ number_format($contract->deposit_amount, 0, ',', '.') }}đ</p></div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700"><i class="bx bx-grid-alt text-xl"></i></span><h3 class="font-bold text-slate-950">Tiện nghi và dịch vụ</h3>
            </div>
            <div class="p-5">
                <div class="mb-4 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200"><i class="bx bx-wifi"></i>Internet · {{ number_format((float) $setting->internet_fee, 0, ',', '.') }}đ/người/tháng</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200"><i class="bx bx-wind"></i>Máy lạnh · đã bao gồm</span>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200"><i class="bx bx-check"></i>Dịch vụ chung bắt buộc · {{ number_format((float) $setting->service_fee, 0, ',', '.') }}đ/người/tháng</span>
                @if($contract->note)
                    <div class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700"><strong>Ghi chú:</strong> {{ $contract->note }}</div>
                @endif
            </div>
        </section>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-semibold text-slate-950">Phát hành bản nháp</h3>
        </div>

        <div class="grid gap-5 p-5 lg:grid-cols-[1fr_360px]">
            <form class="lifecycle-form rounded-lg border border-indigo-200 bg-indigo-50 p-4" method="POST" action="{{ route('admin.contracts.submit-for-signature', $contract) }}">
                @csrf
                @unless($representativeIdentityComplete)
                    <div class="mb-3 flex gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"><i class="bx bx-error-circle mt-0.5 text-lg"></i><div><p class="font-semibold">Chưa thể gửi khách ký</p><p class="mt-0.5">Người đại diện còn thiếu ảnh CCCD mặt trước hoặc mặt sau.</p><a href="{{ route('admin.contracts.edit', $contract) }}" class="mt-1 inline-block font-bold text-indigo-700">Bổ sung hồ sơ ngay →</a></div></div>
                @endunless
                <label for="publish_reason" class="block text-sm font-semibold text-slate-800">Ghi chú khi phát hành</label>
                <textarea id="publish_reason" name="reason" rows="3" maxlength="1000" placeholder="Có thể để trống" class="mt-2 w-full rounded-lg border border-indigo-200 bg-white p-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></textarea>
                <button type="submit" @disabled(! $representativeIdentityComplete) class="mt-3 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none">
                    <i class="bx bx-send text-lg"></i>
                    Phát hành cho khách ký
                </button>
            </form>

            <form class="lifecycle-form rounded-lg border border-rose-200 bg-rose-50 p-4" method="POST" action="{{ route('admin.contracts.cancel', $contract) }}">
                @csrf
                <label for="cancel_reason" class="block text-sm font-semibold text-rose-900">Hủy bản nháp</label>
                <textarea id="cancel_reason" name="cancel_reason" rows="3" required maxlength="1000" placeholder="Nhập lý do hủy bắt buộc" class="mt-2 w-full rounded-lg border border-rose-200 bg-white p-3 text-sm outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"></textarea>
                <button type="submit" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-800">
                    <i class="bx bx-x-circle text-lg"></i>
                    Hủy và giữ lịch sử
                </button>
            </form>
        </div>
    </section>
</div>
