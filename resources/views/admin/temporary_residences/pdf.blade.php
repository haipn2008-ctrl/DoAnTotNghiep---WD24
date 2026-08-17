
<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Phiếu đăng ký tạm trú
        #{{ $temporaryResidence->id }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #f1f3f5;
            color: #222;
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 18mm 16mm;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .12);
        }

        .header {
            text-align: center;
            margin-bottom: 22px;
        }

        .header .title {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .header .subtitle {
            margin-top: 5px;
            font-size: 13px;
            color: #555;
        }

        .header-line {
            width: 80px;
            height: 2px;
            margin: 12px auto 0;
            background: #222;
        }

        .document-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .document-info-item {
            font-size: 12px;
        }

        .section {
            margin-top: 18px;
        }

        .section-title {
            margin: 0 0 10px;
            padding: 7px 10px;
            background: #f2f2f2;
            border-left: 4px solid #333;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td,
        .info-table th {
            border: 1px solid #bfc3c7;
            padding: 8px 9px;
            vertical-align: top;
        }

        .label {
            width: 22%;
            font-weight: 600;
            background: #fafafa;
        }

        .label-wide {
            width: 18%;
            font-weight: 600;
            background: #fafafa;
        }

        .value {
            width: 28%;
        }

        .muted {
            color: #777;
        }

        .status {
            display: inline-block;
            padding: 3px 9px;
            border: 1px solid #999;
            border-radius: 4px;
            font-weight: 600;
        }

        .date-table td {
            width: 50%;
            border: 1px solid #bfc3c7;
            padding: 12px;
            text-align: center;
        }

        .date-label {
            display: block;
            margin-bottom: 4px;
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
        }

        .date-value {
            font-size: 16px;
            font-weight: 700;
        }

        .note {
            min-height: 70px;
            padding: 10px;
            border: 1px solid #bfc3c7;
            white-space: pre-line;
        }

        .signature-table {
            margin-top: 35px;
        }

        .signature-table td {
            width: 50%;
            padding: 8px;
            text-align: center;
            vertical-align: top;
        }

        .signature-title {
            font-weight: 600;
        }

        .signature-space {
            height: 80px;
        }

        .signature-name {
            font-weight: 600;
        }

        .footer {
            margin-top: 25px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }

        .print-actions {
            width: 210mm;
            margin: 15px auto;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .print-btn {
            border: 0;
            padding: 9px 16px;
            border-radius: 5px;
            background: #dc3545;
            color: #fff;
            cursor: pointer;
            font-size: 13px;
        }

        .back-btn {
            border: 1px solid #ccc;
            padding: 8px 15px;
            border-radius: 5px;
            background: #fff;
            color: #333;
            text-decoration: none;
        }

        @media print {

            html,
            body {
                background: #fff;
            }

            .page {
                width: 210mm;
                min-height: 297mm;
                margin: 0;
                padding: 15mm;
                box-shadow: none;
            }

            .print-actions {
                display: none;
            }

            .footer {
                position: relative;
            }
        }

        @page {
            size: A4;
            margin: 0;
        }
    </style>

</head>


<body>


    {{-- ============================================================
        NÚT THAO TÁC - KHÔNG IN
    ============================================================= --}}

    <div class="print-actions">

        <a href="{{ route('admin.temporary_residences.show', $temporaryResidence) }}"
            class="back-btn">

            Quay lại hồ sơ

        </a>

        <button type="button"
            class="print-btn"
            onclick="window.print()">

            In / Lưu PDF

        </button>

    </div>


    {{-- ============================================================
        PHIẾU
    ============================================================= --}}

    <div class="page">


        {{-- ========================================================
            HEADER
        ========================================================= --}}

        <div class="header">

            <h1 class="title">
                Phiếu đăng ký tạm trú
            </h1>

            <div class="subtitle">
                Hồ sơ quản lý đăng ký tạm trú khách thuê phòng
            </div>

            <div class="header-line"></div>

        </div>


        {{-- ========================================================
            THÔNG TIN HỒ SƠ
        ========================================================= --}}

        <div class="document-info">

            <div class="document-info-item">

                <strong>Mã hồ sơ:</strong>

                #{{ $temporaryResidence->id }}

            </div>


            <div class="document-info-item">

                <strong>Ngày lập:</strong>

                {{ $temporaryResidence->created_at
                    ? $temporaryResidence->created_at->format('d/m/Y')
                    : now()->format('d/m/Y') }}

            </div>

        </div>


        {{-- ========================================================
            1. THÔNG TIN KHÁCH THUÊ
        ========================================================= --}}

        <div class="section">

            <h2 class="section-title">
                I. Thông tin người đăng ký
            </h2>


            <table class="info-table">

                <tr>

                    <td class="label">
                        Họ và tên
                    </td>

                    <td colspan="3">

                        {{ $temporaryResidence->tenant->full_name
                            ?? 'Chưa cập nhật' }}

                    </td>

                </tr>


                <tr>

                    <td class="label">
                        Ngày sinh
                    </td>

                    <td>

                        {{ $temporaryResidence->tenant?->date_of_birth
                            ? \Carbon\Carbon::parse(
                                $temporaryResidence->tenant->date_of_birth
                            )->format('d/m/Y')
                            : 'Chưa cập nhật' }}

                    </td>


                    <td class="label">
                        Giới tính
                    </td>

                    <td>

                        @switch($temporaryResidence->tenant->gender ?? null)

                            @case('male')
                                Nam
                            @break

                            @case('female')
                                Nữ
                            @break

                            @case('other')
                                Khác
                            @break

                            @default
                                Chưa cập nhật

                        @endswitch

                    </td>

                </tr>


                <tr>

                    <td class="label">
                        Số CCCD
                    </td>

                    <td>

                        {{ $temporaryResidence->tenant->cccd
                            ?? 'Chưa cập nhật' }}

                    </td>


                    <td class="label">
                        Số điện thoại
                    </td>

                    <td>

                        {{ $temporaryResidence->tenant->phone
                            ?? 'Chưa cập nhật' }}

                    </td>

                </tr>


                <tr>

                    <td class="label">
                        Ngày cấp CCCD
                    </td>

                    <td>

                        {{ $temporaryResidence->tenant?->cccd_issue_date
                            ? \Carbon\Carbon::parse(
                                $temporaryResidence->tenant->cccd_issue_date
                            )->format('d/m/Y')
                            : 'Chưa cập nhật' }}

                    </td>


                    <td class="label">
                        Nơi cấp CCCD
                    </td>

                    <td>

                        {{ $temporaryResidence->tenant->cccd_issue_place
                            ?? 'Chưa cập nhật' }}

                    </td>

                </tr>


                <tr>

                    <td class="label">
                        Địa chỉ thường trú
                    </td>

                    <td colspan="3">

                        {{ $temporaryResidence->tenant->address
                            ?? 'Chưa cập nhật' }}

                    </td>

                </tr>

            </table>

        </div>


        {{-- ========================================================
            2. THÔNG TIN HỢP ĐỒNG
        ========================================================= --}}

        <div class="section">

            <h2 class="section-title">
                II. Thông tin hợp đồng thuê phòng
            </h2>


            <table class="info-table">

                <tr>

                    <td class="label-wide">
                        Mã hợp đồng
                    </td>

                    <td>

                        {{ $temporaryResidence->contract->contract_code
                            ?? 'HD-' . $temporaryResidence->contract_id }}

                    </td>


                    <td class="label-wide">
                        Phòng
                    </td>

                    <td>

                        {{ $temporaryResidence->contract->room->room_code
                            ?? 'Chưa xác định' }}

                    </td>

                </tr>


                <tr>

                    <td class="label-wide">
                        Trạng thái hợp đồng
                    </td>

                    <td colspan="3">

                        @switch($temporaryResidence->contract->status ?? null)

                            @case('active')
                                Đang hoạt động
                            @break

                            @case('pending')
                                Chờ xử lý
                            @break

                            @case('signed')
                                Đã ký
                            @break

                            @case('expired')
                                Đã hết hạn
                            @break

                            @case('terminated')
                                Đã chấm dứt
                            @break

                            @default
                                Không xác định

                        @endswitch

                    </td>

                </tr>


                <tr>

                    <td class="label-wide">
                        Ngày bắt đầu
                    </td>

                    <td>

                        {{ $temporaryResidence->contract?->start_date
                            ? \Carbon\Carbon::parse(
                                $temporaryResidence->contract->start_date
                            )->format('d/m/Y')
                            : 'Chưa xác định' }}

                    </td>


                    <td class="label-wide">
                        Ngày kết thúc
                    </td>

                    <td>

                        {{ $temporaryResidence->contract?->end_date
                            ? \Carbon\Carbon::parse(
                                $temporaryResidence->contract->end_date
                            )->format('d/m/Y')
                            : 'Không xác định' }}

                    </td>

                </tr>

            </table>

        </div>


        {{-- ========================================================
            3. THỜI GIAN TẠM TRÚ
        ========================================================= --}}

        <div class="section">

            <h2 class="section-title">
                III. Thời gian tạm trú
            </h2>


            <table class="date-table">

                <tr>

                    <td>

                        <span class="date-label">
                            Từ ngày
                        </span>

                        <span class="date-value">

                            {{ $temporaryResidence->start_date
                                ? $temporaryResidence->start_date->format('d/m/Y')
                                : 'Chưa xác định' }}

                        </span>

                    </td>


                    <td>

                        <span class="date-label">
                            Đến ngày
                        </span>

                        <span class="date-value">

                            {{ $temporaryResidence->end_date
                                ? $temporaryResidence->end_date->format('d/m/Y')
                                : 'Không xác định' }}

                        </span>

                    </td>

                </tr>

            </table>

        </div>


        {{-- ========================================================
            4. THÔNG TIN ĐĂNG KÝ
        ========================================================= --}}

        <div class="section">

            <h2 class="section-title">
                IV. Thông tin đăng ký
            </h2>


            <table class="info-table">

                <tr>

                    <td class="label">
                        Trạng thái đăng ký
                    </td>

                    <td>

                        <span class="status">

                            @switch($temporaryResidence->status)

                                @case('pending')
                                    Chờ xác nhận
                                @break

                                @case('active')
                                    Đang tạm trú
                                @break

                                @case('expired')
                                    Đã hết hạn
                                @break

                                @case('cancelled')
                                    Đã hủy
                                @break

                                @default
                                    Không xác định

                            @endswitch

                        </span>

                    </td>


                    <td class="label">
                        Ngày cập nhật
                    </td>

                    <td>

                        {{ $temporaryResidence->updated_at
                            ? $temporaryResidence->updated_at->format('d/m/Y H:i')
                            : 'Chưa cập nhật' }}

                    </td>

                </tr>

            </table>

        </div>


        {{-- ========================================================
            5. PHƯƠNG TIỆN
        ========================================================= --}}

        @if (
            $temporaryResidence->tenant &&
            $temporaryResidence->tenant->vehicles &&
            $temporaryResidence->tenant->vehicles->count()
        )

            <div class="section">

                <h2 class="section-title">
                    V. Phương tiện của khách thuê
                </h2>


                <table class="info-table">

                    <thead>

                        <tr>

                            <th style="width: 8%;">
                                STT
                            </th>

                            <th>
                                Loại phương tiện
                            </th>

                            <th>
                                Tên phương tiện
                            </th>

                            <th>
                                Biển số
                            </th>

                            <th>
                                Màu
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach (
                            $temporaryResidence->tenant->vehicles
                            as $index => $vehicle
                        )

                            <tr>

                                <td style="text-align: center;">
                                    {{ $index + 1 }}
                                </td>

                                <td>

                                    {{ $vehicle->vehicle_type
                                        ?? 'Chưa cập nhật' }}

                                </td>

                                <td>

                                    {{ $vehicle->vehicle_name
                                        ?? 'Chưa cập nhật' }}

                                </td>

                                <td>

                                    <strong>

                                        {{ $vehicle->license_plate
                                            ?? 'Chưa cập nhật' }}

                                    </strong>

                                </td>

                                <td>

                                    {{ $vehicle->color
                                        ?? 'Chưa cập nhật' }}

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="section">

                <h2 class="section-title">
                    V. Phương tiện của khách thuê
                </h2>

                <div class="note muted">
                    Không có phương tiện được đăng ký.
                </div>

            </div>

        @endif


        {{-- ========================================================
            6. GHI CHÚ
        ========================================================= --}}

        <div class="section">

            <h2 class="section-title">
                VI. Ghi chú
            </h2>


            <div class="note">

                {{ $temporaryResidence->note
                    ?? 'Không có ghi chú.' }}

            </div>

        </div>


        {{-- ========================================================
            CHỮ KÝ
        ========================================================= --}}

        <table class="signature-table">

            <tr>

                <td>

                    <div class="signature-title">
                        NGƯỜI ĐĂNG KÝ
                    </div>

                    <div class="muted">
                        (Ký và ghi rõ họ tên)
                    </div>

                    <div class="signature-space"></div>

                    <div class="signature-name">

                        {{ $temporaryResidence->tenant->full_name
                            ?? '' }}

                    </div>

                </td>


                <td>

                    <div class="signature-title">
                        NGƯỜI QUẢN LÝ
                    </div>

                    <div class="muted">
                        (Ký và ghi rõ họ tên)
                    </div>

                    <div class="signature-space"></div>

                    <div class="signature-name">
                        Quản lý nhà trọ
                    </div>

                </td>

            </tr>

        </table>


        {{-- ========================================================
            THÔNG TIN CHỮ KÝ ĐIỆN TỬ
        ========================================================= --}}

        @if (
            !empty($temporaryResidence->signature) ||
            !empty($temporaryResidence->signed_at)
        )

            <div class="section">

                <div class="note">

                    <strong>
                        Thông tin xác nhận:
                    </strong>

                    <br>

                    Hồ sơ đã được ký vào lúc:

                    {{ $temporaryResidence->signed_at
                        ? $temporaryResidence->signed_at->format('d/m/Y H:i')
                        : 'Chưa xác định' }}

                </div>

            </div>

        @endif


        {{-- ========================================================
            FOOTER
        ========================================================= --}}

        <div class="footer">

            Phiếu được tạo từ hệ thống quản lý phòng trọ.

            <br>

            Mã hồ sơ:
            #{{ $temporaryResidence->id }}

        </div>


    </div>


</body>

</html>
