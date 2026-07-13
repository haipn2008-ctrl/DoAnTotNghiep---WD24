@extends('layouts.admin.index')

@section('title', 'Chi tiết khách thuê | Quản lý phòng trọ')
@section('page_title', 'Chi tiết khách thuê')

@php
    $tenant->loadMissing(['user', 'contracts.room']);
    $activeContract = $tenant->contracts->where('status', 'active')->first();
    $contractStatusLabels = [
        'pending' => ['label' => 'Chờ xử lý', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'],
        'active' => ['label' => 'Đang hiệu lực', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'expired' => ['label' => 'Hết hạn', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'terminated' => ['label' => 'Đã kết thúc', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
    ];
    $infoItems = [
        ['label' => 'Ngày sinh', 'value' => $tenant->date_of_birth ? \Carbon\Carbon::parse($tenant->date_of_birth)->format('d/m/Y') : 'Chưa cập nhật'],
        ['label' => 'CCCD', 'value' => $tenant->cccd],
        ['label' => 'Ngày cấp CCCD', 'value' => $tenant->cccd_issue_date ? \Carbon\Carbon::parse($tenant->cccd_issue_date)->format('d/m/Y') : 'Chưa cập nhật'],
        ['label' => 'Nơi cấp CCCD', 'value' => $tenant->cccd_issue_place ?: 'Chưa cập nhật'],
        ['label' => 'Số điện thoại', 'value' => $tenant->phone],
        ['label' => 'Email', 'value' => $tenant->email ?: 'Chưa cập nhật'],
        ['label' => 'Địa chỉ', 'value' => $tenant->address ?: 'Chưa cập nhật'],
        ['label' => 'Tài khoản', 'value' => $tenant->user ? $tenant->user->name . ' (' . $tenant->user->email . ')' : 'Chưa gắn tài khoản'],
    ];
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Hồ sơ khách thuê</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">{{ $tenant->full_name }}</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.tenants.edit', $tenant) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-edit text-lg"></i>
                    Cập nhật
                </a>
                <a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-arrow-back text-lg"></i>
                    Quay lại
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
            <section class="rounded-lg border border-slate-200 bg-white p-5 text-center shadow-sm">
                <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-indigo-50 text-4xl font-bold text-indigo-700 ring-1 ring-indigo-100">
                    {{ mb_substr($tenant->full_name ?? 'K', 0, 1) }}
                </div>
                <h3 class="mt-4 text-xl font-bold text-slate-950">{{ $tenant->full_name }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $tenant->phone }}</p>

                <div class="mt-5">
                    @if ($activeContract)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Đang thuê phòng {{ $activeContract->room->room_code ?? 'N/A' }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                            Chưa thuê phòng
                        </span>
                    @endif
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Thông tin cá nhân</h3>
                    <p class="text-sm text-slate-500">Thông tin định danh và liên hệ.</p>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    @foreach ($infoItems as $item)
                        <div class="{{ $item['label'] === 'Địa chỉ' ? 'sm:col-span-2' : '' }} rounded-lg bg-slate-50 p-4">
                            <p class="text-sm font-medium text-slate-500">{{ $item['label'] }}</p>
                            <p class="mt-2 break-words font-semibold text-slate-950">{{ $item['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Lịch sử hợp đồng</h3>
                <p class="text-sm text-slate-500">Các hợp đồng đã gắn với khách thuê này.</p>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($tenant->contracts as $contract)
                    @php($contractStatus = $contractStatusLabels[$contract->status] ?? ['label' => $contract->status, 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'])
                    <div class="flex flex-col justify-between gap-3 px-5 py-4 sm:flex-row sm:items-center">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $contract->contract_code }}</p>
                            <p class="mt-1 text-sm text-slate-500">
                                Phòng {{ $contract->room->room_code ?? 'N/A' }} ·
                                {{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') : '---' }}
                                -
                                {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') : '---' }}
                            </p>
                        </div>
                        <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $contractStatus['class'] }}">{{ $contractStatus['label'] }}</span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-500">Khách thuê chưa có hợp đồng.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
