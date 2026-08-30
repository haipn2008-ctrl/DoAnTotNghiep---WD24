<section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
            <h3 class="font-semibold text-slate-950">Khách thuê phù hợp</h3>
            <p class="text-sm text-slate-500">Tìm thấy {{ $tenants->total() }} khách, hiển thị {{ $tenants->count() }} khách trên trang này</p>
        </div>
        <span data-tenant-loading class="hidden text-sm font-semibold text-indigo-600"><i class="bx bx-loader-alt mr-1 animate-spin"></i>Đang lọc</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Khách thuê</th>
                    <th class="px-5 py-3">CCCD</th>
                    <th class="px-5 py-3">Số điện thoại</th>
                    <th class="px-5 py-3">Phòng thuê</th>
                    <th class="px-5 py-3">Ngày tạo</th>
                    <th class="px-5 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tenants as $tenant)
                    @php
                        $membershipContract = $tenant->memberContracts
                            ->whereIn('status', \App\Models\Contract::OPEN_OCCUPANCY_STATUSES)
                            ->first(fn ($contract) => $contract->pivot?->status === \App\Models\ContractTenant::STATUS_CHECKED_IN);
                        $representativeContract = $tenant->contracts
                            ->whereIn('status', \App\Models\Contract::OPEN_OCCUPANCY_STATUSES)
                            ->first();
                        $activeContract = $membershipContract ?? $representativeContract;
                        $hasRentalHistory = $tenant->contracts
                            ->contains(fn ($contract) => $contract->actual_move_in_at !== null)
                            || $tenant->memberContracts
                                ->contains(fn ($contract) => $contract->pivot?->status === \App\Models\ContractTenant::STATUS_MOVED_OUT);
                        $isRepresentative = $membershipContract
                            ? $membershipContract->pivot->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE
                            : $representativeContract !== null;
                    @endphp
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-700 ring-1 ring-indigo-100">{{ mb_substr($tenant->full_name ?? 'K', 0, 1) }}</div>
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $tenant->full_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $tenant->email ?: 'Chưa có email' }}</p>
                                    @if ($activeContract && $isRepresentative)
                                        <span class="mt-1 inline-flex rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700">Người thuê đại diện · Có tài khoản</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $tenant->cccd }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $tenant->phone }}</td>
                        <td class="px-5 py-4">
                            @if ($tenant->status === \App\Models\Tenant::STATUS_ARCHIVED)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-300"><span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>Đã lưu trữ</span>
                            @elseif ($activeContract)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Phòng {{ $activeContract->room->room_code ?? 'Không có' }}</span>
                            @elseif($hasRentalHistory)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Đã rời phòng</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>Chưa thuê</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ optional($tenant->created_at)->format('d/m/Y') }}</td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.tenants.show', $tenant) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100" title="Xem chi tiết"><i class="bx bx-show text-lg"></i></a>
                                @if ($tenant->status !== \App\Models\Tenant::STATUS_ARCHIVED)
                                    <a href="{{ route('admin.tenants.edit', $tenant) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="Chỉnh sửa"><i class="bx bx-edit text-lg"></i></a>
                                @endif
                                @if ($tenant->status === \App\Models\Tenant::STATUS_ARCHIVED)
                                    <form action="{{ route('admin.tenants.restore', $tenant) }}" method="POST" onsubmit="const reason = prompt('Nhập lý do khôi phục khách thuê (ít nhất 10 ký tự):'); if (!reason || reason.trim().length < 10) { alert('Lý do phải có ít nhất 10 ký tự.'); return false; } this.elements.restoration_reason.value = reason.trim(); return confirm('Xác nhận khôi phục hồ sơ và tài khoản liên kết?');">@csrf @method('PATCH')<input type="hidden" name="restoration_reason"><button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100" title="Khôi phục"><i class="bx bx-revision text-lg"></i></button></form>
                                @elseif (! $activeContract)
                                    <form action="{{ route('admin.tenants.destroy', $tenant) }}" method="POST" onsubmit="const reason = prompt('Nhập lý do lưu trữ khách thuê (ít nhất 10 ký tự):'); if (!reason || reason.trim().length < 10) { alert('Lý do phải có ít nhất 10 ký tự.'); return false; } this.elements.archive_reason.value = reason.trim(); return confirm('Xác nhận lưu trữ hồ sơ? Dữ liệu và giấy tờ sẽ được giữ lại.');">@csrf @method('DELETE')<input type="hidden" name="archive_reason"><button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100" title="Lưu trữ"><i class="bx bx-archive text-lg"></i></button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center"><div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400"><i class="bx bx-user text-2xl"></i></div><p class="mt-3 font-semibold text-slate-900">Không tìm thấy khách thuê</p><p class="mt-1 text-sm text-slate-500">Thử thay đổi từ khóa hoặc trạng thái lọc.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if ($tenants->hasPages())
    <div class="mt-6 flex justify-end" data-tenant-pagination>{{ $tenants->links() }}</div>
@endif
