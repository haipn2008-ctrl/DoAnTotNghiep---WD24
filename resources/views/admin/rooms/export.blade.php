@extends('layouts.admin.index')

@section('title', 'Xuất danh sách phòng | Quản lý phòng trọ')
@section('page_title', 'Xuất danh sách phòng')

@php
    $statusOptions = [
        'available' => ['label' => 'Trống', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'occupied' => ['label' => 'Đang thuê', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'maintenance' => ['label' => 'Bảo trì', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
    ];
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Báo cáo phòng</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Xuất danh sách phòng</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.rooms.export.download', request()->only(['room_code', 'status'])) }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    <i class="bx bx-download text-lg"></i>
                    Tải CSV
                </a>
                <a href="{{ route('admin.rooms.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-arrow-back text-lg"></i>
                    Danh sách phòng
                </a>
            </div>
        </div>

        <section class="rounded-lg border border-emerald-200 bg-emerald-50 p-5">
            <div class="flex gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-emerald-600 ring-1 ring-emerald-200">
                    <i class="bx bx-file text-xl"></i>
                </span>
                <div>
                    <h3 class="font-semibold text-emerald-950">File CSV danh sách phòng</h3>
                    <p class="mt-1 text-sm leading-6 text-emerald-800">
                        File tải xuống gồm mã phòng, tầng, giá thuê, diện tích, số người, trạng thái và tiện ích.
                    </p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Dữ liệu xem trước</h3>
                <p class="text-sm text-slate-500">Hiển thị {{ $rooms->count() }} phòng trên trang này.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Mã phòng</th>
                            <th class="px-5 py-3">Tầng</th>
                            <th class="px-5 py-3">Giá thuê</th>
                            <th class="px-5 py-3">Diện tích</th>
                            <th class="px-5 py-3">Số người</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="px-5 py-3">Tiện ích</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rooms as $room)
                            @php($status = $statusOptions[$room->status] ?? ['label' => ucfirst($room->status), 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'])
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4 font-semibold text-slate-950">{{ $room->room_code }}</td>
                                <td class="px-5 py-4 text-slate-600">Tầng {{ $room->floor }}</td>
                                <td class="px-5 py-4 font-semibold text-slate-950">{{ number_format($room->price, 0, ',', '.') }}đ</td>
                                <td class="px-5 py-4 text-slate-600">{{ $room->area }} m²</td>
                                <td class="px-5 py-4 text-slate-600">{{ $room->current_people }}/{{ $room->max_people ?? 4 }} người</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">{{ $status['label'] }}</span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $room->amenities->pluck('name')->join(', ') ?: 'Không có' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-slate-500">Không có phòng để xuất.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex justify-end">
            {{ $rooms->withQueryString()->links() }}
        </div>
    </div>
@endsection
