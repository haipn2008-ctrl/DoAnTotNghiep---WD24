<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [

        'user_id',

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
            ->where('status', 'active')
            ->first();
    }

    // Kiểm tra đang thuê phòng hay không
    public function isRenting()
    {
        return $this->contracts()
            ->where('status', 'active')
            ->exists();
    }
}
