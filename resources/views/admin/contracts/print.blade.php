<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><title>{{ $contract->signed_at ? 'Hợp đồng' : 'Bản dự thảo' }} {{ $contract->contract_code }}</title>
<style>@page{size:A4;margin:18mm}body{font-family:"Times New Roman",serif;font-size:15px;line-height:1.55;color:#111}.page{max-width:180mm;margin:auto}.center{text-align:center}.meta,.inventory{border-collapse:collapse;width:100%}.meta td{padding:5px 0;vertical-align:top}.inventory th,.inventory td{border:1px solid #777;padding:6px;text-align:left;vertical-align:top}.label{width:190px;font-weight:bold}.section{margin-top:22px}.draft{border:3px solid #b91c1c;color:#b91c1c;text-align:center;font-size:22px;font-weight:bold;padding:8px;margin:18px 0}.signatures{display:grid;grid-template-columns:1fr 1fr;text-align:center;margin-top:50px;gap:50px}@media print{button{display:none}}</style></head>
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
    <table class="meta"><tr><td class="label">Thời hạn thuê</td><td>{{ $contract->start_date->format('d/m/Y') }} đến {{ $contract->end_date->format('d/m/Y') }}</td></tr><tr><td class="label">Tiền thuê</td><td>{{ number_format($contract->monthly_rent,0,',','.') }}đ/tháng</td></tr><tr><td class="label">Tiền cọc</td><td>{{ number_format($contract->deposit_amount,0,',','.') }}đ — giữ để quyết toán cuối hợp đồng</td></tr><tr><td class="label">Tiền phòng tháng đầu</td><td>{{ number_format($contract->first_month_rent_amount,0,',','.') }}đ — {{ $contract->first_month_rent_days <= 5 ? 'được miễn do còn không quá 5 ngày' : $contract->first_month_rent_days.' ngày, tính theo giá ngày' }}</td></tr><tr><td class="label">Tổng ban đầu</td><td>{{ number_format($contract->deposit_amount + $contract->first_month_rent_amount,0,',','.') }}đ</td></tr><tr><td class="label">Số người dự kiến</td><td>{{ $contract->number_of_people }}</td></tr><tr><td class="label">Ngày nhận phòng dự kiến</td><td>{{ $contract->scheduled_move_in_date?->format('d/m/Y') ?: '—' }}</td></tr></table>
    <h3 class="section">DỊCH VỤ ĐĂNG KÝ VÀ TÀI SẢN BÀN GIAO</h3>
    <table class="meta"><tr><td class="label">Tiện nghi mặc định</td><td>Wi-Fi và máy lạnh (đã bao gồm trong giá thuê)</td></tr><tr><td class="label">Dịch vụ chung tính phí</td><td>{{ $contract->service_enabled ? 'Có đăng ký' : 'Không đăng ký' }}</td></tr><tr><td class="label">Trông xe</td><td>{{ $contract->parking_quantity > 0 ? ($contract->parking_vehicle_label ?? 'Xe máy').' × '.$contract->parking_quantity : 'Không đăng ký' }}</td></tr></table>
    @if($contract->move_in_inventory_snapshotted_at)
        <table class="inventory"><thead><tr><th>Tài sản / vật dụng</th><th>Số lượng</th><th>Tình trạng</th><th>Ghi chú</th></tr></thead><tbody>@forelse($contract->handoverItems as $item)<tr><td>{{ $item->name }}</td><td>{{ $item->is_quantifiable ? $item->quantity : 'Có' }}</td><td>{{ $item->condition === 'damaged' ? 'Có hư hỏng' : 'Sử dụng bình thường' }}</td><td>{{ $item->note ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="center">Không có tài sản bàn giao được khai báo.</td></tr>@endforelse</tbody></table>
    @else
        <p><em>Phiếu tài sản được chốt khi bản nháp được gửi chờ ký.</em></p>
    @endif
    <p>Điện, nước và dịch vụ được tính theo đơn giá/cấu hình áp dụng trong từng kỳ hóa đơn và theo chỉ số đã xác nhận. Việc ký hợp đồng không đồng nghĩa với việc đã nhận phòng; check-in và biên bản bàn giao được ghi nhận riêng.</p>
    <h3 class="section">TRÁCH NHIỆM CHUNG</h3><p>Hai bên thực hiện đúng nghĩa vụ thanh toán, bảo quản tài sản, tuân thủ pháp luật và quy định của cơ sở. Mọi sửa đổi, gia hạn, checkout hoặc quyết toán phải được ghi nhận thành hành động/chứng từ riêng.</p>
    <div class="signatures"><div><strong>ĐẠI DIỆN BÊN A</strong><br><small>(Ký, ghi rõ họ tên)</small><div style="height:90px"></div><strong>{{ $landlordName ?: 'Chưa cấu hình' }}</strong></div><div><strong>ĐẠI DIỆN BÊN B</strong><br><small>(Ký, ghi rõ họ tên)</small><div style="height:90px"></div><strong>{{ $representative?->full_name ?? $contract->tenant->full_name }}</strong></div></div>
    <p class="center" style="margin-top:40px;color:#555">Trạng thái hệ thống khi in: {{ $contract->status_label }} · Ngày ký: {{ $signedDate?->format('d/m/Y H:i') ?: 'Chưa ký' }}</p>
</div><script>window.onload=()=>window.print()</script></body></html>
