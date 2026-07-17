@extends('layouts.admin.home')

@section('content')

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

@endsection
