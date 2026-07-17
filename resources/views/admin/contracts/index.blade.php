@extends('layouts.admin.index')

@section('title', 'Danh sách hợp đồng | Quản lý phòng trọ')
@section('page_title', 'Danh sách hợp đồng')

@php
    $statusOptions = [
        'active' => ['label' => 'Đang thuê', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'terminated' => ['label' => 'Đã kết thúc', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        'expired' => ['label' => 'Hết hạn', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
        'pending' => ['label' => 'Chờ xử lý', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'],
    ];
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Quản lý hợp đồng</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Danh sách hợp đồng</h2>
            </div>

<<<<<<< HEAD
<div class="container-fluid py-4">

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h2 class="fw-bold mb-1">

            Quản lý hợp đồng

        </h2>

        <p class="text-muted mb-0">

            Quản lý hợp đồng thuê phòng của bạn

        </p>

    </div>

    <div class="d-flex align-items-center gap-2">

        <button class="btn btn-light border">

            <i class="bi bi-list-ul"></i>

        </button>

        <button class="btn btn-light border">

            <i class="bi bi-grid-3x3-gap"></i>

        </button>

        <button
            class="btn btn-success px-4"
            data-bs-toggle="modal"
            data-bs-target="#createContractModal">

            <i class="bi bi-plus-lg"></i>

            Tạo hợp đồng mới

        </button>

    </div>

</div>
<div class="row g-3 mb-4">

<div class="col-lg-3">

<div class="card dashboard-card">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">

Tổng hợp đồng

</small>

<h2>

{{ $contracts->count() }}

</h2>

</div>

<div class="icon bg-primary-subtle">

<i class="bi bi-file-earmark-text text-primary"></i>

</div>

</div>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="card dashboard-card">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">

Đang hoạt động

</small>

<h2 class="text-success">

{{ $contracts->where('status',\App\Models\Contract::STATUS_ACTIVE)->count() }}

</h2>

</div>

<div class="icon bg-success-subtle">

<i class="bi bi-check-lg text-success"></i>

</div>

</div>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="card dashboard-card">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">

Sắp hết hạn

</small>

<h2 class="text-danger">

0

</h2>

</div>

<div class="icon bg-danger-subtle">

<i class="bi bi-clock-history text-danger"></i>

</div>

</div>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="card dashboard-card">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<small class="text-muted">

Hết hạn

</small>

<h2>

{{ $contracts->where('status',\App\Models\Contract::STATUS_EXPIRED)->count() }}

</h2>

</div>

<div class="icon bg-secondary-subtle">

<i class="bi bi-file-earmark text-secondary"></i>

</div>

</div>

</div>

</div>

</div>

</div>
<div class="card border-0 shadow-sm">

<div class="card-body">
<form method="GET"
      action="{{ route('admin.contracts.index') }}"
      class="mb-4">

<div class="row g-3 align-items-center">

    {{-- Tìm kiếm --}}
    <div class="col-lg-4">

        <div class="input-group">

            <span class="input-group-text bg-white">

                <i class="bi bi-search"></i>

            </span>

            <input
                type="text"
                name="keyword"
                class="form-control border-start-0"
                placeholder="Tìm kiếm hợp đồng..."
                value="{{ request('keyword') }}">

        </div>

    </div>

    {{-- Trạng thái --}}
    <div class="col-lg-3">

        <select
            name="status"
            class="form-select">

            <option value="">
                Tất cả trạng thái
            </option>

            <option
                value="{{ \App\Models\Contract::STATUS_ACTIVE }}"
                {{ request('status')==\App\Models\Contract::STATUS_ACTIVE?'selected':'' }}>
                Đang hoạt động
            </option>

            <option
                value="{{ \App\Models\Contract::STATUS_PENDING_SIGNATURE }}"
                {{ request('status')==\App\Models\Contract::STATUS_PENDING_SIGNATURE?'selected':'' }}>
                Chờ ký
            </option>

            <option
                value="{{ \App\Models\Contract::STATUS_DRAFT }}"
                {{ request('status')==\App\Models\Contract::STATUS_DRAFT?'selected':'' }}>
                Bản nháp
            </option>

            <option
                value="{{ \App\Models\Contract::STATUS_EXPIRED }}"
                {{ request('status')==\App\Models\Contract::STATUS_EXPIRED?'selected':'' }}>
                Hết hạn
            </option>

            <option
                value="{{ \App\Models\Contract::STATUS_TERMINATED }}"
                {{ request('status')==\App\Models\Contract::STATUS_TERMINATED?'selected':'' }}>
                Đã chấm dứt
            </option>

        </select>

    </div>

    {{-- Button --}}
    <div class="col-lg-5 text-end">

        <button
            class="btn btn-success px-4">

            <i class="bi bi-search"></i>

            Tìm kiếm

        </button>

        <a
            href="{{ route('admin.contracts.index') }}"
            class="btn btn-outline-secondary">

            <i class="bi bi-arrow-clockwise"></i>

            Làm mới

        </a>

    </div>

</div>

</form>
<div class="d-flex gap-2 mb-4 flex-wrap">

<a
href="{{ route('admin.contracts.index') }}"
class="btn btn-sm {{ request('status')==''?'btn-success':'btn-outline-success' }}">

Tất cả

</a>

<a
href="{{ route('admin.contracts.index',['status'=>\App\Models\Contract::STATUS_ACTIVE]) }}"
class="btn btn-sm {{ request('status')==\App\Models\Contract::STATUS_ACTIVE?'btn-success':'btn-outline-success' }}">

Hoạt động

</a>

<a
href="{{ route('admin.contracts.index',['status'=>\App\Models\Contract::STATUS_EXPIRED]) }}"
class="btn btn-sm {{ request('status')==\App\Models\Contract::STATUS_EXPIRED?'btn-danger':'btn-outline-danger' }}">

Hết hạn

</a>

<a
href="{{ route('admin.contracts.index',['status'=>\App\Models\Contract::STATUS_TERMINATED]) }}"
class="btn btn-sm {{ request('status')==\App\Models\Contract::STATUS_TERMINATED?'btn-dark':'btn-outline-dark' }}">

Chấm dứt

</a>

</div>
<div class="table-responsive">

<table class="table table-hover align-middle contract-table">

<thead>

<tr>

<th width="60">

#

</th>

<th>

Hợp đồng

</th>

<th>

Khách thuê

</th>

<th>

Phòng

</th>

<th>

Thời hạn

</th>

<th>

Trạng thái

</th>

<th class="text-center" width="220">

Thao tác

</th>

</tr>

</thead>

<tbody>
@forelse($contracts as $contract)

<tr>
<td>

<div class="fw-bold">

{{ $loop->iteration }}

</div>

</td>
<td>

<div class="d-flex align-items-center">

<div class="contract-icon">

<i class="bi bi-file-earmark-text"></i>

</div>

<div class="ms-3">

<div class="fw-bold">

{{ $contract->contract_code }}

</div>

<small class="text-muted">

Tạo ngày

{{ optional($contract->created_at)->format('d/m/Y') }}

</small>

</div>

</div>

</td>
<td>

<div class="fw-semibold">

{{ $contract->tenant->full_name ?? '-' }}

</div>

<small class="text-muted">

{{ $contract->tenant->phone ?? 'Chưa có SĐT' }}

</small>

</td>
<td>

<div class="fw-bold text-success">

{{ $contract->room->room_code ?? '-' }}

</div>

<small class="text-muted">

{{ number_format($contract->monthly_rent) }} VNĐ

</small>

</td>
<td>

<div>

{{ $contract->start_date->format('d/m/Y') }}

</div>

<small class="text-muted">

↓

</small>

<div>

{{ $contract->end_date->format('d/m/Y') }}

</div>

</td>
<td>

@if($contract->isActive())

<span class="badge rounded-pill bg-success px-3">

Đang hoạt động

</span>

@elseif($contract->isDraft())

<span class="badge rounded-pill bg-secondary px-3">

Bản nháp

</span>

@elseif($contract->isPendingSignature())

<span class="badge rounded-pill bg-warning text-dark px-3">

Chờ ký

</span>

@elseif($contract->isExpired())

<span class="badge rounded-pill bg-danger px-3">

Hết hạn

</span>

@elseif($contract->isTerminated())

<span class="badge rounded-pill bg-dark px-3">

Đã chấm dứt

</span>

@else

<span class="badge rounded-pill bg-info px-3">

Khác

</span>

@endif

</td>
<td class="text-center">
    <div class="d-flex justify-content-center gap-2">

        {{-- Xem --}}
        <button
            type="button"
            class="btn btn-outline-info btn-action btn-view-contract"
            data-url="{{ route('admin.contracts.modal', $contract) }}"
            title="Xem">

            <i class="bi bi-eye"></i>

        </button>

        {{-- Sửa --}}
        @if($contract->canEdit())
        <button
            type="button"
            class="btn btn-outline-primary btn-action editContractBtn"

            data-id="{{ $contract->id }}"
            data-room="{{ $contract->room_id }}"
            data-tenant="{{ $contract->tenant_id }}"
            data-rent="{{ $contract->monthly_rent }}"
            data-deposit="{{ $contract->deposit_amount }}"
            data-start="{{ optional($contract->start_date)->format('Y-m-d') }}"
            data-end="{{ optional($contract->end_date)->format('Y-m-d') }}"
            data-status="{{ $contract->status }}"
            data-content="{{ e($contract->contract_content) }}"
            data-note="{{ $contract->note }}"
            data-image="{{ $contract->contract_file ? asset($contract->contract_file) : '' }}"

            data-bs-toggle="modal"
            data-bs-target="#editContractModal"

            title="Sửa">

            <i class="bi bi-pencil"></i>

        </button>
        @endif

        {{-- Xóa --}}
        @if($contract->isDraft())
        <form action="{{ route('admin.contracts.destroy',$contract->id) }}"
              method="POST"
              onsubmit="return confirm('Bạn chắc chắn muốn xóa?')">

            @csrf
            @method('DELETE')

            <button
                class="btn btn-outline-danger btn-action"
                title="Xóa">
                <i class="bi bi-trash"></i>
            </button>

        </form>
        @endif

    </div>
</td>

</tr>

@empty

<tr>
    <td colspan="7" class="text-center py-5">
        <div class="text-center">
            <i class="bi bi-file-earmark-text display-4 text-secondary"></i>
            <h5 class="mt-3">Chưa có hợp đồng nào</h5>
            <p class="text-muted">Hãy tạo hợp đồng đầu tiên.</p>
        </div>
    </td>
</tr>

@endforelse

</tbody>
</table>

</div>

<div class="d-flex justify-content-between align-items-center mt-4">
    <div class="text-muted">
        Hiển thị <strong>{{ $contracts->count() }}</strong> hợp đồng
    </div>

    <div>
        @if(method_exists($contracts,'links'))
            {{ $contracts->links() }}
        @endif
    </div>
</div>

<div
    class="modal fade"
    id="contractModal">

    <div class="modal-dialog modal-xl modal-dialog-scrollable">

        <div class="modal-content"

            id="contractModalContent">

        </div>

    </div>

</div>

</div> {{-- card-body --}}
</div> {{-- card --}}
</div> {{-- container-fluid --}}

@include('admin.contracts.modal.create')
@include('admin.contracts.modal.edit')

@include('admin.contracts.partials.script')
@include('admin.contracts.partials.edit-script')

@include('admin.contracts.modal.extend-modal')
@include('admin.contracts.modal.terminate-modal')
@include('admin.contracts.modal.return-deposit-modal')

=======
            <a href="{{ route('admin.contracts.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                <i class="bx bx-plus text-lg"></i>
                Tạo hợp đồng mới
            </a>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.contracts.index') }}" class="grid gap-3 md:grid-cols-[1fr_220px_auto] md:items-end">
                <div>
                    <label for="keyword" class="mb-1.5 block text-sm font-semibold text-slate-700">Tìm kiếm</label>
                    <div class="relative">
                        <i class="bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                        <input id="keyword" type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Mã HĐ, người thuê, số phòng..." class="h-11 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    </div>
                </div>

                <div>
                    <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">Trạng thái</label>
                    <select id="status" name="status" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statusOptions as $value => $meta)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800">
                        <i class="bx bx-filter-alt text-lg"></i>
                        Lọc
                    </button>
                    <a href="{{ route('admin.contracts.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Làm mới
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-slate-950">Tất cả hợp đồng</h3>
                    <p class="text-sm text-slate-500">Tìm thấy {{ $contracts->count() }} hợp đồng</p>
                </div>
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
                                $status = $statusOptions[$contract->status] ?? ['label' => $contract->status, 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'];
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950">{{ $contract->contract_code ?: 'HD' . str_pad($contract->id, 3, '0', STR_PAD_LEFT) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Tiền cọc {{ number_format($contract->deposit_amount ?? 0, 0, ',', '.') }}đ</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-medium text-slate-900">{{ $contract->tenant->full_name ?? 'N/A' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $contract->tenant->phone ?? 'Chưa có SĐT' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">
                                        Phòng {{ $contract->room->room_code ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <p>{{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}</p>
                                    <p class="text-xs text-slate-500">đến {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>
                                        {{ $status['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.contracts.show', $contract) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100" title="Xem chi tiết">
                                            <i class="bx bx-show text-lg"></i>
                                        </a>
                                        <a href="{{ route('admin.contracts.print', $contract->id) }}" target="_blank" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="In hợp đồng">
                                            <i class="bx bx-printer text-lg"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <i class="bx bx-file text-2xl"></i>
                                    </div>
                                    <p class="mt-3 font-semibold text-slate-900">Chưa có hợp đồng nào</p>
                                    <p class="mt-1 text-sm text-slate-500">Tạo hợp đồng mới sau khi đã có phòng và khách thuê.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225
@endsection
