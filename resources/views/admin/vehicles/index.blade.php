@extends('layouts.admin.index')

@section('title', 'Quản lý phương tiện | Quản lý phòng trọ')
@section('page_title', 'Quản lý phương tiện')

@php
    $statusLabels = [
        'pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối',
        'cancelled' => 'Đã hủy', 'removed' => 'Đã gỡ',
    ];
    $statusClasses = [
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'cancelled' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'removed' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
    $typeLabels = ['motorcycle' => 'Xe máy', 'electric_motorcycle' => 'Xe máy điện', 'bicycle' => 'Xe đạp'];
    $tabs = [
        'current' => 'Đang quản lý', 'pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối', 'removed' => 'Đã gỡ', 'cancelled' => 'Đã hủy', 'all' => 'Tất cả',
    ];
@endphp

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-2xl font-bold text-slate-950">Danh sách phương tiện</h2>
                <p class="mt-1 text-sm text-slate-500">Theo dõi xe đang gửi, xử lý yêu cầu và tra cứu theo khách thuê hoặc phòng.</p>
            </div>
            <span class="w-fit rounded-full bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700">
                {{ number_format((int) ($counts['approved'] ?? 0)) }} xe đang gửi
            </span>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.vehicles.index', array_filter(['status' => $key, 'search' => $search, 'room_id' => $roomId])) }}"
                   class="rounded-full px-3.5 py-2 text-sm font-semibold {{ $status === $key ? 'bg-indigo-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }} ({{ (int) ($counts[$key] ?? 0) }})
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('admin.vehicles.index') }}" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[minmax(260px,1fr)_220px_auto]">
            <input type="hidden" name="status" value="{{ $status }}">
            <div>
                <label for="vehicle-search" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tìm kiếm</label>
                <div class="relative">
                    <i class="bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                    <input id="vehicle-search" name="search" value="{{ $search }}" placeholder="Biển số, tên xe, khách thuê, phòng..." class="h-11 w-full rounded-lg border border-slate-200 pl-10 pr-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                </div>
            </div>
            <div>
                <label for="vehicle-room" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phòng hiện tại</label>
                <select id="vehicle-room" name="room_id" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Tất cả phòng</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" @selected($roomId === $room->id)>Phòng {{ $room->room_code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="h-11 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">Lọc danh sách</button>
                @if($search !== '' || $roomId)
                    <a href="{{ route('admin.vehicles.index', ['status' => $status]) }}" class="inline-flex h-11 items-center rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">Xóa lọc</a>
                @endif
            </div>
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Phương tiện</th>
                            <th class="px-5 py-3">Chủ xe</th>
                            <th class="px-5 py-3">Phòng / Hợp đồng</th>
                            <th class="px-5 py-3">Xét duyệt</th>
                            <th class="px-5 py-3">Trạng thái / Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($vehicles as $vehicle)
                            @php
                                $currentContract = $vehicle->tenant?->memberContracts?->first()
                                    ?? $vehicle->tenant?->contracts?->first();
                            @endphp
                            <tr id="vehicle-{{ $vehicle->id }}" class="align-top hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <div class="flex min-w-64 gap-3">
                                        @if($vehicle->imageExists())
                                            <a href="{{ route('admin.vehicles.image', $vehicle) }}" data-image-modal data-image-title="Ảnh {{ $vehicle->vehicle_name ?: 'phương tiện' }}" class="shrink-0">
                                                <img src="{{ route('admin.vehicles.image', $vehicle) }}" alt="Ảnh phương tiện" class="h-16 w-24 rounded-lg object-cover ring-1 ring-slate-200">
                                            </a>
                                        @elseif($vehicle->vehicle_image)
                                            <span class="flex h-16 w-24 shrink-0 items-center justify-center rounded-lg bg-amber-50 px-2 text-center text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Ảnh không còn tồn tại</span>
                                        @else
                                            <span class="flex h-16 w-24 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200"><i class="bx bx-cycling text-2xl"></i></span>
                                        @endif
                                        <span>
                                            <span class="block font-bold text-slate-950">{{ $vehicle->display_license_plate ?: 'Không có biển số' }}</span>
                                            <span class="mt-1 block text-sm text-slate-600">{{ $typeLabels[$vehicle->vehicle_type] ?? 'Phương tiện' }} · {{ $vehicle->vehicle_name ?: 'Chưa ghi tên xe' }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <a href="{{ route('admin.tenants.show', $vehicle->tenant) }}" class="font-semibold text-indigo-700 hover:underline">{{ $vehicle->tenant?->full_name ?? 'Không xác định' }}</a>
                                    <span class="mt-1 block text-slate-500">{{ $vehicle->tenant?->phone ?: 'Chưa có SĐT' }}</span>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    @if($currentContract?->room)
                                        <a href="{{ route('admin.rooms.show', $currentContract->room) }}" class="font-semibold text-slate-950 hover:text-indigo-700 hover:underline">Phòng {{ $currentContract->room->room_code }}</a>
                                        <a href="{{ route('admin.contracts.show', $currentContract) }}" class="mt-1 block text-indigo-700 hover:underline">{{ $currentContract->contract_code }}</a>
                                    @else
                                        <span class="font-semibold text-amber-700">Không có phòng đang ở</span>
                                        <span class="mt-1 block text-xs text-slate-500">Dữ liệu xe được giữ để tra cứu lịch sử.</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    @if($vehicle->reviewed_at)
                                        <span class="block">{{ $vehicle->reviewed_at->format('d/m/Y H:i') }}</span>
                                        <span class="mt-1 block text-xs">{{ $vehicle->reviewer?->name ?? 'Tài khoản đã xóa' }}</span>
                                    @else
                                        Chưa xét duyệt
                                    @endif
                                    @if($vehicle->review_note)<span class="mt-2 block max-w-64 text-xs text-rose-700">{{ $vehicle->review_note }}</span>@endif
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClasses[$vehicle->status] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                        {{ $statusLabels[$vehicle->status] ?? $vehicle->status }}
                                    </span>
                                    @if(in_array($vehicle->status, [\App\Models\Vehicle::STATUS_PENDING, \App\Models\Vehicle::STATUS_REJECTED], true))
                                        <form method="POST" action="{{ route('admin.vehicles.review', $vehicle) }}" class="mt-3 min-w-64 space-y-2">
                                            @csrf @method('PUT')
                                            <input name="review_note" maxlength="500" placeholder="Nhập lý do khi từ chối" class="h-9 w-full rounded-lg border border-slate-200 px-2.5 text-xs outline-none focus:border-indigo-500">
                                            <div class="flex gap-2">
                                                <button name="status" value="approved" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Duyệt</button>
                                                <button name="status" value="rejected" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">Từ chối</button>
                                            </div>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-16 text-center"><i class="bx bx-cycling text-4xl text-slate-300"></i><p class="mt-2 font-semibold text-slate-700">Không tìm thấy phương tiện</p><p class="mt-1 text-sm text-slate-500">Thử thay đổi trạng thái hoặc điều kiện tìm kiếm.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{ $vehicles->links() }}
    </div>
@endsection
