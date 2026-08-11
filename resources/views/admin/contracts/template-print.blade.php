<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Xem trước - Mẫu hợp đồng thuê phòng trọ</title>

    <style>
        /* =========================================================
           RESET
        ========================================================= */
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #eef2f7;
            color: #172033;
        }


        /* =========================================================
           TOOLBAR
        ========================================================= */
        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 100;

            width: 100%;
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid #e2e8f0;

            box-shadow: 0 2px 12px rgba(15, 23, 42, .06);
        }

        .toolbar-inner {
            width: min(1180px, calc(100% - 40px));
            min-height: 74px;
            margin: 0 auto;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;
        }


        /* =========================================================
           TOOLBAR LEFT
        ========================================================= */
        .toolbar-title {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .toolbar-icon {
            width: 42px;
            height: 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;
            background: #eef2ff;
            color: #4f46e5;

            font-size: 19px;
        }

        .toolbar-text h1 {
            margin: 0;

            font-size: 16px;
            font-weight: 700;
            color: #172033;
        }

        .toolbar-text p {
            margin: 3px 0 0;

            font-size: 13px;
            color: #64748b;
        }


        /* =========================================================
           TOOLBAR BUTTONS
        ========================================================= */
        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            height: 40px;
            padding: 0 17px;

            border-radius: 8px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;

            font-size: 14px;
            font-weight: 600;

            text-decoration: none;
            cursor: pointer;

            transition: .18s ease;
        }

        .btn-back {
            background: #fff;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .btn-back:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .btn-print {
            background: #15803d;
            color: #fff;
            border: 1px solid #15803d;

            box-shadow: 0 3px 8px rgba(21, 128, 61, .18);
        }

        .btn-print:hover {
            background: #166534;
            border-color: #166534;
            transform: translateY(-1px);
        }


        /* =========================================================
           PREVIEW AREA
        ========================================================= */
        .preview-area {
            padding: 34px 20px 60px;
        }

        .preview-container {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
        }


        /* =========================================================
           PREVIEW HEADER
        ========================================================= */
        .preview-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 18px;
        }

        .preview-heading-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .preview-heading-left .dot {
            width: 8px;
            height: 8px;

            border-radius: 50%;
            background: #22c55e;
        }

        .preview-heading-left span {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
        }

        .preview-size {
            font-size: 12px;
            color: #94a3b8;
        }


        /* =========================================================
           PAPER WRAPPER
        ========================================================= */
        .paper-stage {
            padding: 34px;

            background: #e8edf4;

            border: 1px solid #dbe2ea;
            border-radius: 12px;

            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.7),
                0 4px 20px rgba(15,23,42,.04);

            overflow-x: auto;
        }


        /* =========================================================
           A4 PAPER
        ========================================================= */
        .print-contract {
            width: 210mm;
            min-height: 297mm;

            margin: 0 auto;
            padding: 18mm 18mm 18mm;

            background: #fff;

            border: 1px solid #d5dbe3;

            box-shadow:
                0 18px 45px rgba(15,23,42,.14);

            font-family: "Times New Roman", Times, serif;
            font-size: 16px;
            line-height: 1.5;
            color: #111827;
        }


        /* =========================================================
           CONTRACT TYPOGRAPHY
        ========================================================= */
        .print-contract p {
            margin: 9px 0;
        }

        .print-contract .center {
            text-align: center;
        }

        .print-contract .national {
            font-size: 18px;
            font-weight: 700;
        }

        .print-contract .slogan {
            margin-top: 5px;
            margin-bottom: 30px;

            font-size: 16px;
        }

        .print-contract .title {
            margin: 25px 0 28px;

            text-align: center;

            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .print-contract .line {
            display: inline-block;

            min-width: 30px;

            padding: 0 4px;

            border-bottom: 1px dotted #555;

            white-space: nowrap;
        }

        .print-contract .short {
            min-width: 145px;
        }

        .print-contract .small {
            min-width: 28px;
        }

        .print-contract .mini {
            min-width: 72px;
        }

        .print-contract .medium {
            min-width: 215px;
        }

        .print-contract .Large {
            min-width: 305px;
        }

        .print-contract .ExtraLarge {
            min-width: 385px;
        }

        .print-contract .big {
            min-width: 470px;
        }

        .print-contract .bold {
            font-weight: 700;
        }


        /* =========================================================
           SIGNATURE
        ========================================================= */
        .signature-section {
            margin-top: 28px;
            page-break-inside: avoid;
        }

        .signature-section hr {
            margin: 28px 0 22px;

            border: 0;
            border-top: 1px solid #64748b;
        }

        .signature-section table {
            width: 100%;

            table-layout: fixed;
            border-collapse: collapse;

            text-align: center;
        }

        .signature-section td {
            width: 50%;

            padding: 0;

            border: 0;

            vertical-align: top;

            white-space: nowrap;
        }

        .signature-space {
            height: 105px;

            vertical-align: bottom !important;
        }

        .signature-section small {
            display: block;
            margin-top: 3px;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */
        @media (max-width: 900px) {

            .toolbar-inner {
                width: calc(100% - 24px);
            }

            .preview-area {
                padding: 20px 12px 40px;
            }

            .paper-stage {
                padding: 18px;
            }
        }

        @media (max-width: 650px) {

            .toolbar-inner {
                min-height: auto;
                padding: 12px 0;

                align-items: flex-start;
            }

            .toolbar-text p {
                display: none;
            }

            .toolbar-actions {
                gap: 6px;
            }

            .btn {
                padding: 0 11px;
            }

            .btn-back {
                font-size: 0;
            }

            .btn-back::before {
                content: "←";
                font-size: 18px;
            }

            .preview-heading {
                align-items: flex-start;
            }

            .preview-size {
                display: none;
            }
        }


        /* =========================================================
           PRINT
        ========================================================= */
        @page {
            size: A4;
            margin: 0;
        }

        @media print {

            html,
            body {
                width: 210mm;
                margin: 0;
                padding: 0;

                background: #fff;
            }

            .print-toolbar,
            .preview-heading,
            .paper-stage {
                display: none !important;
            }

            .preview-area {
                padding: 0;
                margin: 0;
            }

            .preview-container {
                width: auto;
                max-width: none;
                margin: 0;
            }

            .print-contract {
                display: block;

                width: 210mm;
                min-height: 297mm;

                margin: 0;
                padding: 18mm 18mm 18mm;

                border: 0;
                box-shadow: none;

                background: #fff;
            }
        }
    </style>
</head>

<body>

    <!-- =====================================================
         TOOLBAR
    ====================================================== -->
    <header class="print-toolbar">
        <div class="toolbar-inner">

            <div class="toolbar-title">

                <div class="toolbar-icon">
                    🖨
                </div>

                <div class="toolbar-text">
                    <h1>Xem trước bản in</h1>
                    <p>Mẫu hợp đồng thuê phòng trọ</p>
                </div>

            </div>


            <div class="toolbar-actions">

                <button
                    type="button"
                    class="btn btn-back"
                    onclick="window.close();"
                >
                    ←
                    <span>Đóng</span>
                </button>

                <button
                    type="button"
                    class="btn btn-print"
                    onclick="window.print();"
                >
                    🖨
                    <span>In hợp đồng</span>
                </button>

            </div>

        </div>
    </header>


    <!-- =====================================================
         PREVIEW
    ====================================================== -->
    <main class="preview-area">

        <div class="preview-container">

            <div class="preview-heading">

                <div class="preview-heading-left">
                    <span class="dot"></span>
                    <span>NỘI DUNG HỢP ĐỒNG</span>
                </div>

                <span class="preview-size">
                    Khổ giấy A4 · 210 × 297 mm
                </span>

            </div>


            <div class="paper-stage">

                <div class="print-contract">

                    @include('admin.contracts.contract-template-content')

                </div>

            </div>

        </div>

    </main>


    <script>
        window.addEventListener('load', function () {

            // Tự động mở hộp thoại in sau khi trang tải xong
            setTimeout(function () {
                window.focus();
                window.print();
            }, 500);

        });
    </script>

</body>
</html>