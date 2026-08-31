<div style="font-family:'Times New Roman',serif;color:#111;line-height:1.65;font-size:15px">
    <p style="margin:0;text-align:center;font-weight:700;font-size:17px">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
    <p style="margin:2px 0 28px;text-align:center;font-weight:700">Độc lập – Tự do – Hạnh phúc</p>
    <h1 style="margin:0;text-align:center;font-size:21px">PHỤ LỤC HỢP ĐỒNG THUÊ PHÒNG TRỌ</h1>
    <p style="margin:4px 0 26px;text-align:center">Số: <strong>{{ $appendix->code }}</strong></p>
    <p>Căn cứ Hợp đồng số <strong>{{ $appendix->contract->contract_code }}</strong> đã ký ngày {{ $appendix->contract->signed_at?->format('d/m/Y') }} giữa Bên A và Bên B;</p>
    @if(filled($appendix->legal_basis))<p>Căn cứ: {{ $appendix->legal_basis }}</p>@endif
    <p>Hai bên xem xét nội dung phụ lục với các điều khoản sau:</p>
    <h2 style="margin:24px 0 8px;font-size:17px">{{ $appendix->title }}</h2>
    <div style="white-space:pre-line;text-align:justify">{{ $appendix->content }}</div>
    @if($appendix->isPriceAdjustment())
        <table style="margin:18px 0;width:100%;border-collapse:collapse">
            <thead><tr><th style="border:1px solid #777;padding:7px;text-align:left">Khoản thu</th><th style="border:1px solid #777;padding:7px;text-align:right">Đơn giá cũ</th><th style="border:1px solid #777;padding:7px;text-align:right">Đơn giá mới</th></tr></thead>
            <tbody>
                @foreach($appendix->price_adjustments as $field => $change)
                    <tr>
                        <td style="border:1px solid #777;padding:7px">{{ \App\Models\ContractAppendix::PRICE_FIELD_LABELS[$field] ?? $field }}</td>
                        <td style="border:1px solid #777;padding:7px;text-align:right">{{ number_format((float) ($change['old'] ?? 0), 0, ',', '.') }} {{ \App\Models\ContractAppendix::PRICE_FIELD_UNITS[$field] ?? 'đ' }}</td>
                        <td style="border:1px solid #777;padding:7px;text-align:right"><strong>{{ number_format((float) ($change['new'] ?? 0), 0, ',', '.') }} {{ \App\Models\ContractAppendix::PRICE_FIELD_UNITS[$field] ?? 'đ' }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <p style="margin-top:22px"><strong>Ngày bắt đầu áp dụng:</strong> {{ $appendix->effective_from?->format('d/m/Y') }}.</p>
    @if($appendix->isPriceAdjustment() && $appendix->effective_from?->day !== 1)
        <p><em>Đơn giá mới được dùng để tính hóa đơn từ kỳ dịch vụ bắt đầu sau ngày hiệu lực nêu trên.</em></p>
    @endif
    <p>Các nội dung khác của Hợp đồng số {{ $appendix->contract->contract_code }} không được nêu trong phụ lục này vẫn giữ nguyên. Phụ lục là một phần không tách rời của hợp đồng {{ $appendix->isExtension() ? 'sau khi hai bên ký và minh chứng bản ký được lưu trên hệ thống' : 'sau khi được người thuê đại diện chấp nhận' }}.</p>
    <table style="margin-top:42px;width:100%;border-collapse:collapse;text-align:center"><tr><td style="width:50%;border:0"><strong>ĐẠI DIỆN BÊN A</strong><br><span style="font-size:13px">Ban quản lý/Chủ nhà</span></td><td style="width:50%;border:0"><strong>ĐẠI DIỆN BÊN B</strong><br><span style="font-size:13px">Người thuê đại diện</span></td></tr><tr><td style="height:90px;border:0;vertical-align:bottom">{{ $appendix->contract->landlord_name_snapshot ?: 'Chủ nhà' }}</td><td style="height:90px;border:0;vertical-align:bottom">{{ $appendix->contract->tenant?->full_name }}</td></tr></table>
</div>
