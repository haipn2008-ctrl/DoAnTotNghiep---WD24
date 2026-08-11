<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [

        'user_id',

        'payment_code',

        'full_name',

        'date_of_birth',
        'gender',

        'cccd',
        'cccd_issue_date',
        'cccd_issue_place',

        'phone',
        'email',

        'address',
    ];

    protected $casts = [

        'date_of_birth' => 'date',

        'cccd_issue_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (Tenant $tenant) {
            if (! $tenant->payment_code) {
                $tenant->updateQuietly([
                    'payment_code' => 'KH'.str_pad((string) $tenant->id, 8, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Tài khoản đăng nhập
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Các hợp đồng đứng tên
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    // Các hợp đồng mà tenant là người đại diện
    public function representativeContracts()
    {
        return $this->hasMany(
            Contract::class,
            'representative_tenant_id'
        );
    }

    public function memberContracts()
    {
        return $this->belongsToMany(Contract::class, 'contract_occupants')
            ->withPivot(['role', 'status', 'full_name', 'actual_move_in_at', 'actual_move_out_at'])
            ->withTimestamps();
    }

    public function contractOccupancies()
    {
        return $this->hasMany(ContractOccupant::class);
    }

    // Xe của người thuê (chuẩn bị cho giai đoạn 2)
    // public function vehicles()
    // {
    //     return $this->hasMany(Vehicle::class);
    // }

    // // Người ở (chuẩn bị cho giai đoạn 2)
    // public function occupants()
    // {
    //     return $this->hasMany(Occupant::class);
    // }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    // Lấy hợp đồng đang hoạt động
    public function activeContract()
    {
        return $this->contracts()
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
            ->first();
    }

    // Kiểm tra đang thuê phòng hay không
    public function isRenting()
    {
        return $this->contracts()
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
            ->exists();
    }
}
