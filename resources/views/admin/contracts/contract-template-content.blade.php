@php
    $hasContract = isset($contract);
    $blank = '........................................................';
    $representativeMember = $hasContract ? $contract->representativeMember : null;
    $representativeTenant = $representativeMember?->tenant ?? ($hasContract ? $contract->tenant : null);
    $signedDate = $hasContract ? $contract->signed_at : null;
    $propertyAddress = $hasContract ? ($contract->property_address_snapshot ?: $setting->property_address) : null;
    $landlordName = $hasContract ? ($contract->landlord_name_snapshot ?: $setting->landlord_name) : null;
    $landlordIdentity = $hasContract ? ($contract->landlord_identity_snapshot ?: $setting->landlord_identity_number) : null;
    $landlordPhone = $hasContract ? ($contract->landlord_phone_snapshot ?: $setting->landlord_phone) : null;
    $landlordAddress = $hasContract ? ($contract->landlord_address_snapshot ?: $setting->landlord_address) : null;
    $members = $hasContract ? $contract->currentMembers : collect();
    $conditionLabels = ['normal' => 'Sử dụng bình thường', 'damaged' => 'Có hư hỏng'];
@endphp

<style>
    .contract-document{font-family:"Times New Roman",serif;font-size:16px;line-height:1.55;color:#111}
    .contract-document p{margin:7px 0;text-align:justify}
    .contract-document .center{text-align:center}
    .contract-document .national{font-size:18px;font-weight:700}
    .contract-document .slogan{font-size:16px;margin-top:2px}
    .contract-document .title{font-size:22px;font-weight:700;margin:28px 0 2px}
    .contract-document .code{margin-bottom:22px;text-align:center}
    .contract-document .section-title{font-size:17px;font-weight:700;margin:22px 0 8px;text-transform:uppercase}
    .contract-document .party-title{font-weight:700;margin-top:12px}
    .contract-document .line{display:inline-block;min-width:110px;border-bottom:1px dotted #222;text-align:center;font-weight:600}
    .contract-document .line.long{min-width:260px}
    .contract-document .line.short{min-width:60px}
    .contract-document .draft{border:2px solid #b91c1c;color:#b91c1c;font-weight:700;text-align:center;padding:7px;margin:14px 0}
    .contract-document table{width:100%;border-collapse:collapse;margin:10px 0 14px}
    .contract-document th,.contract-document td{border:1px solid #555;padding:6px 7px;text-align:left;vertical-align:top}
    .contract-document th{font-weight:700;text-align:center}
    .contract-document .no-border td{border:0;padding:3px 0}
    .contract-document .label{width:185px;font-weight:700}
    .contract-document .signatures td{border:0;text-align:center;width:50%;padding-top:10px}
    .contract-document .signature-space{height:95px;vertical-align:bottom}
    .contract-document .muted{color:#555;font-size:14px}
    @media print{.contract-document{font-size:14px}.contract-document .section-title{break-after:avoid}.contract-document table{break-inside:auto}.contract-document tr{break-inside:avoid}}
</style>

<div class="contract-document">
    <div class="center national">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
    <div class="center slogan">Độc lập – Tự do – Hạnh phúc</div>
    <div class="center title">HỢP ĐỒNG THUÊ PHÒNG TRỌ</div>
    <div class="code">Số: <strong>{{ $hasContract ? $contract->contract_code : $blank }}</strong></div>

    @if($hasContract && !$signedDate)
        <div class="draft">BẢN DỰ THẢO – CHƯA CÓ HIỆU LỰC</div>
    @endif

    <p>
        Hôm nay, ngày <span class="line short">{{ $signedDate?->format('d') ?: '' }}</span>
        tháng <span class="line short">{{ $signedDate?->format('m') ?: '' }}</span>
        năm <span class="line short">{{ $signedDate?->format('Y') ?: '' }}</span>,
        tại <span class="line long">{{ $propertyAddress ?: '' }}</span>, chúng tôi gồm:
    </p>

    <div class="section-title">I. Thông tin các bên</div>
    <p class="party-title">1. Bên cho thuê (Bên A)</p>
    <table class="no-border">
        <tr><td class="label">Họ và tên</td><td>{{ $landlordName ?: $blank }}</td></tr>
        <tr><td class="label">Ngày sinh</td><td>{{ $hasContract ? ($setting->landlord_date_of_birth?->format('d/m/Y') ?: $blank) : $blank }}</td></tr>
        <tr><td class="label">CCCD</td><td>{{ $landlordIdentity ?: $blank }}</td></tr>
        <tr><td class="label">Ngày cấp, nơi cấp</td><td>{{ $hasContract ? (($setting->landlord_identity_issued_at?->format('d/m/Y') ?: $blank).' – '.($setting->landlord_identity_issued_by ?: $blank)) : $blank }}</td></tr>
        <tr><td class="label">Số điện thoại</td><td>{{ $landlordPhone ?: $blank }}</td></tr>
        <tr><td class="label">Địa chỉ</td><td>{{ $landlordAddress ?: $blank }}</td></tr>
    </table>

    <p class="party-title">2. Bên thuê (Bên B) – Người đại diện</p>
    <table class="no-border">
        <tr><td class="label">Họ và tên</td><td>{{ $representativeMember?->full_name ?? $representativeTenant?->full_name ?? $blank }}</td></tr>
        <tr><td class="label">Ngày sinh</td><td>{{ $representativeMember?->date_of_birth?->format('d/m/Y') ?? $representativeTenant?->date_of_birth?->format('d/m/Y') ?? $blank }}</td></tr>
        <tr><td class="label">Giới tính</td><td>{{ $representativeTenant ? (['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'][$representativeTenant->gender] ?? $blank) : $blank }}</td></tr>
        <tr><td class="label">CCCD</td><td>{{ $representativeMember?->identity_number ?? $representativeTenant?->cccd ?? $blank }}</td></tr>
        <tr><td class="label">Ngày cấp, nơi cấp</td><td>{{ $representativeTenant?->cccd_issue_date?->format('d/m/Y') ?? $blank }} – {{ $representativeTenant?->cccd_issue_place ?? $blank }}</td></tr>
        <tr><td class="label">Số điện thoại</td><td>{{ $representativeMember?->phone ?? $representativeTenant?->phone ?? $blank }}</td></tr>
        <tr><td class="label">Email</td><td>{{ $representativeTenant?->email ?? $blank }}</td></tr>
        <tr><td class="label">Địa chỉ</td><td>{{ $representativeMember?->address ?? $representativeTenant?->address ?? $blank }}</td></tr>
    </table>

    <div class="section-title">II. Nội dung thuê phòng</div>
    <table>
        <tr><td class="label">Phòng thuê</td><td>{{ $hasContract ? ($contract->room?->room_code ?: $blank) : $blank }}</td></tr>
        <tr><td class="label">Vị trí, diện tích</td><td>{{ $hasContract ? 'Tầng '.($contract->room?->floor ?? '—').' – '.number_format((float) ($contract->room?->area ?? 0), 2, ',', '.').' m²' : $blank }}</td></tr>
        <tr><td class="label">Sức chứa</td><td>{{ $hasContract ? ($contract->room?->max_people ?? '—').' người' : $blank }}</td></tr>
        <tr><td class="label">Địa chỉ phòng trọ</td><td>{{ $propertyAddress ?: $blank }}</td></tr>
        <tr><td class="label">Thời hạn thuê</td><td>{{ $hasContract ? $contract->start_date?->format('d/m/Y') : $blank }} đến {{ $hasContract ? $contract->end_date?->format('d/m/Y') : $blank }}</td></tr>
        <tr><td class="label">Ngày nhận phòng dự kiến</td><td>{{ $hasContract ? ($contract->scheduled_move_in_date?->format('d/m/Y') ?: $blank) : $blank }}</td></tr>
        <tr><td class="label">Số người ở</td><td>{{ $hasContract ? $contract->number_of_people.' người' : $blank }}</td></tr>
        <tr><td class="label">Tiền phòng</td><td>{{ $hasContract ? number_format((float) $contract->monthly_rent, 0, ',', '.').'đ/tháng' : $blank }}</td></tr>
        <tr><td class="label">Tiền cọc</td><td>{{ $hasContract ? number_format((float) $contract->deposit_amount, 0, ',', '.').'đ' : $blank }}</td></tr>
        <tr><td class="label">Tiền điện</td><td>{{ number_format((float) $setting->electric_price, 0, ',', '.') }}đ/kWh, tính theo chỉ số công tơ</td></tr>
        <tr><td class="label">Tiền nước</td><td>{{ number_format((float) $setting->water_price, 0, ',', '.') }}đ/m³, tính theo chỉ số đồng hồ</td></tr>
        <tr><td class="label">Internet</td><td>{{ number_format((float) $setting->internet_fee, 0, ',', '.') }}đ/phòng/tháng</td></tr>
        <tr><td class="label">Dịch vụ chung</td><td>{{ number_format((float) $setting->service_fee, 0, ',', '.') }}đ/phòng/tháng</td></tr>
    </table>

    <p><strong>Thanh toán:</strong> Trước khi nhận phòng, Bên B chỉ thanh toán tiền cọc. Tiền cọc được giữ đến khi kết thúc hợp đồng để hoàn trả hoặc khấu trừ khi quyết toán.</p>
    <p>Vào ngày {{ str_pad((string) ($setting->invoice_day ?: 5), 2, '0', STR_PAD_LEFT) }} hằng tháng, Bên B thanh toán tiền phòng, điện, nước, Internet và dịch vụ của tháng liền trước. Tiền phòng tháng đầu tính theo số ngày thuê thực tế; nếu thời gian thuê trong tháng không quá 5 ngày thì được miễn tiền phòng, các khoản sử dụng thực tế vẫn được tính.</p>
    <p><strong>Tài khoản nhận tiền:</strong> {{ $setting->bank_id ?: $blank }} – {{ $setting->bank_account_no ?: $blank }} – {{ $setting->bank_account_name ?: $blank }}.</p>

    <div class="section-title">III. Danh sách người thuê</div>
    <table>
        <thead><tr><th>Họ và tên</th><th>Vai trò</th><th>Ngày sinh</th><th>CCCD</th><th>Liên hệ</th><th>Địa chỉ</th></tr></thead>
        <tbody>
        @if($hasContract)
            @forelse($members as $member)
                <tr>
                    <td>{{ $member->full_name }}</td>
                    <td>{{ $member->role === \App\Models\ContractTenant::ROLE_REPRESENTATIVE ? 'Người đại diện' : 'Người thuê' }}</td>
                    <td>{{ $member->date_of_birth?->format('d/m/Y') ?: '—' }}<br>{{ $member->tenant ? (['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'][$member->tenant->gender] ?? '—') : '—' }}</td>
                    <td>{{ $member->identity_number ?: '—' }}<br><span class="muted">{{ $member->tenant?->cccd_issue_date?->format('d/m/Y') ?: '—' }} · {{ $member->tenant?->cccd_issue_place ?: '—' }}</span></td>
                    <td>{{ $member->phone ?: '—' }}<br><span class="muted">{{ $member->tenant?->email ?: '—' }}</span></td>
                    <td>{{ $member->address ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Chưa có danh sách người thuê.</td></tr>
            @endforelse
        @else
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
        @endif
        </tbody>
    </table>

    <div class="section-title">IV. Chỉ số điện nước và tài sản bàn giao</div>
    <p><strong>Chỉ số tham chiếu:</strong> Điện {{ isset($referenceReading) && $referenceReading ? $referenceReading->electricity_new.' kWh' : $blank }}; Nước {{ isset($referenceReading) && $referenceReading ? $referenceReading->water_new.' m³' : $blank }}.</p>
    <table>
        <thead><tr><th>Tài sản, vật dụng</th><th>Số lượng</th><th>Tình trạng</th><th>Ghi chú</th></tr></thead>
        <tbody>
        @if($hasContract && $contract->move_in_inventory_snapshotted_at)
            @forelse($contract->handoverItems as $item)
                <tr><td>{{ $item->name }}</td><td>{{ $item->is_quantifiable ? $item->quantity : 'Có' }}</td><td>{{ $conditionLabels[$item->condition] ?? 'Chưa xác định' }}</td><td>{{ $item->note ?: '—' }}</td></tr>
            @empty
                <tr><td colspan="4">Không có tài sản bàn giao được khai báo.</td></tr>
            @endforelse
        @elseif($hasContract)
            @forelse($contract->room?->amenities ?? collect() as $asset)
                <tr><td>{{ $asset->name }}</td><td>{{ $asset->is_quantifiable ? $asset->pivot->quantity : 'Có' }}</td><td>{{ $conditionLabels[$asset->pivot->condition] ?? 'Chưa xác định' }}</td><td>{{ $asset->pivot->note ?: '—' }}</td></tr>
            @empty
                <tr><td colspan="4">Không có tài sản bàn giao được khai báo.</td></tr>
            @endforelse
        @else
            <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
        @endif
        </tbody>
    </table>

    @if($hasContract && filled($contract->note))
        <p><strong>Ghi chú hợp đồng:</strong> {{ $contract->note }}</p>
    @endif

    <div class="section-title">V. Quyền và nghĩa vụ của các bên</div>
    <p><strong>Bên A:</strong> Bàn giao phòng và tài sản đúng thỏa thuận; bảo đảm nguồn điện, nước, Internet và điều kiện sử dụng chung; thông báo các thay đổi về đơn giá theo quy định.</p>
    <p><strong>Bên B:</strong> Thanh toán đầy đủ, đúng hạn; sử dụng phòng đúng mục đích; bảo quản tài sản; giữ gìn an ninh, vệ sinh; đăng ký người ở và phương tiện theo quy định; không tự ý sửa chữa hoặc cho thuê lại khi chưa được đồng ý.</p>
    <p>Một bên muốn chấm dứt hợp đồng trước thời hạn phải thông báo cho bên còn lại ít nhất 30 ngày, trừ trường hợp hai bên có thỏa thuận khác hoặc pháp luật quy định khác.</p>
    <p>Khi kết thúc hợp đồng, hai bên chốt chỉ số điện nước, kiểm tra tài sản, đối chiếu công nợ và quyết toán tiền cọc. Khoản khấu trừ phải có căn cứ và được ghi nhận.</p>
    <div class="section-title">VI. Cam kết, hiệu lực và giải quyết tranh chấp</div>
    <p>Hai bên cam kết thông tin cung cấp là đúng sự thật, tự nguyện giao kết, có đầy đủ quyền và năng lực thực hiện hợp đồng; Bên A cam kết có quyền cho thuê hợp pháp đối với phòng nêu trên.</p>
    <p>Hợp đồng có hiệu lực kể từ thời điểm hai bên ký, trừ khi hai bên có thỏa thuận khác bằng văn bản. Mọi sửa đổi, bổ sung, gia hạn hoặc chấm dứt phải được ghi nhận bằng văn bản hoặc trên hệ thống và được các bên xác nhận.</p>
    <p>Tranh chấp được ưu tiên giải quyết bằng thương lượng; nếu không đạt được thỏa thuận, một trong các bên có quyền yêu cầu cơ quan có thẩm quyền giải quyết theo pháp luật.</p>
    <p>Hợp đồng được lập thành 02 bản có giá trị như nhau, mỗi bên giữ 01 bản. Hai bên đã đọc, hiểu và đồng ý với toàn bộ nội dung hợp đồng.</p>

    <table class="signatures">
        <tr><td><strong>ĐẠI DIỆN BÊN A</strong><br><span>(Ký, ghi rõ họ tên)</span></td><td><strong>ĐẠI DIỆN BÊN B</strong><br><span>(Ký, ghi rõ họ tên)</span></td></tr>
        <tr><td class="signature-space"><strong>{{ $landlordName ?: 'Chủ nhà trọ' }}</strong></td><td class="signature-space"><strong>{{ $representativeMember?->full_name ?? $representativeTenant?->full_name ?? 'Khách thuê' }}</strong></td></tr>
    </table>

    @if($hasContract)
        <p class="center muted">Trạng thái: {{ $contract->status_label }} · Ngày ký: {{ $signedDate?->format('d/m/Y H:i') ?: 'Chưa ký' }}</p>
    @endif
</div>
