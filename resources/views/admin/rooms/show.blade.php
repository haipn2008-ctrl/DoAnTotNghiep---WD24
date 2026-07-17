@extends('layouts.admin.index')

@section('title', 'Chi tiết phòng | Quản lý phòng trọ')
@section('page_title', 'Chi tiết phòng')

@php
    $statusOptions = [
        'available' => ['label' => 'Trống', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'occupied' => ['label' => 'Đang thuê', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        'maintenance' => ['label' => 'Bảo trì', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
    ];
    $status = $statusOptions[$room->status] ?? ['label' => ucfirst($room->status), 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'];
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Phòng {{ $room->room_code }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Chi tiết phòng</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.rooms.edit', $room) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-edit text-lg"></i>
                    Cập nhật
                </a>
                <a href="{{ route('admin.rooms.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-arrow-back text-lg"></i>
                    Quay lại
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                @if ($room->thumbnail)
                    <img src="{{ asset('storage/' . $room->thumbnail) }}" alt="Phòng {{ $room->room_code }}" class="h-72 w-full object-cover">
                @else
                    <div class="flex h-72 w-full items-center justify-center bg-slate-100 text-slate-400">
                        <i class="bx bx-image text-5xl"></i>
                    </div>
                @endif

                <div class="space-y-4 p-5">
                    <div>
                        <p class="text-sm text-slate-500">Mã phòng</p>
                        <p class="mt-1 text-2xl font-bold text-slate-950">{{ $room->room_code }}</p>
                    </div>

                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>
                        {{ $status['label'] }}
                    </span>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Thông tin phòng</h3>
                    <p class="text-sm text-slate-500">Thông tin giá thuê, sức chứa và mô tả.</p>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Tầng</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">Tầng {{ $room->floor }}</p>
                    </div>

                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Giá thuê</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">{{ number_format($room->price, 0, ',', '.') }}đ</p>
                    </div>

                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Diện tích</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">{{ $room->area }} m²</p>
                    </div>

                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Số người hiện tại</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">{{ $room->current_people }}/{{ $room->max_people ?? 4 }} người</p>
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-sm font-semibold text-slate-700">Mô tả</p>
                        <p class="mt-2 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-600">
                            {{ $room->description ?: 'Chưa có mô tả cho phòng này.' }}
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Tiện ích phòng</h3>
                <p class="text-sm text-slate-500">Các tiện ích đã gắn với phòng này.</p>
            </div>

            <div class="p-5">
                @forelse ($room->amenities as $amenity)
                    <span class="mb-2 mr-2 inline-flex items-center rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200">
                        <i class="bx bx-check mr-1 text-base"></i>
                        {{ $amenity->name }}
                    </span>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                        Chưa có tiện ích nào.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
