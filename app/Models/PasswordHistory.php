<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Exercise 52.2 — Password History
 *
 * Stores bcrypt hashes of a user's previous passwords.
 * Only hashes are persisted — plain-text is NEVER stored.
 *
 * Why hash previous passwords?
 * ────────────────────────────
 * If histories were stored as plain-text and the database were breached,
 * an attacker would immediately know N previous passwords for every user,
 * making account takeover trivial (users often reuse passwords across sites).
 * Bcrypt hashes are one-way: the attacker must crack each hash independently,
 * which — at bcrypt cost 12 — takes billions of years per hash on commodity
 * hardware. This means the "reuse check" is safe to store.
 */
class PasswordHistory extends Model
{
    public $timestamps = false; // we only track created_at manually

    protected $fillable = ['user_id', 'password'];

    protected $hidden = ['password'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
