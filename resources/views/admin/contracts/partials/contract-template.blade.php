@verbatim
<style>
.contract-paper{
    font-family:"Times New Roman",serif;
    font-size:16px;
    line-height:1.65;
    color:#1f2937;
    background:#fff;
    max-width:820px;
    margin:0 auto;
    padding:56px 64px 70px;
    box-sizing:border-box;
    border:1px solid #e5e7eb;
    box-shadow:0 8px 28px rgba(15,23,42,.10);
}
.contract-paper p{margin:0 0 12px;text-align:justify;}
.contract-paper .national-title{
    text-align:center;font-weight:700;font-size:18px;margin:0;color:#111827;
}
.contract-paper .national-subtitle{
    text-align:center;font-size:17px;margin:7px 0 38px;
}
.contract-paper .contract-title{
    text-align:center;font-weight:700;font-size:20px;text-transform:uppercase;
    margin:38px 0 34px;color:#111827;
}
.contract-paper .section-title{
    font-size:19px;font-weight:700;margin:30px 0 14px;color:#111827;
}
.contract-paper .party-title{font-weight:700;margin-top:18px;}
.contract-paper .intro{font-weight:700;margin-top:18px;}
.contract-paper u{text-underline-offset:2px;}
.contract-paper .signature-table{
    width:100%;text-align:center;margin-top:55px;border-collapse:collapse;
}
.contract-paper .signature-table td{width:50%;border:0;padding:0;}
.contract-paper .signature-space{height:110px;vertical-align:bottom;}
@media(max-width:900px){
    .contract-paper{max-width:100%;padding:36px 30px 48px;box-shadow:none;border:0;}
}
</style>

<div class="contract-paper">

    <p class="national-title">
        CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM
    </p>

    <p class="national-subtitle">
        <strong>Độc lập – Tự do – Hạnh phúc</strong>
    </p>

    <p class="contract-title">
        HỢP ĐỒNG THUÊ PHÒNG TRỌ
    </p>

    <p>
        Hôm nay ngày <u>{{created_day}}</u>
        tháng <u>{{created_month}}</u>
        năm <u>{{created_year}}</u>;
        tại địa chỉ: <u>{{house_address}}</u>
    </p>

    <p><strong>Chúng tôi gồm:</strong></p>

    <p class="party-title"><strong>1. Đại diện bên cho thuê phòng trọ (Bên A):</strong></p>

    <p>
        Ông/bà: <u>Nguyễn Văn A</u>
        &nbsp;&nbsp;&nbsp;&nbsp;
        Sinh ngày: <u>08/01/2005</u>
    </p>

    <p>
        Nơi đăng ký hộ khẩu: <u>{{house_address}}</u>
    </p>

    <p>
        Số CMND/CCCD: <u>012345678999</u>
        &nbsp;&nbsp;&nbsp;&nbsp;
        cấp ngày: <u>01/01/2023</u>
    </p>

    <p>
        tại: <u>Cục CSQLHC về TTXH</u>
    </p>

    <p>
        Số điện thoại: <u>0865819798</u>
    </p>

    <p class="party-title"><strong>2. Bên thuê phòng trọ (Bên B):</strong></p>

    <p>
        Ông/bà: <u>{{tenant_name}}</u>
        &nbsp;&nbsp;&nbsp;&nbsp;
        Sinh ngày: <u>{{tenant_dob}}</u>
    </p>

    <p>
        Nơi đăng ký HK thường trú: <u>{{tenant_address}}</u>
    </p>

    <p>
        Số CMND/CCCD: <u>{{tenant_cccd}}</u>
        &nbsp;&nbsp;&nbsp;&nbsp;
        cấp ngày: <u>{{tenant_cccd_issue_date}}</u>
    </p>

    <p>
        tại: <u>{{tenant_cccd_issue_place}}</u>
    </p>

    <p>
        Số điện thoại: <u>{{tenant_phone}}</u>
    </p>

    <p>
        <strong>
            Sau khi bàn bạc trên tinh thần dân chủ, hai bên cùng có lợi,
            cùng thống nhất như sau:
        </strong>
    </p>

    <p>
        Bên A đồng ý cho bên B thuê 01 phòng ở tại địa chỉ:
        <u>{{house_address}}, phòng {{room}}</u>
    </p>

    <p>
        Giá thuê: <u>{{price}}</u> đ/tháng
    </p>

    <p>
        Hình thức thanh toán:
        <u>Tiền mặt hoặc chuyển khoản</u>
    </p>

    <p>
        Tiền điện <u>3.500</u> đ/kwh tính theo chỉ số công tơ,
        thanh toán vào cuối các tháng.
    </p>

    <p>
        Tiền nước: <u>100.000</u> đ/người thanh toán vào đầu các tháng.
    </p>

    <p>
        Tiền đặt cọc trước khi nhận phòng: <u>{{deposit}}</u>
    </p>

    <p>
        Tiền đặt cọc được giữ riêng đến khi hợp đồng kết thúc để hoàn trả hoặc khấu trừ khi quyết toán. Vào ngày 05 hằng tháng, bên thuê thanh toán tiền phòng, điện, nước và dịch vụ đã sử dụng trong tháng liền trước. Tiền phòng tháng đầu được tính theo số ngày thuê thực tế; nếu thời gian thuê trong tháng không quá 5 ngày thì được miễn tiền phòng, còn điện, nước và dịch vụ vẫn tính theo thực tế.
    </p>

    <p>
        Hợp đồng có giá trị kể từ ngày
        <u>{{start_day}}</u> tháng <u>{{start_month}}</u> năm <u>{{start_year}}</u>
        đến ngày
        <u>{{end_day}}</u> tháng <u>{{end_month}}</u> năm <u>{{end_year}}</u>
    </p>

    <h3 class="section-title">
        TRÁCH NHIỆM CỦA CÁC BÊN
    </h3>

    <p><strong>* Trách nhiệm của bên A:</strong></p>

    <p>- Tạo mọi điều kiện thuận lợi để bên B thực hiện theo hợp đồng.</p>

    <p>- Cung cấp nguồn điện, nước, wifi cho bên B sử dụng.</p>

    <p><strong>* Trách nhiệm của bên B:</strong></p>

    <p>- Thanh toán đầy đủ các khoản tiền theo đúng thỏa thuận.</p>

    <p>
        - Bảo quản các trang thiết bị và cơ sở vật chất của bên A trang bị
        cho ban đầu (làm hỏng phải sửa, mất phải đền).
    </p>

    <p>
        - Không được tự ý sửa chữa, cải tạo cơ sở vật chất khi chưa được
        sự đồng ý của bên A.
    </p>

    <p>- Giữ gìn vệ sinh trong và ngoài khuôn viên của phòng trọ.</p>

    <p>
        - Bên B phải chấp hành mọi quy định của pháp luật Nhà nước và
        quy định của địa phương.
    </p>

    <p>
        - Nếu bên B cho khách ở qua đêm thì phải báo và được sự đồng ý
        của chủ nhà đồng thời phải chịu trách nhiệm về các hành vi vi phạm
        pháp luật của khách trong thời gian ở lại.
    </p>

    <h3 class="section-title">
        TRÁCH NHIỆM CHUNG
    </h3>

    <p>- Hai bên phải tạo điều kiện cho nhau thực hiện hợp đồng.</p>

    <p>
        - Trong thời gian hợp đồng còn hiệu lực nếu bên nào vi phạm các
        điều khoản đã thỏa thuận thì bên còn lại có quyền đơn phương chấm
        dứt hợp đồng; nếu vi phạm hợp đồng đó gây tổn thất cho bên bị vi
        phạm hợp đồng thì bên vi phạm hợp đồng phải bồi thường thiệt hại.
    </p>

    <p>
        - Một trong hai bên muốn chấm dứt hợp đồng trước thời hạn thì phải
        báo trước cho bên kia ít nhất 30 ngày và hai bên phải có sự thống nhất.
    </p>

    <p>- Bên A hoàn lại phần tiền cọc còn lại cho Bên B sau khi đối trừ công nợ, hư hỏng và các nghĩa vụ có chứng từ.</p>

    <p>
        - Bên nào vi phạm điều khoản chung thì phải chịu trách nhiệm trước pháp luật.
    </p>

    <p>
        - Hợp đồng được lập thành 02 bản có giá trị pháp lý như nhau,
        mỗi bên giữ một bản.
    </p>

    <table class="signature-table">
        <tbody>
            <tr>
                <td>
                    <strong>ĐẠI DIỆN BÊN A</strong><br>
                    <span>(Bên cho thuê)</span>
                </td>
                <td>
                    <strong>ĐẠI DIỆN BÊN B</strong><br>
                    <span>(Bên thuê)</span>
                </td>
            </tr>
            <tr>
                <td class="signature-space">
                    <strong>Nguyễn Văn A</strong><br>
                    <small>Chủ nhà trọ</small>
                </td>
                <td class="signature-space">
                    <strong>{{tenant_name}}</strong><br>
                    <small>Khách thuê</small>
                </td>
            </tr>
        </tbody>
    </table>

</div>
@endverbatim
