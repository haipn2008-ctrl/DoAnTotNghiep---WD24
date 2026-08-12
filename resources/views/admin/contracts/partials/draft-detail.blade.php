@php
    $representative = $contract->occupants->firstWhere('role', \App\Models\ContractOccupant::ROLE_REPRESENTATIVE);
    $residents = $contract->occupants->where('role', \App\Models\ContractOccupant::ROLE_OCCUPANT)->values();
    $durationDays = $contract->start_date && $contract->end_date
        ? $contract->start_date->diffInDays($contract->end_date)
        : null;
    $serviceItems = collect([
        $contract->internet_enabled ? 'Internet' : null,
        $contract->service_enabled ? 'Dịch vụ chung' : null,
        $contract->parking_quantity > 0 ? $contract->parking_quantity.' xe' : null,
    ])->filter();
@endphp

<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <p class="text-sm font-medium text-slate-500">Quản lý hợp đồng</p>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-bold text-slate-950">{{ $contract->contract_code }}</h2>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                    Bản nháp
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">Rà soát thông tin trước khi phát hành cho khách ký.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i class="bx bx-arrow-back text-lg"></i>
                Danh sách hợp đồng
            </a>
            <a target="_blank" href="{{ route('admin.contracts.print', $contract) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
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
            <p class="text-sm text-slate-500">Phòng thuê, người đại diện và các mốc thời gian đã thỏa thuận.</p>
        </div>

        <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-4">
                <p class="text-sm font-medium text-slate-500">Phòng thuê</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ $contract->room?->room_code ?? 'Chưa xác định' }}</p>
                <p class="mt-1 text-xs text-slate-500">Sức chứa tối đa {{ $contract->room?->max_people ?? 0 }} người</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4">
                <p class="text-sm font-medium text-slate-500">Người đại diện thuê</p>
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
                <p class="mt-1 text-xs text-slate-500">Đại diện {{ $contract->representative_is_occupant ? 'có' : 'không' }} trực tiếp ở</p>
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
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                <h3 class="font-semibold text-slate-950">Người đại diện và người ở</h3>
                <p class="text-sm text-slate-500">Thông tin định danh đã lưu trong bản nháp hợp đồng.</p>
            </div>
            <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">{{ $contract->number_of_people }} người dự kiến ở</span>
        </div>

        <div class="grid gap-4 p-5 lg:grid-cols-2">
            @foreach($contract->occupants as $occupant)
                <article class="rounded-lg border {{ $occupant->role === \App\Models\ContractOccupant::ROLE_REPRESENTATIVE ? 'border-indigo-200 bg-indigo-50/40' : 'border-slate-200' }} p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $occupant->full_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $occupant->role === \App\Models\ContractOccupant::ROLE_REPRESENTATIVE ? 'Người đại diện thuê' : 'Người ở' }}
                                @if($occupant->role === \App\Models\ContractOccupant::ROLE_REPRESENTATIVE)
                                    · {{ $contract->representative_is_occupant ? 'có trực tiếp ở' : 'không trực tiếp ở' }}
                                @endif
                            </p>
                        </div>
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $occupant->identity_number ?: 'Chưa có CCCD' }}</span>
                    </div>

                    <div class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                        <p><span class="text-slate-500">Ngày sinh:</span> <strong class="text-slate-800">{{ $occupant->date_of_birth?->format('d/m/Y') ?? 'Chưa cập nhật' }}</strong></p>
                        <p><span class="text-slate-500">Điện thoại:</span> <strong class="text-slate-800">{{ $occupant->phone ?: 'Chưa cập nhật' }}</strong></p>
                    </div>

                    @if($occupant->identity_front_path && $occupant->identity_back_path)
                        <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-200 pt-3">
                            <a target="_blank" href="{{ route('admin.contract-occupants.identity-document', [$occupant, 'front']) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-slate-50"><i class="bx bx-id-card"></i> CCCD mặt trước</a>
                            <a target="_blank" href="{{ route('admin.contract-occupants.identity-document', [$occupant, 'back']) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-slate-50"><i class="bx bx-id-card"></i> CCCD mặt sau</a>
                        </div>
                    @endif
                </article>
            @endforeach

            @if($contract->occupants->isEmpty())
                <div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500 lg:col-span-2">Chưa có hồ sơ người đại diện hoặc người ở.</div>
            @endif
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Tài chính dự kiến</h3>
                <p class="text-sm text-slate-500">Các khoản trong bản nháp, chưa được ghi nhận là đã thu.</p>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Tiền phòng mỗi tháng</p><p class="mt-2 text-lg font-bold text-slate-950">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ</p></div>
                <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Tiền cọc thỏa thuận</p><p class="mt-2 text-lg font-bold text-slate-950">{{ number_format($contract->deposit_amount, 0, ',', '.') }}đ</p></div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Dịch vụ đăng ký</h3>
                <p class="text-sm text-slate-500">Dịch vụ dự kiến áp dụng khi hợp đồng có hiệu lực.</p>
            </div>
            <div class="p-5">
                @if($serviceItems->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach($serviceItems as $service)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200"><i class="bx bx-check"></i>{{ $service }}</span>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-slate-200 px-4 py-7 text-center text-sm text-slate-500">Chưa đăng ký dịch vụ bổ sung.</div>
                @endif
                @if($contract->note)
                    <div class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700"><strong>Ghi chú:</strong> {{ $contract->note }}</div>
                @endif
            </div>
        </section>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-semibold text-slate-950">Phát hành bản nháp</h3>
            <p class="text-sm text-slate-500">Kiểm tra bản in hoặc chỉnh sửa nếu cần. Khi phát hành, hợp đồng chuyển sang trạng thái chờ khách ký.</p>
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
