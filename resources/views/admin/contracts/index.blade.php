@extends('layouts.admin.index')

@section('title', 'Quản lý hợp đồng')
@section('page_title', 'Quản lý phòng trọ')

@section('content')

<div class="space-y-6">

    {{-- HEADER - cùng style với trang Yêu cầu gia hạn --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Quản lý hợp đồng</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Quản lý hợp đồng</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý hợp đồng thuê phòng của bạn.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50"
                    title="Danh sách">
                <i class="bi bi-list-ul"></i>
            </button>

            <button type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50"
                    title="Lưới">
                <i class="bi bi-grid-3x3-gap"></i>
            </button>

            <button type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                    data-bs-toggle="modal"
                    data-bs-target="#createContractModal">
                <i class="bi bi-plus-lg"></i>
                Tạo hợp đồng mới
            </button>
        </div>
    </div>

    {{-- THỐNG KÊ - cùng style với trang Yêu cầu gia hạn --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Tổng hợp đồng</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $contracts->count() }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-lg text-indigo-600">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Đang hoạt động</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-700">{{ $contracts->where('status', \App\Models\Contract::STATUS_ACTIVE)->count() }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-xl font-bold text-emerald-700">
                    <i class="bi bi-check-lg"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-rose-200 bg-rose-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-rose-700">Sắp hết hạn</p>
                    <p class="mt-2 text-3xl font-bold text-rose-700">0</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-100 text-xl text-rose-700">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Hết hạn</p>
                    <p class="mt-2 text-3xl font-bold text-slate-700">{{ $contracts->where('status', \App\Models\Contract::STATUS_EXPIRED)->count() }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-200 text-xl text-slate-600">
                    <i class="bi bi-file-earmark"></i>
                </div>
            </div>
        </div>

    </div>

    {{-- Từ đây giữ nguyên toàn bộ filter, trạng thái, bảng, thao tác, modal và script hiện có --}}
    <div class="contract-list-bootstrap">
<div class="card border-0 shadow-sm">

<div class="card-body">
<form method="GET"
      action="{{ route('admin.contracts.index') }}"
      class="mb-4 contract-list-filter">

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
        class="form-select"
        onchange="this.form.submit()">

        <option value="">
            Tất cả trạng thái
        </option>

        <option
            value="{{ \App\Models\Contract::STATUS_DRAFT }}"
            {{ request('status') == \App\Models\Contract::STATUS_DRAFT ? 'selected' : '' }}>
            Bản nháp
        </option>

        <option
            value="{{ \App\Models\Contract::STATUS_PENDING_SIGNATURE }}"
            {{ request('status') == \App\Models\Contract::STATUS_PENDING_SIGNATURE ? 'selected' : '' }}>
            Chờ ký
        </option>

        <option
            value="{{ \App\Models\Contract::STATUS_SIGNED }}"
            {{ request('status') == \App\Models\Contract::STATUS_SIGNED ? 'selected' : '' }}>
            Đã ký
        </option>

        <option
            value="{{ \App\Models\Contract::STATUS_DEPOSIT_PAID }}"
            {{ request('status') == \App\Models\Contract::STATUS_DEPOSIT_PAID ? 'selected' : '' }}>
            Đã xác nhận cọc
        </option>

        <option
            value="{{ \App\Models\Contract::STATUS_ACTIVE }}"
            {{ request('status') == \App\Models\Contract::STATUS_ACTIVE ? 'selected' : '' }}>
            Đang hoạt động
        </option>

        <option
            value="{{ \App\Models\Contract::STATUS_EXPIRED }}"
            {{ request('status') == \App\Models\Contract::STATUS_EXPIRED ? 'selected' : '' }}>
            Hết hạn
        </option>

        <option
            value="{{ \App\Models\Contract::STATUS_TERMINATED }}"
            {{ request('status') == \App\Models\Contract::STATUS_TERMINATED ? 'selected' : '' }}>
            Đã chấm dứt
        </option>

        <option
            value="{{ \App\Models\Contract::STATUS_DEPOSIT_RETURNED }}"
            {{ request('status') == \App\Models\Contract::STATUS_DEPOSIT_RETURNED ? 'selected' : '' }}>
            Đã hoàn cọc
        </option>

        <option
            value="{{ \App\Models\Contract::STATUS_COMPLETED }}"
            {{ request('status') == \App\Models\Contract::STATUS_COMPLETED ? 'selected' : '' }}>
            Hoàn tất
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

    @if($contract->status === \App\Models\Contract::STATUS_ACTIVE)

        <span class="badge rounded-pill bg-success px-3">
            Đang hoạt động
        </span>

    @elseif($contract->status === \App\Models\Contract::STATUS_DRAFT)

        <span class="badge rounded-pill bg-secondary px-3">
            Bản nháp
        </span>

    @elseif($contract->status === \App\Models\Contract::STATUS_PENDING_SIGNATURE)

        <span class="badge rounded-pill bg-warning text-dark px-3">
            Chờ ký
        </span>

    @elseif($contract->status === \App\Models\Contract::STATUS_SIGNED)

        <span class="badge rounded-pill bg-primary px-3">
            Đã ký
        </span>

    @elseif($contract->status === \App\Models\Contract::STATUS_DEPOSIT_PAID)

        <span class="badge rounded-pill bg-info text-dark px-3">
            Đã xác nhận cọc
        </span>

    @elseif($contract->status === \App\Models\Contract::STATUS_EXPIRED)

        <span class="badge rounded-pill bg-danger px-3">
            Hết hạn
        </span>

    @elseif($contract->status === \App\Models\Contract::STATUS_TERMINATED)

        <span class="badge rounded-pill bg-dark px-3">
            Đã chấm dứt
        </span>

    @elseif($contract->status === \App\Models\Contract::STATUS_DEPOSIT_RETURNED)

        <span class="badge rounded-pill bg-info px-3">
            Đã hoàn cọc
        </span>

    @elseif($contract->status === \App\Models\Contract::STATUS_COMPLETED)

        <span class="badge rounded-pill bg-success px-3">
            Hoàn tất
        </span>

    @else

        <span class="badge rounded-pill bg-secondary px-3">
            Không xác định
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
</div> {{-- contract-list-bootstrap --}}
</div> {{-- page content --}}

@include('admin.contracts.modal.create')
@include('admin.contracts.modal.edit')

@include('admin.contracts.partials.script')
@include('admin.contracts.partials.edit-script')

@include('admin.contracts.modal.extend-modal')
@include('admin.contracts.modal.terminate-modal')
@include('admin.contracts.modal.return-deposit-modal')

@include('admin.contracts.modal.recall-modal')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
@endpush
@endsection