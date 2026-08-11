<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><title>{{ $contract->signed_at ? 'Hợp đồng' : 'Bản dự thảo' }} {{ $contract->contract_code }}</title>
<style>@page{size:A4;margin:18mm}body{font-family:"Times New Roman",serif;font-size:15px;line-height:1.55;color:#111}.page{max-width:180mm;margin:auto}.center{text-align:center}.meta{border-collapse:collapse;width:100%}.meta td{padding:5px 0;vertical-align:top}.label{width:190px;font-weight:bold}.section{margin-top:22px}.draft{border:3px solid #b91c1c;color:#b91c1c;text-align:center;font-size:22px;font-weight:bold;padding:8px;margin:18px 0}.signatures{display:grid;grid-template-columns:1fr 1fr;text-align:center;margin-top:50px;gap:50px}@media print{button{display:none}}</style></head>
<body><div class="page">
@php
        $representative = $contract->representativeOccupant;
        $signedDate=$contract->signed_at;
        $propertyAddress=$contract->property_address_snapshot;
        $landlordName=$contract->landlord_name_snapshot;
        $landlordAddress=$contract->landlord_address_snapshot;
        $landlordPhone=$contract->landlord_phone_snapshot;
        $landlordIdentity=$contract->landlord_identity_snapshot;
    @endphp
    <div class="center"><strong>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong><br>Độc lập – Tự do – Hạnh phúc<h1>HỢP ĐỒNG THUÊ PHÒNG</h1><p>Số: {{ $contract->contract_code }}</p></div>
    @if(!$signedDate)<div class="draft">BẢN DỰ THẢO / CHƯA KÝ</div><p><strong>Lưu ý:</strong> tài liệu này chưa phải hợp đồng đã ký và không chứng minh khách đã nhận phòng.</p>@else<p>Hôm nay, ngày {{ $signedDate->format('d/m/Y') }}, tại {{ $propertyAddress ?: 'địa chỉ tài sản chưa được cấu hình trong snapshot' }}, hai bên gồm:</p>@endif

    <h3 class="section">BÊN CHO THUÊ (BÊN A)</h3>
    <table class="meta"><tr><td class="label">Họ tên</td><td>{{ $landlordName ?: 'Chưa cấu hình trong snapshot hợp đồng' }}</td></tr><tr><td class="label">CCCD/giấy tờ</td><td>{{ $landlordIdentity ?: 'Chưa cấu hình' }}</td></tr><tr><td class="label">Điện thoại</td><td>{{ $landlordPhone ?: 'Chưa cấu hình' }}</td></tr><tr><td class="label">Địa chỉ</td><td>{{ $landlordAddress ?: 'Chưa cấu hình' }}</td></tr></table>
    <h3 class="section">BÊN THUÊ (BÊN B)</h3>
    <table class="meta"><tr><td class="label">Họ tên</td><td>{{ $representative?->full_name ?? $contract->tenant->full_name }}</td></tr><tr><td class="label">Ngày sinh</td><td>{{ $representative?->date_of_birth?->format('d/m/Y') ?? $contract->tenant->date_of_birth?->format('d/m/Y') ?? '—' }}</td></tr><tr><td class="label">CCCD</td><td>{{ $representative?->identity_number ?? $contract->tenant->cccd }}</td></tr><tr><td class="label">Điện thoại</td><td>{{ $representative?->phone ?? $contract->tenant->phone }}</td></tr><tr><td class="label">Địa chỉ</td><td>{{ $representative?->address ?? $contract->tenant->address ?? '—' }}</td></tr></table>
    <h3 class="section">NỘI DUNG THỎA THUẬN</h3>
    <p>Bên A đồng ý cho Bên B thuê phòng <strong>{{ $contract->room->room_code }}</strong> tại <strong>{{ $propertyAddress ?: 'địa chỉ chưa cấu hình' }}</strong>.</p>
    <table class="meta"><tr><td class="label">Thời hạn thuê</td><td>{{ $contract->start_date->format('d/m/Y') }} đến {{ $contract->end_date->format('d/m/Y') }}</td></tr><tr><td class="label">Tiền thuê</td><td>{{ number_format($contract->monthly_rent,0,',','.') }}đ/tháng</td></tr><tr><td class="label">Tiền đặt cọc</td><td>{{ number_format($contract->deposit_amount,0,',','.') }}đ</td></tr><tr><td class="label">Số người dự kiến</td><td>{{ $contract->number_of_people }}</td></tr><tr><td class="label">Ngày nhận phòng dự kiến</td><td>{{ $contract->scheduled_move_in_date?->format('d/m/Y') ?: '—' }}</td></tr></table>
    <p>Điện, nước và dịch vụ được tính theo đơn giá/cấu hình áp dụng trong từng kỳ hóa đơn và theo chỉ số đã xác nhận. Việc ký hợp đồng không đồng nghĩa với việc đã nhận phòng; check-in và biên bản bàn giao được ghi nhận riêng.</p>
    <h3 class="section">TRÁCH NHIỆM CHUNG</h3><p>Hai bên thực hiện đúng nghĩa vụ thanh toán, bảo quản tài sản, tuân thủ pháp luật và quy định của cơ sở. Mọi sửa đổi, gia hạn, checkout, quyết toán hoặc xử lý cọc phải được ghi nhận thành hành động/chứng từ riêng.</p>
    <div class="signatures"><div><strong>ĐẠI DIỆN BÊN A</strong><br><small>(Ký, ghi rõ họ tên)</small><div style="height:90px"></div><strong>{{ $landlordName ?: 'Chưa cấu hình' }}</strong></div><div><strong>ĐẠI DIỆN BÊN B</strong><br><small>(Ký, ghi rõ họ tên)</small><div style="height:90px"></div><strong>{{ $representative?->full_name ?? $contract->tenant->full_name }}</strong></div></div>
    <p class="center" style="margin-top:40px;color:#555">Trạng thái hệ thống khi in: {{ $contract->status_label }} · Ngày ký: {{ $signedDate?->format('d/m/Y H:i') ?: 'Chưa ký' }}</p>
</div><script>window.onload=()=>window.print()</script></body></html>
