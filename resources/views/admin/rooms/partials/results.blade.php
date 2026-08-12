@php
    $statusOptions = [
        'available' => ['label' => 'Trống', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'occupied' => ['label' => 'Đang thuê', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        'maintenance' => ['label' => 'Bảo trì', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
    ];
@endphp

<section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
            <h3 class="font-semibold text-slate-950">Phòng phù hợp</h3>
            <p class="text-sm text-slate-500">Tìm thấy {{ $rooms->total() }} phòng, hiển thị {{ $rooms->count() }} phòng trên trang này</p>
        </div>
        <span data-room-loading class="hidden text-sm font-semibold text-indigo-600"><i class="bx bx-loader-alt mr-1 animate-spin"></i>Đang lọc</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Phòng</th>
                    <th class="px-5 py-3">Tầng</th>
                    <th class="px-5 py-3">Giá thuê</th>
                    <th class="px-5 py-3">Diện tích</th>
                    <th class="px-5 py-3">Số người</th>
                    <th class="px-5 py-3">Trạng thái</th>
                    <th class="px-5 py-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rooms as $room)
                    @php($status = $statusOptions[$room->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'])
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                @if ($room->thumbnail)
                                    <img src="{{ asset('storage/' . $room->thumbnail) }}" alt="Phòng {{ $room->room_code }}" class="h-12 w-12 rounded-lg object-cover ring-1 ring-slate-200">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200"><i class="bx bx-image text-xl"></i></div>
                                @endif
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $room->room_code }}</p>
                                    <p class="text-xs text-slate-500">{{ $room->amenities->count() }} tiện ích</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">Tầng {{ $room->floor }}</td>
                        <td class="px-5 py-4 font-semibold text-slate-950">{{ number_format($room->price, 0, ',', '.') }}đ</td>
                        <td class="px-5 py-4 text-slate-600">{{ $room->area }} m²</td>
                        <td class="px-5 py-4 text-slate-600">{{ $room->current_people }}/{{ $room->max_people ?? 4 }} người</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>{{ $status['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.rooms.show', $room) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100" title="Xem chi tiết"><i class="bx bx-show text-lg"></i></a>
                                <a href="{{ route('admin.rooms.edit', $room) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="Chỉnh sửa"><i class="bx bx-edit text-lg"></i></a>
                                @if ($room->contracts_count === 0 && $room->utility_readings_count === 0)
                                    <form action="{{ route('admin.rooms.destroy', $room) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa phòng này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100" title="Xóa"><i class="bx bx-trash text-lg"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400"><i class="bx bx-building-house text-2xl"></i></div>
                            <p class="mt-3 font-semibold text-slate-900">Không tìm thấy phòng</p>
                            <p class="mt-1 text-sm text-slate-500">Thử thay đổi mã phòng hoặc trạng thái lọc.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if ($rooms->hasPages())
    <div class="mt-6 flex justify-end" data-room-pagination>{{ $rooms->links() }}</div>
@endif
