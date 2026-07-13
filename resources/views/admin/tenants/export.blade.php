@extends('layouts.admin.index')

@section('title', 'Xuất danh sách khách thuê | Quản lý phòng trọ')
@section('page_title', 'Xuất danh sách khách thuê')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Báo cáo khách thuê</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Xuất danh sách khách thuê</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.tenants.export.download') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    <i class="bx bx-download text-lg"></i>
                    Tải CSV
                </a>
                <a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-arrow-back text-lg"></i>
                    Danh sách khách thuê
                </a>
            </div>
        </div>

        <section class="rounded-lg border border-emerald-200 bg-emerald-50 p-5">
            <div class="flex gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-emerald-600 ring-1 ring-emerald-200">
                    <i class="bx bx-file text-xl"></i>
                </span>
                <div>
                    <h3 class="font-semibold text-emerald-950">File CSV danh sách khách thuê</h3>
                    <p class="mt-1 text-sm leading-6 text-emerald-800">
                        File tải xuống gồm họ tên, CCCD, số điện thoại, email, địa chỉ, phòng thuê và trạng thái.
                    </p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Dữ liệu xem trước</h3>
                <p class="text-sm text-slate-500">Hiển thị {{ $tenants->count() }} khách thuê trên trang này.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Khách thuê</th>
                            <th class="px-5 py-3">CCCD</th>
                            <th class="px-5 py-3">SĐT</th>
                            <th class="px-5 py-3">Phòng thuê</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="px-5 py-3">Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($tenants as $tenant)
                            @php
                                $activeRoom = $tenant->contracts
                                    ->where('status', 'active')
                                    ->pluck('room.room_code')
                                    ->first();
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4 font-semibold text-slate-950">{{ $tenant->full_name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $tenant->cccd }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $tenant->phone }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $activeRoom ?? '-' }}</td>
                                <td class="px-5 py-4">
                                    @if ($activeRoom)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Đang thuê</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">Chưa thuê</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ optional($tenant->created_at)->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">Chưa có khách thuê nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex justify-end">
            {{ $tenants->withQueryString()->links() }}
        </div>
    </div>
@endsection
