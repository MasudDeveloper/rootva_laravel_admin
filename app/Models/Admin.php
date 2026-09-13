<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admin_users';

    public $timestamps = false; // Legacy table doesn't have standard timestamps

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'permissions',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin' || $this->username === 'admin' || $this->id === 1;
    }

    public function hasPermission(string $permissionKey): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->permissions ?? [];
        return in_array($permissionKey, $permissions);
    }
}

