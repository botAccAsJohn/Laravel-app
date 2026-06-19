<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'description',
    ];

    /**
     * The "booted" method of the model.
     * We force the guard_name to 'web' for all RBAC roles by default.
     * This prevents Spatie from accidentally assigning roles to the 'admin'
     * guard when an administrator creates a role from the admin panel.
     */
    protected static function booted(): void
    {
        static::creating(function (Role $role) {
            if (empty($role->guard_name) || $role->guard_name === config('auth.defaults.guard')) {
                $role->guard_name = 'web';
            }
        });
    }
}
