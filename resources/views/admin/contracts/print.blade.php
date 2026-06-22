<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Hợp đồng thuê phòng trọ</title>

<style>

@page{
    size:A4;
    margin:15mm;
}

body{
    font-family:"Times New Roman", serif;
    font-size:16px;
    line-height:1.4;
}

.contract{
    width:210mm;
    min-height:297mm;
    margin:auto;
    background:white;
    padding:40px;
    border:1px solid #999;
    box-sizing:border-box;
}

.center{
    text-align:center;
}

.national{
    font-weight:bold;
    font-size:18px;
}

.slogan{
    text-align: center;
    font-size: 17px;
    margin-top: 6px;
    margin-bottom: 40px;
}

.title{
    text-align: center;
    font-size: 16px;
    font-weight: bold;
    text-transform: uppercase;
    margin-top: 45px;
    margin-bottom: 35px;
}

.line{
    display:inline-block;
    border-bottom:1px dotted #000;
    padding:0 5px;
    text-align:center;
}

.short{
    min-width:180px;
}
.small{
    min-width:15px;
}
.mini{
    min-width:80px;
}

.medium{
    min-width:250px;
}
.Large{
    min-width:350px;
}
.ExtraLarge{
    min-width:450px;
}
.big{
    min-width:520px;
}

.section{
    margin-top:20px;
}

.bold{
    font-weight:bold;
}
p{
    margin:12px 0;
    /* text-align:justify; */
}

/* h2{
    margin-top:40px;
    margin-bottom:20px;
} */
.info-row{
    margin:6px 0;
}

.info-row .line{
    display:inline-block;
    border-bottom:1px dotted #000;
    padding:0 3px;
}
.contract-info{
    margin-bottom:15px;
}
@media print{

    body{
        background:white;
    }

    .contract{
        border:none;
        padding:0;
        width:auto;
    }
     @page {
        margin: 20mm;
    }
}

</style>

</head>

<body>

<div class="contract">

<div class="center national">
     CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM
</div>

<div class="center slogan">
    Độc lập – Tự do – Hạnh phúc
</div>

<div class="center title">
    HỢP ĐỒNG THUÊ PHÒNG TRỌ
</div>
<p class="contract-info">
Hôm nay ngày
<strong class="line small">{{ \Carbon\Carbon::parse($contract->created_at)->format('d') }}</strong>
tháng
<strong class="line small">{{ \Carbon\Carbon::parse($contract->created_at)->format('m') }}</strong>
năm
<strong class="line small">{{ \Carbon\Carbon::parse($contract->created_at)->format('Y') }}</strong>;
tại địa chỉ:
<span class="line mini">
    Cầu Giấy - Hà Nội
</span>
</p>
<p>
    <span class="line big"></span>
</p>
<p class="bold">
Chúng tôi gồm:
</p>
<p>
1. Đại diện bên cho thuê phòng trọ (Bên A):
</p>

<p>
Ông/bà:
<strong class="line medium">Nguyễn Văn A</strong>

&nbsp;&nbsp;&nbsp;&nbsp;

Sinh ngày:
<strong class="line mini">08/01/2005</strong>
</p>

<p>
Nơi đăng ký hộ khẩu:
<strong class="line Large">Cầu Giấy - Hà Nội</strong>
</p>

<p>
Số CMND/CCCD:
<strong class="line short">012345678999</strong>

&nbsp;&nbsp;&nbsp;&nbsp;

cấp ngày:
<strong class="line mini">01/01/2023</strong>
</p>

<p>
tại:
<strong class="line ExtraLarge">Cục CSQLHC về TTXH</strong>
</p>

<p>
Số điện thoại:
<strong class="line Large">0865819798</strong>
</p>
<p>
2. Bên thuê phòng trọ (Bên B):
</p>

<p class="info-row">
Ông/bà:
<span class="line medium">
    {{ $contract->tenant->full_name }}
</span>

&nbsp;&nbsp;&nbsp;&nbsp;

Sinh ngày:
<span class="line mini"></span>
</p>

<p>
Nơi đăng ký HK thường trú:
<span class="line Large">
{{ $contract->tenant->address }}
</span>
</p>

<p>

Số CMND/CCCD:
<span class="line short">
{{ $contract->tenant->cccd }}
</span>

&nbsp;&nbsp;&nbsp;&nbsp;

cấp ngày 
<span class="line mini"></span>

</p>
<p>
tại:
<span  class="line ExtraLarge"></span>
</p>
<p>
Số điện thoại:
<span class="line Large">
{{ $contract->tenant->phone }}
</span>
</p>

<p class="bold">
Sau khi bàn bạc trên tinh thần dân chủ, hai bên cùng có lợi,
cùng thống nhất như sau:
</p>

<p>
Bên A đồng ý cho bên B thuê 01 phòng ở tại địa chỉ:
<span class="line short">
{{ $contract->room->address }}
Phòng {{ $contract->room->room_code }}
</span>
</p>
<p>
    <span class="line big"></span>
</p>

<p>
Giá thuê:
<span class="line short">
{{ number_format($contract->room->price,0,',','.') }}
</span>
đ/tháng
</p>

<p>
Hình thức thanh toán:
<span class="line Large">
Tiền mặt hoặc chuyển khoản
</span>
</p>

<p>
Tiền điện
<span class="line mini">
3.500
</span>
đ/kwh tính theo chỉ số công tơ,
thanh toán vào cuối các tháng.
</p>

<p>
Tiền nước:
<span class="line short">
100.000
</span>
đ/người thanh toán vào đầu các tháng.
</p>

<p>
Tiền đặt cọc:
<span class="line ExtraLarge">
{{ number_format($contract->deposit_amount,0,',','.') }}
</span>
</p>

<p>
Hợp đồng có giá trị kể từ ngày
<strong class="line small">{{ \Carbon\Carbon::parse($contract->created_at)->format('d') }}</strong>
tháng
<strong class="line small">{{ \Carbon\Carbon::parse($contract->created_at)->format('m') }}</strong>
năm
<strong class="line small">{{ \Carbon\Carbon::parse($contract->created_at)->format('Y') }}</strong>

{{-- {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }} --}}
đến ngày
<strong class="line small">{{ \Carbon\Carbon::parse($contract->end_date)->format('d') }}</strong>
tháng
<strong class="line small">{{ \Carbon\Carbon::parse($contract->end_date)->format('m') }}</strong>
năm
<strong class="line small">{{ \Carbon\Carbon::parse($contract->end_date)->format('Y') }}</strong>

{{-- {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }} --}}
</p>
<div style="margin-top:40px;">

    <h3 style="
        font-size:20px;
        font-weight:bold;
        margin-bottom:15px;
    ">
        TRÁCH NHIỆM CỦA CÁC BÊN
    </h3>

    <p class="bold">
        * Trách nhiệm của bên A:
    </p>

    <p>
        - Tạo mọi điều kiện thuận lợi để bên B thực hiện theo hợp đồng.
    </p>

    <p>
        - Cung cấp nguồn điện, nước, wifi cho bên B sử dụng.
    </p>

    <p class="bold">
        * Trách nhiệm của bên B:
    </p>

    <p>
        - Thanh toán đầy đủ các khoản tiền theo đúng thỏa thuận.
    </p>

    <p>
        - Bảo quản các trang thiết bị và cơ sở vật chất của bên A trang bị cho ban đầu (làm hỏng phải sửa, mất phải đền).
    </p>

    <p>
        - Không được tự ý sửa chữa, cải tạo cơ sở vật chất khi chưa được sự đồng ý của bên A.
    </p>

    <p>
        - Giữ gìn vệ sinh trong và ngoài khuôn viên của phòng trọ.
    </p>

    <p>
        - Bên B phải chấp hành mọi quy định của pháp luật Nhà nước và quy định của địa phương.
    </p>

    <p>
        - Nếu bên B cho khách ở qua đêm thì phải báo và được sự đồng ý của chủ nhà đồng thời phải chịu trách nhiệm về các hành vi vi phạm pháp luật của khách trong thời gian ở lại.
    </p>

    <div style="height:30px;"></div>

    <h3 style="
        font-size:20px;
        font-weight:bold;
        margin-bottom:15px;
    ">
        TRÁCH NHIỆM CHUNG
    </h3>

    <p>
        - Hai bên phải tạo điều kiện cho nhau thực hiện hợp đồng.
    </p>

    <p>
        - Trong thời gian hợp đồng còn hiệu lực nếu bên nào vi phạm các điều khoản đã thỏa thuận thì bên còn lại có quyền đơn phương chấm dứt hợp đồng; nếu vi phạm hợp đồng đó gây tổn thất cho bên bị vi phạm hợp đồng thì bên vi phạm hợp đồng phải bồi thường thiệt hại.
    </p>

    <p>
        - Một trong hai bên muốn chấm dứt hợp đồng trước thời hạn thì phải báo trước cho bên kia ít nhất 30 ngày và hai bên phải có sự thống nhất.
    </p>

    <p>
        - Bên A phải trả lại tiền đặt cọc cho bên B.
    </p>

    <p>
        - Bên nào vi phạm điều khoản chung thì phải chịu trách nhiệm trước pháp luật.
    </p>

    <p>
        - Hợp đồng được lập thành 02 bản có giá trị pháp lý như nhau, mỗi bên giữ một bản.
    </p>

</div>

<div style="page-break-after:always;"></div>
<div>

    <p>
        - Một trong hai bên muốn chấm dứt hợp đồng trước thời hạn
        thì phải báo trước cho bên kia ít nhất 30 ngày và hai bên
        phải có sự thống nhất.
    </p>

    <p>
        - Bên A phải trả lại tiền đặt cọc cho bên B.
    </p>

    <p>
        - Bên nào vi phạm điều khoản chung thì phải chịu trách nhiệm
        trước pháp luật.
    </p>

    <p>
        - Hợp đồng được lập thành 02 bản có giá trị pháp lý như nhau,
        mỗi bên giữ một bản.
    </p>

    <br><br><br>

    <hr style="margin-top:40px; margin-bottom:30px;">

    <table style="width:100%; text-align:center;">
        <tr>
            <td width="50%">
                <strong>
                    ĐẠI DIỆN BÊN A
                </strong>
                <br>
                <span>(Bên cho thuê)</span>
            </td>

            <td width="50%">
                <strong>
                    ĐẠI DIỆN BÊN B
                </strong>
                <br>
                <span>(Bên thuê)</span>
            </td>
        </tr>

        <tr>
            <td style="height:120px; vertical-align:bottom;">

                {{-- Sau này thay bằng ảnh chữ ký --}}
                {{-- <img src="{{ asset('uploads/signatures/landlord.png') }}"
                 height="80"> --}}

                <br>

                <strong>
                Nguyễn Văn A
                </strong>

                <br>

                <small>Chủ nhà trọ</small>

            </td>

            <td style="height:120px; vertical-align:bottom;">

                <br><br><br><br><br>

                <strong>
                    {{ $contract->tenant->full_name }}
                </strong>

                <br>

                <small>Khách thuê</small>

            </td>
        </tr>
    </table>  

</div>

</div>

<script>
window.onload = function () {
    window.print();
};
</script>

</body>
</html>