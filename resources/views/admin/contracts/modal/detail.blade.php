<div class="modal-header contract-header border-0">

    <div class="d-flex justify-content-between align-items-center w-100">

    <div>

        <h3 class="fw-bold mb-1">

            <i class="bi bi-file-earmark-text me-2"></i>

            Chi tiết hợp đồng

        </h3>

        <div class="text-white-50">

            Mã hợp đồng:

            <strong class="text-white">

                {{ $contract->contract_code }}

            </strong>

        </div>

    </div>

    <div class="d-flex align-items-center">

        @if($contract->isActive())

            <span class="badge bg-success me-3">

                Đang hoạt động

            </span>

        @elseif($contract->isDraft())

            <span class="badge bg-secondary me-3">

                Bản nháp

            </span>

        @elseif($contract->isExpired())

            <span class="badge bg-danger me-3">

                Hết hạn

            </span>

        @else

            <span class="badge bg-warning text-dark me-3">

                {{  $contract->status_text }}

            </span>

        @endif

        <button

            type="button"

            class="btn-close btn-close-white"

            data-bs-dismiss="modal">

        </button>

    </div>

</div>

</div>


<div class="contract-body p-4">

<div class="container-fluid p-0">

<div class="row g-3 mb-4">

<div class="col-lg-3">

<div class="card border-0 shadow-sm">

<div class="card-body text-center">

<div class="rounded-circle bg-success bg-opacity-10 d-inline-flex
justify-content-center align-items-center mb-3"

style="width:60px;height:60px;">

<i class="bi bi-cash-stack text-success fs-3"></i>

</div>

<div class="text-muted">

Tiền thuê

</div>

<h4 class="fw-bold text-success mt-2">

{{ number_format($contract->monthly_rent) }}

</h4>

<small>

VNĐ / tháng

</small>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="card border-0 shadow-sm">

<div class="card-body text-center">

<div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex
justify-content-center align-items-center mb-3"

style="width:60px;height:60px;">

<i class="bi bi-wallet2 text-warning fs-3"></i>

</div>

<div class="text-muted">

Tiền cọc

</div>

<h4 class="fw-bold text-warning mt-2">

{{ number_format($contract->deposit_amount) }}

</h4>

<small>

VNĐ

</small>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="card border-0 shadow-sm">

<div class="card-body text-center">

<div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex
justify-content-center align-items-center mb-3"

style="width:60px;height:60px;">

<i class="bi bi-calendar-check text-primary fs-3"></i>

</div>

<div class="text-muted">

Ngày bắt đầu

</div>

<h5 class="fw-bold mt-2">

{{ optional($contract->start_date)->format('d/m/Y') }}

</h5>

</div>

</div>

</div>

<div class="col-lg-3">

<div class="card border-0 shadow-sm">

<div class="card-body text-center">

<div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex
justify-content-center align-items-center mb-3"

style="width:60px;height:60px;">

<i class="bi bi-calendar-x text-danger fs-3"></i>

</div>

<div class="text-muted">

Ngày kết thúc

</div>

<h5 class="fw-bold mt-2">

{{ optional($contract->end_date)->format('d/m/Y') }}

</h5>

</div>

</div>

</div>

</div>



<ul
class="nav nav-pills nav-fill mb-4"
id="detailTab"
role="tablist">

<li class="nav-item">

<button

class="nav-link active"

data-bs-toggle="pill"

data-bs-target="#infoTab">

<i class="bi bi-info-circle me-2"></i>

Thông tin

</button>

</li>

<li class="nav-item">

<button

class="nav-link"

data-bs-toggle="pill"

data-bs-target="#contentTab">

<i class="bi bi-file-earmark-richtext me-2"></i>

Nội dung hợp đồng

</button>

</li>

</ul>


<div class="tab-content">

<div

class="tab-pane fade show active"

id="infoTab">


<div class="card border-0 shadow-sm mb-4">

<div class="card-header bg-white">

<h5 class="fw-bold mb-0">

<i class="bi bi-file-earmark-text me-2 text-primary"></i>

Thông tin hợp đồng

</h5>

</div>

<div class="card-body">
<div class="row g-4">

    <div class="col-md-6">

        <table class="table table-borderless align-middle mb-0">

            <tr>

                <th width="180" class="text-muted">

                    Mã hợp đồng

                </th>

                <td>

                    <strong>

                        {{ $contract->contract_code }}

                    </strong>

                </td>

            </tr>

            <tr>

                <th class="text-muted">

                    Trạng thái

                </th>

                <td>

                    @switch( $contract->status_text)

                        @case('active')

                            <span class="badge bg-success">

                                Đang hoạt động

                            </span>

                            @break

                        @case('expired')

                            <span class="badge bg-danger">

                                Hết hạn

                            </span>

                            @break

                        @case('draft')

                            <span class="badge bg-secondary">

                                Bản nháp

                            </span>

                            @break

                        @default

                            <span class="badge bg-warning text-dark">

                                {{ ucfirst( $contract->status_text) }}

                            </span>

                    @endswitch

                </td>

            </tr>

            <tr>

                <th class="text-muted">

                    Tiền thuê

                </th>

                <td class="fw-bold text-success">

                    {{ number_format($contract->monthly_rent) }} VNĐ

                </td>

            </tr>

            <tr>

                <th class="text-muted">

                    Tiền cọc

                </th>

                <td class="fw-bold text-warning">

                    {{ number_format($contract->deposit_amount) }} VNĐ

                </td>

            </tr>

            <tr>

                <th class="text-muted">

                    Chu kỳ thanh toán

                </th>

                <td>

                    {{ $contract->payment_cycle }}

                </td>

            </tr>

            <tr>

                <th class="text-muted">

                    Ngày tạo

                </th>

                <td>

                    {{ optional($contract->created_at)->format('d/m/Y H:i') }}

                </td>

            </tr>

        </table>

    </div>

    <div class="col-md-6">

        <div class="alert alert-light border mb-0">

            <h6 class="fw-bold mb-3">

                <i class="bi bi-info-circle text-primary me-2"></i>

                Ghi chú

            </h6>

            @if(!empty($contract->note))

                {!! nl2br(e($contract->note)) !!}

            @else

                <span class="text-muted">

                    Không có ghi chú.

                </span>

            @endif

        </div>

    </div>

</div>

</div>

</div>



<div class="row g-4">

    <div class="col-lg-6">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-header bg-white">

                <h5 class="fw-bold mb-0">

                    <i class="bi bi-house-door-fill text-success me-2"></i>

                    Thông tin phòng

                </h5>

            </div>

            <div class="card-body">

                <table class="table table-borderless mb-0">

                    <tr>

                        <th width="160">

                            Mã phòng

                        </th>

                        <td>

                            {{ $contract->room->room_code ?? '-' }}

                        </td>

                    </tr>

                    {{-- <tr>

                        <th>

                            Tên phòng

                        </th>

                        <td>

                            {{ $contract->room->room_name ?? '-' }}

                        </td>

                    </tr> --}}

                    <tr>

                        <th>

                            Giá phòng

                        </th>

                        <td class="text-success fw-bold">

                            {{ number_format($contract->room->price ?? 0) }} VNĐ

                        </td>

                    </tr>

                    <tr>

                        <th>

                            Trạng thái

                        </th>

                        <td>

                            {{ $contract->room->status_text ?? '-' }}

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>



    <div class="col-lg-6">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-header bg-white">

                <h5 class="fw-bold mb-0">

                    <i class="bi bi-person-fill text-primary me-2"></i>

                    Thông tin khách thuê

                </h5>

            </div>

            <div class="card-body">

                <table class="table table-borderless mb-0">

                    <tr>

                        <th width="160">

                            Họ tên

                        </th>

                        <td>

                            {{ $contract->tenant->full_name }}

                        </td>

                    </tr>

                    <tr>

                        <th>

                            Điện thoại

                        </th>

                        <td>

                            {{ $contract->tenant->phone }}

                        </td>

                    </tr>

                    <tr>

                        <th>

                            CCCD

                        </th>

                        <td>

                            {{ $contract->tenant->cccd }}

                        </td>

                    </tr>

                    <tr>

                        <th>

                            Email

                        </th>

                        <td>

                            {{ $contract->tenant->email ?? '-' }}

                        </td>

                    </tr>

                    <tr>

                        <th>

                            Địa chỉ

                        </th>

                        <td>

                            {{ $contract->tenant->address ?? '-' }}

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>
<div class="row g-4 mt-1">

    <!-- Thời gian hợp đồng -->
    <div class="col-lg-6">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-header bg-white">

                <h5 class="fw-bold mb-0">

                    <i class="bi bi-calendar-event text-primary me-2"></i>

                    Thời gian hợp đồng

                </h5>

            </div>

            <div class="card-body">

                @php

                    $start = \Carbon\Carbon::parse($contract->start_date)->startOfDay();

                    $end = \Carbon\Carbon::parse($contract->end_date)->startOfDay();

                    $today = now()->startOfDay();

                    $totalDays = max($start->diffInDays($end), 1);

                    if ($today->lt($start)) {

                        $usedDays = 0;

                    } elseif ($today->gt($end)) {

                        $usedDays = $totalDays;

                    } else {

                        $usedDays = $start->diffInDays($today);

                    }

                    $progress = round(($usedDays / $totalDays) * 100);

                    $remain = max(0, $today->diffInDays($end));

                @endphp

                <div class="row text-center">

                    <div class="col-5">

                        <small class="text-muted">

                            Ngày bắt đầu

                        </small>

                        <h5 class="fw-bold mt-2">

                            {{ $start->format('d/m/Y') }}

                        </h5>

                    </div>

                    <div class="col-2 d-flex align-items-center justify-content-center">

                        <i class="bi bi-arrow-right fs-3 text-success"></i>

                    </div>

                    <div class="col-5">

                        <small class="text-muted">

                            Ngày kết thúc

                        </small>

                        <h5 class="fw-bold mt-2 text-danger">

                            {{ $end->format('d/m/Y') }}

                        </h5>

                    </div>

                </div>

                <hr>

                <div class="progress" style="height:10px">

                    <div
                        class="progress-bar bg-success"
                        style="width:{{ $progress }}%">

                    </div>

                </div>

                <div class="d-flex justify-content-between mt-2">

                    <small class="text-muted">

                        Đã sử dụng

                        {{ floor($progress) }}%

                    </small>

                    <small class="text-success fw-bold">

                        Còn {{ $remain }} ngày

                    </small>

                </div>

            </div>

        </div>

    </div>



    <!-- Tài chính -->

    <div class="col-lg-6">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-header bg-white">

                <h5 class="fw-bold mb-0">

                    <i class="bi bi-wallet2 text-success me-2"></i>

                    Thông tin tài chính

                </h5>

            </div>

            <div class="card-body">

                <table class="table table-borderless mb-0">

                    <tr>

                        <th width="180">

                            Tiền thuê

                        </th>

                        <td class="text-success fw-bold">

                            {{ number_format($contract->monthly_rent) }} VNĐ

                        </td>

                    </tr>

                    <tr>

                        <th>

                            Tiền cọc

                        </th>

                        <td class="text-warning fw-bold">

                            {{ number_format($contract->deposit_amount) }} VNĐ

                        </td>

                    </tr>

                    {{-- <tr>

                        <th>

                            Chu kỳ

                        </th>

                        <td>

                            {{ $contract->payment_cycle }}

                        </td>

                    </tr> --}}

                    <tr>

                        <th>

                            Thanh toán

                        </th>

                        <td>

                            @if($contract->deposit_confirmed)

                                <span class="badge bg-success">

                                    Đã xác nhận

                                </span>

                            @else

                                <span class="badge bg-danger">

                                    Chưa xác nhận

                                </span>

                            @endif

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>



<div class="row mt-4">

    <div class="col-lg-12">

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white">

                <h5 class="fw-bold mb-0">

                    <i class="bi bi-images text-info me-2"></i>

                    Hình ảnh hợp đồng

                </h5>

            </div>

            <div class="card-body">

                @if(!empty($contract->contract_file))

                    <div class="text-center">

                        <img

                            src="{{ asset($contract->contract_file) }}"

                            class="img-fluid rounded shadow"

                            style="max-height:500px;">

                    </div>

                @else

                    <div
                        class="border rounded bg-light d-flex
                        justify-content-center align-items-center"

                        style="height:320px;">

                        <div class="text-center">

                            <i class="bi bi-image display-1 text-secondary"></i>

                            <h5 class="mt-3 text-muted">

                                Chưa có hình ảnh hợp đồng

                            </h5>

                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>

</div>
        </div>
    </div>
</div>

{{-- ========================= --}}
{{-- TAB NỘI DUNG HỢP ĐỒNG --}}
{{-- ========================= --}}

<div
    class="tab-pane fade"
    id="contentTab"
    role="tabpanel">

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <h5 class="fw-bold mb-0">

                    <i class="bi bi-file-earmark-richtext text-primary me-2"></i>

                    Nội dung hợp đồng

                </h5>

                <span class="badge bg-primary">

                    Read Only

                </span>

            </div>

        </div>

        <div class="card-body">

            @if(!empty($contract->contract_content))

                <div
                    class="border rounded bg-white p-4"

                    style="
                        min-height:650px;
                        max-height:700px;
                        overflow:auto;
                        line-height:1.8;
                    ">

                    {!! $contract->contract_content !!}

                </div>

            @else

                <div
                    class="alert alert-warning mb-0">

                    <i class="bi bi-exclamation-circle me-2"></i>

                    Hợp đồng chưa có nội dung.

                </div>

            @endif

        </div>

    </div>

</div>

</div>

</div>

</div>

{{-- ========================= --}}
{{-- FOOTER --}}
{{-- ========================= --}}

<div class="modal-footer bg-light">

    <div class="me-auto">

        <small class="text-muted">

            Cập nhật lần cuối:

            {{ optional($contract->updated_at)->format('d/m/Y H:i') }}

        </small>

    </div>

    <button

        type="button"

        class="btn btn-secondary"

        data-bs-dismiss="modal">

        <i class="bi bi-x-circle me-1"></i>

        Đóng

    </button>

    <a

        href="{{ route('admin.contracts.print',$contract) }}"

        target="_blank"

        class="btn btn-success">

        <i class="bi bi-printer me-1"></i>

        In hợp đồng

    </a>
    @if($contract->isPendingSignature())

    <form
        action="{{ route('admin.contracts.recall-signature',$contract) }}"
        method="POST"
        class="d-inline">

        @csrf

        <button
            type="submit"
            class="btn btn-danger"
            onclick="return confirm('Thu hồi hợp đồng để chỉnh sửa?')">

            <i class="bi bi-arrow-counterclockwise me-1"></i>

            Thu hồi

        </button>

    </form>

    @endif
    @if($contract->isDraft())

    <form
        action="{{ route('admin.contracts.send-signature', $contract) }}"
        method="POST"
        class="d-inline">

        @csrf

        <button
            type="submit"
            class="btn btn-warning"
            onclick="return confirm('Gửi hợp đồng cho khách thuê ký?')">

            <i class="bi bi-send me-1"></i>

            Gửi hợp đồng

        </button>

    </form>

    @endif

    @if(in_array($contract->status,['draft','active']))

    <button
        type="button"
        class="btn btn-primary editContractBtn"

        data-id="{{ $contract->id }}"
        data-room="{{ $contract->room_id }}"
        data-tenant="{{ $contract->tenant_id }}"
        data-rent="{{ $contract->monthly_rent }}"
        data-deposit="{{ $contract->deposit_amount }}"
        data-start="{{ optional($contract->start_date)->format('Y-m-d') }}"
        data-end="{{ optional($contract->end_date)->format('Y-m-d') }}"
        data-status="{{ $contract->status_text }}"
        data-content="{{ e($contract->contract_content) }}"
        data-note="{{ $contract->note }}"
        data-image="{{ $contract->contract_file ? asset($contract->contract_file) : '' }}"

        data-bs-toggle="modal"
        data-bs-target="#editContractModal">

        <i class="bi bi-pencil-square me-1"></i>

        Chỉnh sửa

    </button>

    @endif

    @if($contract->canExtend())

    <button
        class="btn btn-warning"
        data-bs-toggle="modal"
        data-bs-target="#extendContractModal">

        <i class="bi bi-arrow-repeat me-1"></i>

        Gia hạn

    </button>

    @endif


    @if($contract->canTerminate())

    <button
        type="button"
        class="btn btn-danger terminateBtn"

        data-id="{{ $contract->id }}"

        data-end="{{ optional($contract->end_date)->format('Y-m-d') }}"

        data-bs-toggle="modal"

        data-bs-target="#terminateContractModal">

        <i class="bi bi-slash-circle me-1"></i>

        Kết thúc

    </button>

    @endif
    @if($contract->canReturnDeposit())

    <button
        type="button"
        class="btn btn-info returnDepositBtn"

        data-id="{{ $contract->id }}"

        data-bs-toggle="modal"
        data-bs-target="#returnDepositModal">

        <i class="bi bi-cash-coin me-1"></i>

        Hoàn cọc

    </button>

    @endif

</div>



<style>

/* ===============================
   Modal
================================ */

#contractDetailModal .modal-dialog{
    max-width:1400px;
}

#contractDetailModal .modal-content{
    border:none;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 15px 40px rgba(0,0,0,.15);
}

/* ===============================
   Header
================================ */

.contract-header{

    background:linear-gradient(135deg,#2563eb,#3b82f6);

    color:#fff;

    padding:18px 28px;

    border:none;

}
.contract-header .btn-close{

    margin:0;

    opacity:1;

}

.contract-header .badge{

    margin-right:15px;

}

.contract-header h3{

    margin:0;

}

.contract-header h3{

    color:#fff;

}

.contract-header .text-muted{

    color:rgba(255,255,255,.85)!important;

}

.contract-body{

    background:#f6f8fb;

    max-height:78vh;

    overflow-y:auto;

    padding:25px;

}

/* ===============================
   Card
================================ */

.contract-body .card{

    border:none;

    border-radius:15px;

    transition:.25s;

}

.contract-body .card:hover{

    transform:translateY(-4px);

    box-shadow:0 15px 35px rgba(0,0,0,.08);

}

.card-header{

    background:#fff;

    border-bottom:1px solid #edf2f7;

    padding:18px 22px;

}

.card-body{

    padding:22px;

}

/* ===============================
   Nav
================================ */

.nav-pills .nav-link{

    border-radius:12px;

    padding:13px 22px;

    color:#555;

    font-weight:600;

}

.nav-pills .nav-link.active{

    background:#2563eb;

    box-shadow:0 8px 20px rgba(37,99,235,.25);

}

/* ===============================
   Badge
================================ */

.badge{

    padding:8px 14px;

    font-size:.82rem;

    border-radius:30px;

}

/* ===============================
   Table
================================ */

.table-borderless tr{

    border-bottom:1px solid #f1f3f6;

}

.table-borderless tr:last-child{

    border:none;

}

.table-borderless th{

    color:#6b7280;

    font-weight:600;

}

.table-borderless td{

    font-weight:500;

}

/* ===============================
   Progress
================================ */

.progress{

    border-radius:30px;

    overflow:hidden;

    height:10px;

    background:#e9ecef;

}

.progress-bar{

    border-radius:30px;

}

/* ===============================
   Image
================================ */

.contract-image img{

    transition:.3s;

    border-radius:15px;

}

.contract-image img:hover{

    transform:scale(1.02);

}

/* ===============================
   Footer
================================ */

.modal-footer{

    background:#fff;

    border-top:1px solid #eee;

    padding:18px 25px;

}

.modal-footer .btn{

    border-radius:10px;

    padding:9px 18px;

    font-weight:600;

}

/* ===============================
   Scroll
================================ */

.contract-body::-webkit-scrollbar{

    width:8px;

}

.contract-body::-webkit-scrollbar-thumb{

    background:#b8c0cc;

    border-radius:30px;

}

.contract-body::-webkit-scrollbar-track{

    background:#eef2f7;

}

/* ===============================
   Responsive
================================ */

@media(max-width:992px){

    .contract-body{

        padding:15px;

    }

    .modal-dialog{

        margin:.5rem;

    }

    .card-body{

        padding:16px;

    }

    .nav-pills .nav-link{

        margin-bottom:10px;

    }

}

</style>