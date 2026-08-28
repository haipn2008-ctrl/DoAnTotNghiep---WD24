<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    public const CLAUSE_LABELS = [
        'deposit_payment' => 'Thanh toán tiền cọc',
        'monthly_payment' => 'Thanh toán hằng tháng',
        'landlord_obligations' => 'Quyền và nghĩa vụ Bên A',
        'tenant_obligations' => 'Quyền và nghĩa vụ Bên B',
        'early_termination' => 'Chấm dứt trước thời hạn',
        'settlement' => 'Quyết toán khi kết thúc',
        'commitment' => 'Cam kết của các bên',
        'effectiveness' => 'Hiệu lực và sửa đổi hợp đồng',
        'dispute_resolution' => 'Giải quyết tranh chấp',
        'copies' => 'Số bản và giá trị pháp lý',
    ];

    public const DEFAULT_CLAUSES = [
        'deposit_payment' => 'Trước khi nhận phòng, Bên B chỉ thanh toán tiền cọc. Tiền cọc được giữ đến khi kết thúc hợp đồng để hoàn trả hoặc khấu trừ khi quyết toán.',
        'monthly_payment' => 'Vào ngày :invoice_day hằng tháng, Bên B thanh toán tiền phòng, điện, nước, Internet và dịch vụ của tháng liền trước. Tiền phòng tháng đầu tính theo số ngày thuê thực tế; nếu thời gian thuê trong tháng không quá 5 ngày thì được miễn tiền phòng, các khoản sử dụng thực tế vẫn được tính.',
        'landlord_obligations' => 'Bàn giao phòng và tài sản đúng thỏa thuận; bảo đảm nguồn điện, nước, Internet và điều kiện sử dụng chung; thông báo các thay đổi về đơn giá theo quy định.',
        'tenant_obligations' => 'Thanh toán đầy đủ, đúng hạn; sử dụng phòng đúng mục đích; bảo quản tài sản; giữ gìn an ninh, vệ sinh; đăng ký người ở và phương tiện theo quy định; không tự ý sửa chữa hoặc cho thuê lại khi chưa được đồng ý.',
        'early_termination' => 'Một bên muốn chấm dứt hợp đồng trước thời hạn phải thông báo cho bên còn lại ít nhất 30 ngày, trừ trường hợp hai bên có thỏa thuận khác hoặc pháp luật quy định khác.',
        'settlement' => 'Khi kết thúc hợp đồng, hai bên chốt chỉ số điện nước, kiểm tra tài sản, đối chiếu công nợ và quyết toán tiền cọc. Khoản khấu trừ phải có căn cứ và được ghi nhận.',
        'commitment' => 'Hai bên cam kết thông tin cung cấp là đúng sự thật, tự nguyện giao kết, có đầy đủ quyền và năng lực thực hiện hợp đồng; Bên A cam kết có quyền cho thuê hợp pháp đối với phòng nêu trên.',
        'effectiveness' => 'Hợp đồng có hiệu lực kể từ thời điểm hai bên ký, trừ khi hai bên có thỏa thuận khác bằng văn bản. Mọi sửa đổi, bổ sung, gia hạn hoặc chấm dứt phải được ghi nhận bằng văn bản hoặc trên hệ thống và được các bên xác nhận.',
        'dispute_resolution' => 'Tranh chấp được ưu tiên giải quyết bằng thương lượng; nếu không đạt được thỏa thuận, một trong các bên có quyền yêu cầu cơ quan có thẩm quyền giải quyết theo pháp luật.',
        'copies' => 'Hợp đồng được lập thành 02 bản có giá trị như nhau, mỗi bên giữ 01 bản. Hai bên đã đọc, hiểu và đồng ý với toàn bộ nội dung hợp đồng.',
    ];

    protected $fillable = [
        'name', 'version', 'clauses', 'is_active', 'effective_from', 'created_by',
    ];

    protected $casts = [
        'clauses' => 'array',
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
    ];

    public static function activeOrCreate(): self
    {
        return self::query()->where('is_active', true)->latest('version')->first()
            ?? self::query()->create([
                'name' => 'Mẫu hợp đồng thuê phòng trọ',
                'version' => 1,
                'clauses' => self::DEFAULT_CLAUSES,
                'is_active' => true,
                'effective_from' => now(),
            ]);
    }

    public function clause(string $key): string
    {
        return (string) (($this->clauses ?? [])[$key] ?? self::DEFAULT_CLAUSES[$key] ?? '');
    }
}
