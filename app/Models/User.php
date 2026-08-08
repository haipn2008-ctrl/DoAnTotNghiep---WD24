<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 1;

    public const ROLE_CLIENT = 2;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role_id',
        'password',
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
        return $this->hasRole('user', 'client');
    }
}
