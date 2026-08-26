<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phụ lục chuyển giao người đại diện</title>
    <style>
        @page { margin: 24mm 20mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; line-height: 1.65; }
        h1 { margin: 18px 0 4px; text-align: center; font-size: 20px; text-transform: uppercase; }
        .center { text-align: center; }
        .muted { color: #4b5563; }
        .section { margin-top: 18px; }
        .section-title { margin-bottom: 6px; font-size: 13px; font-weight: bold; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 6px; vertical-align: top; }
        .label { width: 170px; font-weight: bold; }
        .terms { margin-top: 16px; padding: 12px 14px; border: 1px solid #9ca3af; }
        .signatures { margin-top: 36px; text-align: center; }
        .signatures td { width: 33.33%; font-weight: bold; }
        .signature-space { height: 90px; }
    </style>
</head>
<body>
    @php
        $contract = $transfer->contract;
        $old = $transfer->old_representative_snapshot;
        $new = $transfer->new_representative_snapshot;
    @endphp

    <div class="center"><strong>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong></div>
    <div class="center">Độc lập - Tự do - Hạnh phúc</div>
    <div class="center">—————————</div>

    <h1>Phụ lục hợp đồng</h1>
    <div class="center muted">Về việc chuyển giao người đại diện thuê phòng</div>
    <div class="center"><strong>Số: PL-{{ $contract->contract_code }}-{{ str_pad((string) $transfer->id, 3, '0', STR_PAD_LEFT) }}</strong></div>

    <div class="section">
        <table>
            <tr><td class="label">Hợp đồng chính:</td><td>{{ $contract->contract_code }}</td></tr>
            <tr><td class="label">Phòng thuê:</td><td>{{ $contract->room?->room_code }}{{ $contract->property_address_snapshot ? ' – '.$contract->property_address_snapshot : '' }}</td></tr>
            <tr><td class="label">Thời hạn hợp đồng:</td><td>{{ $contract->start_date?->format('d/m/Y') }} đến {{ $contract->end_date?->format('d/m/Y') }}</td></tr>
            <tr><td class="label">Ngày có hiệu lực:</td><td>{{ $transfer->effective_at->format('d/m/Y H:i') }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">1. Người đại diện cũ (Bên chuyển giao)</div>
        <table>
            <tr><td class="label">Họ và tên:</td><td>{{ data_get($old, 'full_name') }}</td></tr>
            <tr><td class="label">Ngày sinh:</td><td>{{ data_get($old, 'date_of_birth') ? \Carbon\Carbon::parse(data_get($old, 'date_of_birth'))->format('d/m/Y') : '—' }}</td></tr>
            <tr><td class="label">CCCD/Giấy tờ:</td><td>{{ data_get($old, 'identity_number') ?: '—' }}</td></tr>
            <tr><td class="label">Điện thoại:</td><td>{{ data_get($old, 'phone') ?: '—' }}</td></tr>
            <tr><td class="label">Địa chỉ:</td><td>{{ data_get($old, 'address') ?: '—' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Người đại diện mới (Bên nhận chuyển giao)</div>
        <table>
            <tr><td class="label">Họ và tên:</td><td>{{ data_get($new, 'full_name') }}</td></tr>
            <tr><td class="label">Ngày sinh:</td><td>{{ data_get($new, 'date_of_birth') ? \Carbon\Carbon::parse(data_get($new, 'date_of_birth'))->format('d/m/Y') : '—' }}</td></tr>
            <tr><td class="label">CCCD/Giấy tờ:</td><td>{{ data_get($new, 'identity_number') ?: '—' }}</td></tr>
            <tr><td class="label">Điện thoại:</td><td>{{ data_get($new, 'phone') ?: '—' }}</td></tr>
            <tr><td class="label">Địa chỉ:</td><td>{{ data_get($new, 'address') ?: '—' }}</td></tr>
        </table>
    </div>

    <div class="terms">
        <strong>Nội dung thỏa thuận:</strong>
        <p>Kể từ ngày {{ $transfer->effective_at->format('d/m/Y') }}, ông/bà <strong>{{ data_get($new, 'full_name') }}</strong> thay thế ông/bà <strong>{{ data_get($old, 'full_name') }}</strong> làm người đại diện thuê phòng theo Hợp đồng {{ $contract->contract_code }}.</p>
        <p>Ông/bà {{ data_get($new, 'full_name') }} kế thừa toàn bộ quyền lợi, bao gồm khoản tiền cọc đang ghi nhận là <strong>{{ number_format((float) $transfer->deposit_amount_snapshot, 0, ',', '.') }} đồng</strong>, và toàn bộ nghĩa vụ phát sinh từ hợp đồng chính kể từ thời điểm chuyển giao.</p>
        <p>Lý do chuyển giao: {{ $transfer->reason }}.</p>
        <p>Các nội dung khác của hợp đồng chính không thay đổi. Phụ lục này là bộ phận không tách rời của hợp đồng chính, được lập thành các bản có giá trị pháp lý như nhau và có hiệu lực từ thời điểm nêu trên.</p>
    </div>

    <table class="signatures">
        <tr><td>ĐẠI DIỆN BÊN CHO THUÊ</td><td>NGƯỜI ĐẠI DIỆN CŨ</td><td>NGƯỜI ĐẠI DIỆN MỚI</td></tr>
        <tr><td class="muted">(Ký, ghi rõ họ tên)</td><td class="muted">(Ký, ghi rõ họ tên)</td><td class="muted">(Ký, ghi rõ họ tên)</td></tr>
        <tr class="signature-space"><td></td><td></td><td></td></tr>
        <tr><td>{{ $contract->landlord_name_snapshot ?: $setting->landlord_name }}</td><td>{{ data_get($old, 'full_name') }}</td><td>{{ data_get($new, 'full_name') }}</td></tr>
    </table>
</body>
</html>
