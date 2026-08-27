<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $attributes = [
        'status' => 'active',
        'must_change_password' => false,
    ];

    public const ROLE_ADMIN = 1;

    public const ROLE_USER = 2;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SETTLING = 'settling';

    public const STATUS_FORMER = 'former';

    public const STATUS_LOCKED = 'locked';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role_id',
        'password',
        'status',
        'activated_at',
        'terms_accepted_at',
        'last_login_at',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activated_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    public function contractHistories()
    {
        return $this->hasMany(ContractHistory::class);
    }

    public function hasRole(string ...$roles): bool
    {
        $roleName = strtolower((string) $this->role?->role_name);
        $roles = array_map('strtolower', $roles);

        return in_array($roleName, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isClient(): bool
    {
        return $this->hasRole('user');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canAccessPortal(): bool
    {
        return in_array(
            $this->status,
            [self::STATUS_ACTIVE, self::STATUS_SETTLING, self::STATUS_FORMER],
            true
        );
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
