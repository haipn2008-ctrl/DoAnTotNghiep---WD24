@extends('layouts.admin.index')

@section('title', 'Mẫu hợp đồng')
@section('page_title', 'Mẫu hợp đồng')
@push('styles')
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush
@section('content')
<div class="container-fluid contract-template-page">

    <div class="template-head">
        <div>
            <div class="template-eyebrow">QUẢN LÝ HỢP ĐỒNG</div>
            <h2>Mẫu hợp đồng</h2>
            <p>Mẫu hợp đồng trắng dùng để xem trước và in thành bản cứng.</p>
        </div>

        <div class="template-actions">
            <a href="{{ route('admin.contracts.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i> Quản lý hợp đồng
            </a>

            <button type="button" class="btn btn-success" id="printContractTemplate">
                <i class="bi bi-printer me-1"></i> In hợp đồng
            </button>
        </div>
    </div>

    <div class="card contract-template-card">
        <div class="card-header bg-white">
            <div class="file-title">
                <span class="file-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </span>
                <div>
                    <h5>Mẫu hợp đồng thuê phòng trọ</h5>
                    <span>Mẫu trắng — không chứa dữ liệu của khách thuê hoặc hợp đồng cụ thể</span>
                </div>
            </div>
        </div>

        <div class="card-body template-preview-wrapper">
            <div class="contract-paper">
                @include('admin.contracts.contract-template-content')
            </div>
        </div>

        <div class="card-footer bg-white">
            <span>
                <i class="bi bi-info-circle me-1"></i>
                Khi in, hệ thống chỉ in phần hợp đồng A4.
            </span>

            <button type="button" class="btn btn-success" id="printContractTemplateBottom">
                <i class="bi bi-printer me-1"></i> In hợp đồng
            </button>
        </div>
    </div>
</div>

<style>
.file-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #eef2ff;
        color: #4f46e5;
        font-size: 20px;
    }

    .file-icon i {
        display: inline-block;
        font-size: 20px;
        line-height: 1;
    }
.contract-template-page {
    width:100%;
    max-width:none;
    margin:0;
    padding:28px 32px 40px;
}

.template-head {
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:24px;
    margin-bottom:20px;
}

.template-eyebrow {
    color:#4f46e5;
    font-size:12px;
    font-weight:700;
    letter-spacing:.08em;
    margin-bottom:5px;
}

.template-head h2 {
    margin:0 0 5px;
    color:#111827;
    font-size:27px;
    font-weight:700;
}

.template-head p {
    margin:0;
    color:#64748b;
    font-size:14px;
}

.template-actions {
    display:flex;
    gap:10px;
    flex-shrink:0;
}

.template-actions .btn,
.contract-template-card .btn {
    min-height:40px;
    border-radius:9px;
    font-weight:600;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    padding:9px 16px;
}

.template-actions .btn-success,
.contract-template-card .btn-success {
    background:#198754 !important;
    border:1px solid #198754 !important;
    color:#fff !important;
}

.template-actions .btn-success:hover,
.contract-template-card .btn-success:hover {
    background:#157347 !important;
    border-color:#146c43 !important;
    color:#fff !important;
}

.template-actions .btn-light {
    background:#fff !important;
    border:1px solid #cbd5e1 !important;
    color:#1e293b !important;
}

.contract-template-card {
    overflow:hidden;
    border:1px solid #e2e8f0 !important;
    border-radius:14px !important;
    box-shadow:0 6px 24px rgba(15,23,42,.06) !important;
}

.contract-template-card .card-header {
    padding:16px 22px;
    border-bottom:1px solid #e5e7eb;
}

.file-title {
    display:flex;
    align-items:center;
    gap:12px;
}

.file-icon {
    width:42px;
    height:42px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:10px;
    background:#eef2ff;
    color:#4f46e5;
    font-size:19px;
}

.file-title h5 {
    margin:0 0 3px;
    font-size:16px;
    font-weight:700;
    color:#111827;
}

.file-title span {
    color:#64748b;
    font-size:13px;
}

.template-preview-wrapper {
    padding:38px 24px 44px !important;
    background:#eef2f7;
    overflow-x:auto;
    overflow-y:hidden;
    width:100%;
}

.contract-paper {
    width:210mm;
    min-height:297mm;
    margin:0 auto;
    padding:15mm 16mm 17mm;
    background:#fff;
    border:1px solid #d8dee8;
    box-shadow:0 14px 35px rgba(15,23,42,.13);
    font-family:"Times New Roman",Times,serif;
    font-size:16px;
    line-height:1.48;
    color:#111827;
}

.contract-paper p { margin:9px 0; }
.contract-paper .center { text-align:center; }
.contract-paper .national { font-weight:700; font-size:18px; color:#111; }
.contract-paper .slogan { font-size:16px; margin:5px 0 28px; }

.contract-paper .title {
    text-align:center;
    font-size:20px;
    font-weight:700;
    text-transform:uppercase;
    margin:25px 0 27px;
    color:#111;
}

.contract-paper .line {
    display:inline-block;
    border-bottom:1px dotted #333;
    padding:0 3px;
    text-align:center;
    white-space:nowrap;
}

.contract-paper .short{min-width:145px}
.contract-paper .small{min-width:28px}
.contract-paper .mini{min-width:72px}
.contract-paper .medium{min-width:215px}
.contract-paper .Large{min-width:305px}
.contract-paper .ExtraLarge{min-width:385px}
.contract-paper .big{min-width:470px}
.contract-paper .bold{font-weight:700}

.contract-paper .signature-section {
    margin-top:26px;
    page-break-inside:avoid;
}

.contract-paper .signature-section hr {
    margin:28px 0 22px !important;
    border:0;
    border-top:1px solid #64748b;
}

.contract-paper .signature-section table {
    width:100%;
    table-layout:fixed;
    border-collapse:collapse;
    text-align:center;
}

.contract-paper .signature-section td {
    width:50%;
    padding:0;
    border:0;
    vertical-align:top;
    white-space:nowrap;
}

.contract-paper .signature-section .signature-space {
    height:105px;
    vertical-align:bottom;
}

.contract-paper .signature-section .signature-blank {
    height:72px;
}

.contract-template-card .card-footer {
    min-height:64px;
    padding:12px 22px !important;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
}

.contract-template-card .card-footer > span {
    color:#64748b;
    font-size:13px;
}

@media (max-width:992px) {
    .contract-template-page {
        padding:22px 18px 32px;
    }

    .template-head {
        align-items:flex-start;
        flex-direction:column;
    }

    .template-actions {
        width:100%;
    }

    .template-actions .btn {
        flex:1;
    }
}

@media print {
    @page {
        size: A4;
        margin: 0;
    }

    /* Không in giao diện quản trị */
    aside,
    nav,
    header,
    footer,
    .sidebar,
    .admin-sidebar,
    .main-header,
    .topbar,
    .template-head,
    .contract-template-card .card-header,
    .contract-template-card .card-footer {
        display: none !important;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
    }

    .contract-template-page {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .contract-template-card {
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        margin: 0 !important;
    }

    .template-preview-wrapper {
        padding: 0 !important;
        margin: 0 !important;
        background: #fff !important;
        overflow: visible !important;
    }

    .contract-paper {
        width: 210mm !important;
        min-height: 297mm !important;
        margin: 0 auto !important;
        padding: 15mm 16mm 17mm !important;
        border: 0 !important;
        box-shadow: none !important;
        background: #fff !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    function printTemplate() {
        // In trực tiếp tờ A4 đang hiển thị trên trang hiện tại.
        // Không mở /admin/contracts/template/print và không tạo popup.
        window.print();
    }

    document.getElementById('printContractTemplate')
        ?.addEventListener('click', printTemplate);

    document.getElementById('printContractTemplateBottom')
        ?.addEventListener('click', printTemplate);
});
</script>
@endsection