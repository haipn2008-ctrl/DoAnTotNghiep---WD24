@php
    $representative = $contract->currentMembers->firstWhere('role', \App\Models\ContractTenant::ROLE_REPRESENTATIVE);
    $residents = $contract->currentMembers->where('role', \App\Models\ContractTenant::ROLE_TENANT)->values();
    $durationDays = $contract->start_date && $contract->end_date
        ? $contract->start_date->diffInDays($contract->end_date)
        : null;
@endphp

<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-bold text-slate-950">{{ $contract->contract_code }}</h2>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                    Bản nháp
                </span>
            </div>
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

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <p class="font-semibold">Không thể thực hiện thao tác. Vui lòng kiểm tra lại:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-start gap-3 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
        <i class="bx bx-info-circle mt-0.5 text-xl text-sky-600"></i>
        <div>
            <p class="font-semibold">Hợp đồng này mới chỉ là bản nháp</p>
            <p class="mt-0.5 text-sky-800">Khách chưa nhận được yêu cầu ký, chưa phát sinh nghĩa vụ cọc và phòng chưa bị chuyển sang trạng thái đang thuê.</p>
        </div>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-semibold text-slate-950">Thông tin hợp đồng</h3>
        </div>

        <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-4">
                <p class="text-sm font-medium text-slate-500">Phòng thuê</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ $contract->room?->room_code ?? 'Chưa xác định' }}</p>
                <p class="mt-1 text-xs text-slate-500">Sức chứa tối đa {{ $contract->room?->max_people ?? 0 }} người</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4">
                <p class="text-sm font-medium text-slate-500">Người thuê đại diện · Tài khoản liên hệ</p>
                <p class="mt-2 font-semibold text-slate-950">{{ $representative?->full_name ?? $contract->tenant?->full_name ?? 'Chưa xác định' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $representative?->phone ?? $contract->tenant?->phone ?? 'Chưa có số điện thoại' }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4">
                <p class="text-sm font-medium text-slate-500">Thời hạn thuê</p>
                <p class="mt-2 font-semibold text-slate-950">{{ $contract->start_date?->format('d/m/Y') }} – {{ $contract->end_date?->format('d/m/Y') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $durationDays !== null ? $durationDays.' ngày' : 'Chưa xác định' }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4">
                <p class="text-sm font-medium text-slate-500">Số người dự kiến ở</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ $contract->number_of_people }}/{{ $contract->room?->max_people ?? 0 }} người</p>
            </div>
        </div>

        <div class="grid gap-4 border-t border-slate-100 p-5 sm:grid-cols-2">
            <div class="rounded-lg border border-slate-200 px-4 py-3">
                <p class="text-sm font-medium text-slate-500">Ngày dự kiến nhận phòng</p>
                <p class="mt-1 font-semibold text-slate-950">{{ $contract->scheduled_move_in_date?->format('d/m/Y') ?? 'Chưa xác định' }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 px-4 py-3">
                <p class="text-sm font-medium text-slate-500">Hạn cuối phải nhận phòng</p>
                <p class="mt-1 font-semibold text-slate-950">{{ $contract->reservation_expires_at?->format('d/m/Y') ?? 'Chưa xác định' }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 px-4 py-3 sm:col-span-2">
                <p class="text-sm font-medium text-slate-500">Chỉ số tham chiếu trước bàn giao</p>
                <p class="mt-1 font-semibold text-slate-950">Điện {{ $suggestedHandoverReading?->electricity_new ?? '—' }} kWh · Nước {{ $suggestedHandoverReading?->water_new ?? '—' }} m³</p>
            </div>
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h3 class="font-semibold text-slate-950">Danh sách người thuê</h3>
            </div>
            <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">{{ $contract->number_of_people }} người dự kiến ở</span>
        </div>

        <div class="grid gap-4 p-5 lg:grid-cols-2">
            @foreach($contract->currentMembers as $member)
                <article class="rounded-lg border {{ $member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE ? 'border-indigo-200 bg-indigo-50/40' : 'border-slate-200' }} p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $member->full_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE ? 'Người thuê đại diện · Có tài khoản liên hệ' : 'Người thuê · Không cấp tài khoản riêng' }}
                            </p>
                        </div>
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $member->identity_number ?: 'Chưa có CCCD' }}</span>
                    </div>

                    <div class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
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
        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Tài chính dự kiến</h3>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Tiền phòng mỗi tháng</p><p class="mt-2 text-lg font-bold text-slate-950">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ</p></div>
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Tiền cọc trước khi vào ở</p><p class="mt-2 text-lg font-bold text-slate-950">{{ number_format($contract->deposit_amount, 0, ',', '.') }}đ</p></div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Tiện nghi và dịch vụ</h3>
            </div>
            <div class="p-5">
                <div class="mb-4 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200"><i class="bx bx-wifi"></i>Internet · {{ number_format((float) $setting->internet_fee, 0, ',', '.') }}đ/tháng</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200"><i class="bx bx-wind"></i>Máy lạnh · đã bao gồm</span>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200"><i class="bx bx-check"></i>Dịch vụ chung bắt buộc · {{ number_format((float) $setting->service_fee, 0, ',', '.') }}đ/tháng</span>
                @if($contract->note)
                    <div class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700"><strong>Ghi chú:</strong> {{ $contract->note }}</div>
                @endif
            </div>
        </section>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-semibold text-slate-950">Phát hành bản nháp</h3>
        </div>

        <div class="grid gap-5 p-5 lg:grid-cols-[1fr_360px]">
            <form class="lifecycle-form rounded-lg border border-indigo-200 bg-indigo-50 p-4" method="POST" action="{{ route('admin.contracts.submit-for-signature', $contract) }}">
                @csrf
                <label for="publish_reason" class="block text-sm font-semibold text-slate-800">Ghi chú khi phát hành</label>
                <textarea id="publish_reason" name="reason" rows="3" maxlength="1000" placeholder="Có thể để trống" class="mt-2 w-full rounded-lg border border-indigo-200 bg-white p-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></textarea>
                <button type="submit" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
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
