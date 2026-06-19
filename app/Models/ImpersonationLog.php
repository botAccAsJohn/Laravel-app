<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit record created each time an admin impersonates a customer.
 *
 * @property int         $id
 * @property int         $admin_id
 * @property string      $admin_email
 * @property int         $target_user_id
 * @property string      $target_email
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon|null $stopped_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 */
class ImpersonationLog extends Model
{
    protected $fillable = [
        'admin_id',
        'admin_email',
        'target_user_id',
        'target_email',
        'ip_address',
        'user_agent',
        'stopped_at',
    ];

    protected function casts(): array
    {
        return [
            'stopped_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /** Mark the impersonation session as ended. */
    public function stop(): void
    {
        $this->update(['stopped_at' => now()]);
    }

    /** True if the impersonation session is still active. */
    public function isActive(): bool
    {
        return $this->stopped_at === null;
    }
}
