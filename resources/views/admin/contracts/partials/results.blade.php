@php
    $statusOptions = [
        'draft' => ['label' => 'Bản nháp', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'],
        'pending_signature' => ['label' => 'Chờ ký', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
        'pending_deposit' => ['label' => 'Chờ tiền cọc', 'class' => 'bg-orange-50 text-orange-700 ring-orange-200', 'dot' => 'bg-orange-500'],
        'awaiting_move_in' => ['label' => 'Chờ nhận phòng', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200', 'dot' => 'bg-sky-500'],
        'active' => ['label' => 'Đang thuê', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'expired' => ['label' => 'Hết hạn - chờ xử lý', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        'settling' => ['label' => 'Đang quyết toán', 'class' => 'bg-violet-50 text-violet-700 ring-violet-200', 'dot' => 'bg-violet-500'],
        'completed' => ['label' => 'Hoàn tất', 'class' => 'bg-green-50 text-green-700 ring-green-200', 'dot' => 'bg-green-500'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-gray-50 text-gray-700 ring-gray-200', 'dot' => 'bg-gray-500'],
    ];
@endphp

<section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
            <h3 class="font-semibold text-slate-950">Hợp đồng phù hợp</h3>
            <p class="text-sm text-slate-500">Tìm thấy {{ $contracts->count() }} hợp đồng</p>
        </div>
        <span data-contract-loading class="hidden text-sm font-semibold text-indigo-600"><i class="bx bx-loader-alt mr-1 animate-spin"></i>Đang lọc</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Hợp đồng</th>
                    <th class="px-5 py-3">Người thuê</th>
                    <th class="px-5 py-3">Phòng</th>
                    <th class="px-5 py-3">Thời hạn</th>
                    <th class="px-5 py-3">Trạng thái</th>
                    <th class="px-5 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($contracts as $contract)
                    @php
                        $status = $contract->isExpiringSoon()
                            ? ['label' => 'Sắp hết hạn', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500']
                            : ($statusOptions[$contract->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400']);
                    @endphp
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-slate-950">{{ $contract->contract_code ?: 'HD' . str_pad($contract->id, 3, '0', STR_PAD_LEFT) }}</p>
                            <p class="mt-1 text-xs text-slate-500">Tiền cọc {{ number_format($contract->deposit_amount ?? 0, 0, ',', '.') }}đ</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-medium text-slate-900">{{ $contract->tenant->full_name ?? 'Không có' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $contract->tenant->phone ?? 'Chưa có SĐT' }}</p>
                        </td>
                        <td class="px-5 py-4"><span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">Phòng {{ $contract->room->room_code ?? 'Không có' }}</span></td>
                        <td class="px-5 py-4 text-slate-600">
                            <p>{{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}</p>
                            <p class="text-xs text-slate-500">đến {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}"><span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>{{ $status['label'] }}</span>
                            @if($contract->isReservationOverdue())<p class="mt-1 text-xs font-semibold text-rose-700">Quá hạn nhận phòng</p>@endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.contracts.show', $contract) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100" title="Xem chi tiết"><i class="bx bx-show text-lg"></i></a>
                                <a data-contract-print href="{{ route('admin.contracts.print', $contract->id) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="In hợp đồng"><i class="bx bx-printer text-lg"></i></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400"><i class="bx bx-file text-2xl"></i></div>
                            <p class="mt-3 font-semibold text-slate-900">Không tìm thấy hợp đồng</p>
                            <p class="mt-1 text-sm text-slate-500">Thử thay đổi từ khóa hoặc trạng thái lọc.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
