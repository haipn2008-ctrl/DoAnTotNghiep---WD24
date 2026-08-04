<div class="col-lg-6">

    <div class="card shadow-sm border-0 h-100 contract-editor-card">

        {{-- Header --}}
        <div class="card-header bg-white border-bottom">

            <label class="form-label fw-semibold mb-2">
                Mẫu hợp đồng
            </label>

            <select
                class="form-select"
                id="templateSelect"
                name="template_id">

                <option value="">
                    Hợp đồng thuê phòng trọ tiêu chuẩn
                </option>

                @foreach($templates ?? [] as $template)
                    <option value="{{ $template->id }}">
                        {{ $template->name }}
                    </option>
                @endforeach

            </select>

        </div>

        {{-- Body --}}
        <div class="card-body p-0 d-flex flex-column">

            <div class="px-3 py-2 border-bottom">
                <label class="form-label fw-semibold mb-0">
                    Nội dung hợp đồng
                </label>
            </div>

            <div class="editor-toolbar">
                <div class="btn-group">

                    <button
                        type="button"
                        class="btn btn-success active"
                        id="editorBtn">
                        Soạn thảo
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-success"
                        id="previewBtn">
                        Xem trước
                    </button>

                </div>
            </div>

            {{-- CKEditor --}}
            <div id="editorWrapper" class="flex-grow-1">

                <textarea
                    id="contractEditor"
                    name="contract_content">@if(old('contract_content')){!! old('contract_content') !!}@else@verbatim
<div style="font-family:'Times New Roman',serif;font-size:16px;line-height:1.5;color:#000;">

    <p style="text-align:center;font-weight:bold;font-size:18px;margin:0;">
        CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM
    </p>

    <p style="text-align:center;font-size:17px;margin:6px 0 40px;">
        <strong>Độc lập – Tự do – Hạnh phúc</strong>
    </p>

    <p style="text-align:center;font-weight:bold;font-size:18px;text-transform:uppercase;margin:45px 0 35px;">
        HỢP ĐỒNG THUÊ PHÒNG TRỌ
    </p>

    <p>
        Hôm nay ngày <u>{{created_day}}</u>
        tháng <u>{{created_month}}</u>
        năm <u>{{created_year}}</u>;
        tại địa chỉ: <u>{{house_address}}</u>
    </p>

    <p><strong>Chúng tôi gồm:</strong></p>

    <p><strong>1. Đại diện bên cho thuê phòng trọ (Bên A):</strong></p>

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

    <p><strong>2. Bên thuê phòng trọ (Bên B):</strong></p>

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
        Tiền đặt cọc: <u>{{deposit}}</u>
    </p>

    <p>
        Hợp đồng có giá trị kể từ ngày
        <u>{{start_day}}</u> tháng <u>{{start_month}}</u> năm <u>{{start_year}}</u>
        đến ngày
        <u>{{end_day}}</u> tháng <u>{{end_month}}</u> năm <u>{{end_year}}</u>
    </p>

    <h3 style="font-size:20px;font-weight:bold;margin-top:40px;margin-bottom:15px;">
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

    <h3 style="font-size:20px;font-weight:bold;margin-top:30px;margin-bottom:15px;">
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

    <p>- Bên A phải trả lại tiền đặt cọc cho bên B.</p>

    <p>
        - Bên nào vi phạm điều khoản chung thì phải chịu trách nhiệm trước pháp luật.
    </p>

    <p>
        - Hợp đồng được lập thành 02 bản có giá trị pháp lý như nhau,
        mỗi bên giữ một bản.
    </p>

    <table style="width:100%;text-align:center;margin-top:60px;border-collapse:collapse;">
        <tbody>
            <tr>
                <td style="width:50%;border:0;">
                    <strong>ĐẠI DIỆN BÊN A</strong><br>
                    <span>(Bên cho thuê)</span>
                </td>
                <td style="width:50%;border:0;">
                    <strong>ĐẠI DIỆN BÊN B</strong><br>
                    <span>(Bên thuê)</span>
                </td>
            </tr>
            <tr>
                <td style="height:120px;vertical-align:bottom;border:0;">
                    <strong>Nguyễn Văn A</strong><br>
                    <small>Chủ nhà trọ</small>
                </td>
                <td style="height:120px;vertical-align:bottom;border:0;">
                    <strong>{{tenant_name}}</strong><br>
                    <small>Khách thuê</small>
                </td>
            </tr>
        </tbody>
    </table>

</div>
@endverbatim@endif</textarea>

            </div>

            {{-- Preview --}}
            <div
                id="previewWrapper"
                class="flex-grow-1"
                style="display:none">

                <div
                    id="previewContent"
                    class="contract-preview">
                </div>

            </div>

        </div>

    </div>

</div>
