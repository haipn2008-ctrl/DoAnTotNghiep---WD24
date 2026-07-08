@extends('layouts.admin.index')

@section('title', 'Sinh hóa đơn')

@section('content')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">
                    <i class="fas fa-file-invoice-dollar text-primary"></i>
                    Sinh hóa đơn
                </h3>
                <small class="text-muted">
                    Phát hành hóa đơn tiền phòng, điện nước và dịch vụ.
                </small>
            </div>

            <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Danh sách hóa đơn
            </a>
        </div>

        <div class="card shadow-sm mb-4">

            <div class="card-header bg-primary text-white">
                <strong>Chọn kỳ hóa đơn</strong>
            </div>

            <div class="card-body">

                <form method="GET">

                    <div class="row">

                        <div class="col-md-4">

                            <label class="form-label">
                                Tháng
                            </label>

                            <select name="month" class="form-select" onchange="this.form.submit()">

                                @for($m = 1; $m <= 12; $m++)

                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>

                                        Tháng {{ $m }}

                                    </option>

                                @endfor

                            </select>

                        </div>

                        <div class="col-md-4">

                            <label class="form-label">
                                Năm
                            </label>

                            <select name="year" class="form-select" onchange="this.form.submit()">

                                @for($y = date('Y') - 2; $y <= date('Y') + 2; $y++)

                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>

                                        {{ $y }}

                                    </option>

                                @endfor

                            </select>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        <div class="card shadow-sm">

            <div class="card-header">

                <strong>

                    Danh sách hợp đồng đang thuê

                </strong>

            </div>

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0 align-middle">

                        <thead class="table-light">

                            <tr>

                                <th width="60">#</th>

                                <th>Phòng</th>

                                <th>Khách thuê</th>

                                <th>Hợp đồng</th>

                                <th>Tiền phòng</th>

                                <th>Trạng thái</th>

                                <th width="220">Thao tác</th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($contracts as $contract)

                                @php

                                    $issued = in_array(
                                        $contract->room_id,
                                        $issuedRoomIds
                                    );

                                @endphp

                                <tr>

                                    <td>

                                        {{ $loop->iteration }}

                                    </td>

                                    <td>

                                        {{ $contract->room->room_code }}

                                    </td>

                                    <td>

                                        {{ $contract->tenant->full_name }}

                                    </td>

                                    <td>

                                        {{ $contract->contract_code }}

                                    </td>

                                    <td>

                                        {{ number_format($contract->monthly_rent) }}

                                        đ

                                    </td>

                                    <td>

                                        @if($issued)

                                            <span class="badge bg-success">

                                                Đã phát hành

                                            </span>

                                        @else

                                            <span class="badge bg-warning text-dark">

                                                Chưa phát hành

                                            </span>

                                        @endif

                                    </td>

                                    <td>

                                        @if(!$issued)

                                            <button class="btn btn-info btn-sm preview-btn" data-id="{{ $contract->id }}">

                                                <i class="fas fa-eye"></i>

                                                Xem trước

                                            </button>

                                            <button class="btn btn-primary btn-sm issue-btn" data-id="{{ $contract->id }}">

                                                <i class="fas fa-file-invoice"></i>

                                                Phát hành

                                            </button>

                                        @else

                                            <span class="text-success">

                                                ✔ Đã tạo hóa đơn

                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="7" class="text-center py-5">

                                        Không có hợp đồng nào.

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>
    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">

        <div class="modal-dialog modal-xl">

            <div class="modal-content">

                <div class="modal-header bg-primary text-white">

                    <h5 class="modal-title">

                        <i class="fas fa-file-invoice-dollar me-2"></i>

                        Xem trước hóa đơn

                    </h5>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="row mb-3">

                        <div class="col-md-6">

                            <table class="table table-sm">

                                <tr>

                                    <th width="160">

                                        Phòng

                                    </th>

                                    <td id="preview-room"></td>

                                </tr>

                                <tr>

                                    <th>

                                        Khách thuê

                                    </th>

                                    <td id="preview-tenant"></td>

                                </tr>

                                <tr>

                                    <th>

                                        Hợp đồng

                                    </th>

                                    <td id="preview-contract"></td>

                                </tr>

                            </table>

                        </div>

                        <div class="col-md-6">

                            <table class="table table-sm">

                                <tr>

                                    <th width="160">

                                        Tháng

                                    </th>

                                    <td id="preview-month"></td>

                                </tr>

                                <tr>

                                    <th>

                                        Ngày lập

                                    </th>

                                    <td id="preview-date"></td>

                                </tr>

                                <tr>

                                    <th>

                                        Hạn thanh toán

                                    </th>

                                    <td id="preview-due"></td>

                                </tr>

                            </table>

                        </div>

                    </div>

                    <table class="table table-bordered">

                        <thead class="table-light">

                            <tr>

                                <th>

                                    Khoản thu

                                </th>

                                <th width="100">

                                    SL

                                </th>

                                <th width="100">

                                    ĐVT

                                </th>

                                <th width="170">

                                    Đơn giá

                                </th>

                                <th width="180">

                                    Thành tiền

                                </th>

                            </tr>

                        </thead>

                        <tbody id="preview-lines">

                        </tbody>

                        <tfoot>

                            <tr>

                                <th colspan="4" class="text-end">

                                    Tổng cộng

                                </th>

                                <th class="text-danger fs-5" id="preview-total">

                                </th>

                            </tr>

                        </tfoot>

                    </table>

                </div>

                <div class="modal-footer">

                    <button class="btn btn-secondary" data-bs-dismiss="modal">

                        Đóng

                    </button>

                    <button class="btn btn-success" id="btnIssueInvoice">

                        <i class="fas fa-check"></i>

                        Phát hành hóa đơn

                    </button>

                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script>

        let selectedContract = null;

        const month = {{ $month }};

        const year = {{ $year }};

        const previewModal = new bootstrap.Modal(
            document.getElementById('previewModal')
        );

    </script>
    <script>

        /*
        |--------------------------------------------------------------------------
        | Phát hành hóa đơn
        |--------------------------------------------------------------------------
        */

        document.getElementById("btnIssue")
            .addEventListener("click", function () {

                if (!selectedContract) {

                    alert("Chưa chọn hợp đồng.");

                    return;

                }

                this.disabled = true;

                this.innerHTML =
                    '<span class="spinner-border spinner-border-sm"></span> Đang phát hành...';

                fetch(
                    `/admin/invoices/contracts/${selectedContract}/issue`,
                    {

                        method: "POST",

                        headers: {

                            "Content-Type": "application/json",

                            "Accept": "application/json",

                            "X-CSRF-TOKEN":
                                document.querySelector(
                                    'meta[name="csrf-token"]'
                                ).content

                        },

                        body: JSON.stringify({

                            month: month,

                            year: year

                        })

                    }
                )

                    .then(async response => {

                        const data = await response.json();

                        if (!response.ok) {

                            throw data;

                        }

                        return data;

                    })

                    .then(function (data) {

                        previewModal.hide();

                        alert(data.message);

                        window.location.reload();

                    })

                    .catch(function (error) {

                        alert(error.message ?? "Có lỗi xảy ra.");

                    })

                    .finally(() => {

                        document.getElementById("btnIssue").disabled = false;

                        document.getElementById("btnIssue").innerHTML =
                            '<i class="bi bi-check-circle"></i> Phát hành hóa đơn';

                    });

            });

    </script>
@endpush
<script>

    let selectedContract = null;

    const month = {{ $month }};

    const year = {{ $year }};

    const previewModal = new bootstrap.Modal(
        document.getElementById('previewModal')
    );

    /*
    |--------------------------------------------------------------------------
    | Xem trước hóa đơn
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll(".preview-btn").forEach(button => {

        button.addEventListener("click", function () {

            selectedContract = this.dataset.id;

            fetch(
                `/admin/invoices/contracts/${selectedContract}/preview?month=${month}&year=${year}`
            )

                .then(res => res.json())

                .then(data => {

                    if (data.message) {

                        alert(data.message);

                        return;

                    }

                    document.getElementById("preview-room").innerHTML =
                        data.room_code;

                    document.getElementById("preview-tenant").innerHTML =
                        data.tenant_name;

                    document.getElementById("preview-contract").innerHTML =
                        data.contract_code;

                    document.getElementById("preview-month").innerHTML =
                        data.month + "/" + data.year;

                    document.getElementById("preview-date").innerHTML =
                        data.invoice_date;

                    document.getElementById("preview-due").innerHTML =
                        data.due_date;

                    let html = "";

                    data.lines.forEach(function (item) {

                        html += `
                    <tr>

                        <td>${item.name}</td>

                        <td class="text-center">
                            ${item.quantity}
                        </td>

                        <td class="text-center">
                            ${item.unit ?? ""}
                        </td>

                        <td class="text-end">
                            ${Number(item.unit_price)
                                .toLocaleString('vi-VN')}
                        </td>

                        <td class="text-end fw-bold">

                            ${Number(item.amount)
                                .toLocaleString('vi-VN')}

                        </td>

                    </tr>
                `;

                    });

                    document.getElementById("preview-lines")
                        .innerHTML = html;

                    document.getElementById("preview-total")
                        .innerHTML =
                        Number(data.total_amount)
                            .toLocaleString('vi-VN') + " đ";

                    previewModal.show();

                })

                .catch(function () {

                    alert("Không thể xem trước hóa đơn.");

                });

        });

    });

</script>
