@extends('layouts.admin.home')

@section('content')

<div class="container-fluid py-4">
    <div class="card shadow-sm">

    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h4 class="mb-0">
            📄 Danh sách hợp đồng
        </h4>

        <a href="{{ route('admin.contracts.create') }}"
           class="btn btn-primary">
            + Tạo hợp đồng mới
        </a>
    </div>

    <div class="card-body">

        {{-- Tìm kiếm + Lọc --}}
        <form method="GET"
            action="{{ route('admin.contracts.index') }}">

            <div class="row mb-3">

                <div class="col-md-4">
                    <input type="text"
                        name="keyword"
                        class="form-control"
                        placeholder="Tìm theo mã HĐ, người thuê, số phòng"
                        value="{{ request('keyword') }}">
                </div>

                <div class="col-md-3">
                    <select name="status"
                            class="form-select">

                        <option value="">
                            Tất cả trạng thái
                        </option>

                        <option value="active"
                            {{ request('status') == 'active' ? 'selected' : '' }}>
                            Đang thuê
                        </option>

                        <option value="terminated"
                            {{ request('status') == 'terminated' ? 'selected' : '' }}>
                            Đã kết thúc
                        </option>

                        <option value="expired"
                            {{ request('status') == 'expired' ? 'selected' : '' }}>
                            Đã hết hạn
                        </option>

                        <option value="pending"
                            {{ request('status') == 'pending' ? 'selected' : '' }}>
                            Chờ xử lý
                        </option>


                    </select>
                </div>

                <div class="col-md-3">

                <button type="submit"
                        class="btn btn-primary">
                        Tìm kiếm
                 </button>

                    <a href="{{ route('admin.contracts.index') }}"
                    class="btn btn-secondary">
                        Reset
                    </a>

                </div>

            </div>

        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">

                <thead class="table-primary">
                    <tr>
                        <th>STT</th>
                        <th>Mã HĐ</th>
                        <th>Người thuê</th>
                        <th>Phòng</th>
                        <th>Ngày bắt đầu</th>
                        <th>Ngày kết thúc</th>
                        <th>Trạng thái</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($contracts as $contract)

                <tr>

                    <td>
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        HD{{ str_pad($contract->id, 3, '0', STR_PAD_LEFT) }}
                    </td>

                    <td>
                        {{ $contract->tenant->full_name ?? 'N/A' }}
                    </td>

                    <td>
                        <span class="badge bg-info text-dark">
                            {{ $contract->room->room_code ?? 'N/A' }}
                        </span>
                    </td>

                    <td>
                        {{ date('d/m/Y', strtotime($contract->start_date)) }}
                    </td>

                    <td>
                        {{ date('d/m/Y', strtotime($contract->end_date)) }}
                    </td>

                    <td>
                        @if($contract->status == 'active')
                            <span class="badge bg-success">
                                Đang thuê
                            </span>
                        @elseif($contract->status == 'terminated')
                            <span class="badge bg-danger">
                                Đã kết thúc
                            </span>

                        @elseif($contract->status == 'expired')
                            <span class="badge bg-warning text-dark">
                                Hết hạn
                            </span>

                        @elseif($contract->status == 'pending')
                            <span class="badge bg-secondary">
                                Chờ xử lý
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                {{ $contract->status }}
                            </span>
                        @endif
                    </td>

                    <td class="text-center">

                        <a href="{{ route('admin.contracts.show', $contract->id) }}"
                           class="btn btn-sm btn-info text-white">
                            Chi tiết
                        </a>

                        <a href="{{ route('admin.contracts.print', $contract->id) }}"
                           class="btn btn-sm btn-warning text-white"
                           target="_blank">
                            In
                        </a>

                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="8" class="text-center text-muted">
                        Chưa có hợp đồng nào
                    </td>
                </tr>

                @endforelse

                </tbody>

            </table>
        </div>

    </div>

</div>

</div>

@endsection
