<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    /**
     * $fillable: Danh sách các trường được phép gán dữ liệu hàng loạt (Mass Assignment).
     * Bao gồm tất cả các thông tin từ thông tin chung, tiền cọc, gia hạn đến chấm dứt hợp đồng.
     */
    protected $fillable = [
        'contract_code',
        'room_id',
        'tenant_id',
        'representative_tenant_id',
        'monthly_rent',
        'deposit_amount',
        'deposit_status',
        'deposit_paid_at',
        'number_of_people',
        'signed_at',
        'start_date',
        'end_date',
        'actual_end_date',
        'extended_at',
        'extend_start_date',
        'extend_end_date',
        'extend_reason',
        'extend_note',
        'terminated_at',
        'terminated_by',
        'termination_reason',
        'termination_note',
        'contract_file',
        'status',
        'note',
    ];

    /**
     * $casts: Tự động chuyển đổi kiểu dữ liệu khi truy xuất từ database.
     * Giúp làm việc với đối tượng Carbon (datetime) và Carbon/String (date) dễ dàng hơn.
     */
    protected $casts = [
        'signed_at'         => 'datetime',
        'deposit_paid_at'   => 'datetime',
        'extended_at'       => 'datetime',
        'terminated_at'     => 'datetime',
        'start_date'        => 'date',
        'end_date'          => 'date',
        'actual_end_date'   => 'date',
        'extend_start_date' => 'date',
        'extend_end_date'   => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships (Quan hệ giữa các bảng)
    |--------------------------------------------------------------------------
    */

    public function room() // Mỗi hợp đồng thuộc về 1 phòng
    {
        return $this->belongsTo(Room::class);
    }

    public function tenant() // Người thuê chính
    {
        return $this->belongsTo(Tenant::class);
    }

    public function representative() // Người đại diện (trỏ về bảng Tenants)
    {
        return $this->belongsTo(Tenant::class, 'representative_tenant_id');
    }

    public function invoices() // Một hợp đồng có thể có nhiều hóa đơn
    {
        return $this->hasMany(Invoice::class);
    }

    public function utilityReadings() // Các chỉ số điện nước liên quan
    {
        return $this->hasMany(UtilityReading::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods (Phương thức hỗ trợ kiểm tra trạng thái)
    | Giúp code Controller sạch hơn: thay vì $c->status == 'active', dùng $c->isActive()
    |--------------------------------------------------------------------------
    */

    public function isDraft()
    {
        return $this->status === 'draft';
    }
    public function isPendingSignature()
    {
        return $this->status === 'pending_signature';
    }
    public function isSigned()
    {
        return $this->status === 'signed';
    }
    public function isActive()
    {
        return $this->status === 'active';
    }
    public function isExpired()
    {
        return $this->status === 'expired';
    }
    public function isTerminated()
    {
        return $this->status === 'terminated';
    }
    public function isDepositPaid()
    {
        return $this->deposit_status === 'paid';
    }
}
