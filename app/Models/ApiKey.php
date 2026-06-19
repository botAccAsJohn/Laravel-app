<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Exercise 53.3 — API Key Model
 *
 * Design: store SHA-256 hash, NEVER the plain-text key.
 *
 * Why SHA-256 (not bcrypt/argon2id) for API keys?
 * ─────────────────────────────────────────────────
 * Passwords and API keys need DIFFERENT hashing strategies:
 *
 * ┌─────────────────┬─────────────────────────┬────────────────────────────┐
 * │ Property        │ Passwords               │ API Keys                   │
 * ├─────────────────┼─────────────────────────┼────────────────────────────┤
 * │ Source          │ Human-chosen (weak)     │ Cryptographically random   │
 * │ Dictionary risk │ HIGH — words, patterns  │ ZERO — 256 bits of entropy │
 * │ Brute-force     │ Feasible if unsalted    │ Infeasible regardless       │
 * │ Hash speed need │ SLOW (200–500 ms)       │ FAST (< 1 µs)              │
 * │ Verification    │ Once per session login  │ Every API request          │
 * │ Algorithm       │ argon2id / bcrypt       │ SHA-256                    │
 * │ Salted?         │ YES (bcrypt/argon auto) │ No (random key = its salt) │
 * └─────────────────┴─────────────────────────┴────────────────────────────┘
 *
 * A 32-byte random API key has 256 bits of entropy. Even SHA-256 without a
 * salt cannot be brute-forced — the universe will end first. bcrypt would
 * add ~200 ms to every API request for zero security benefit.
 */
class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'key_hash',
        'key_prefix',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = [
        'key_hash', // never leak the hash in API responses
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at'   => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Factory Methods ────────────────────────────────────────────────────

    /**
     * Generate a new API key, persist its hash, and return the plain-text
     * key exactly ONCE. The plain-text is NEVER stored.
     *
     * Usage:
     *   [$apiKey, $plainText] = ApiKey::generate($user, 'Production Key');
     *   // Show $plainText to user — it will NEVER be retrievable again.
     *
     * Key format: "sk-" prefix + 40 random hex chars = 43 total chars.
     * The prefix helps users identify keys in logs/configs.
     */
    public static function generate(User $user, string $name, ?\DateTimeInterface $expiresAt = null): array
    {
        // 20 random bytes → 40 hex chars. Entropy: 160 bits (overkill for
        // brute-force, easily sufficient for all practical purposes).
        $random   = bin2hex(random_bytes(20));
        $plain    = 'sk-' . $random;          // "sk-" prefix for identification
        $hash     = hash('sha256', $plain);    // store this
        $prefix   = substr($plain, 0, 8);      // "sk-ab12…" shown in UI

        $model = static::create([
            'user_id'    => $user->id,
            'name'       => $name,
            'key_hash'   => $hash,
            'key_prefix' => $prefix,
            'expires_at' => $expiresAt,
        ]);

        // Return the model AND the plain-text (caller must show it to user).
        return [$model, $plain];
    }

    /**
     * Find an API key by its plain-text value using a timing-safe comparison.
     *
     * Why hash_equals() instead of == or ===?
     * ─────────────────────────────────────────
     * A standard == comparison in PHP short-circuits on the first differing
     * character. An attacker who can measure sub-millisecond response time
     * differences can learn character-by-character which prefix of their
     * guess matches the real hash — a classical timing side-channel attack.
     *
     * hash_equals() always compares the FULL string in constant time,
     * regardless of where the strings first differ. Response time reveals
     * nothing about partial matches.
     *
     * Note: We first hash the incoming key with SHA-256, then call
     * hash_equals() to compare two hashes. The hash lookup is done via
     * a DB index on key_hash (exact match) — no timing side-channel
     * possible at the DB level. The hash_equals() call is a defence-in-depth
     * safeguard for the in-memory comparison step.
     */
    public static function findByPlainKey(string $plain): ?static
    {
        $incomingHash = hash('sha256', $plain);

        // Fast O(1) indexed lookup — no full-table scan.
        $key = static::where('key_hash', $incomingHash)->first();

        if (! $key) {
            return null;
        }

        // Timing-safe final verification (defence in depth).
        if (! hash_equals($key->key_hash, $incomingHash)) {
            return null;
        }

        // Reject expired keys.
        if ($key->expires_at && $key->expires_at->isPast()) {
            return null;
        }

        // Update last_used_at asynchronously to avoid adding DB write latency
        // to every API request. Use updateQuietly() to skip model events.
        $key->updateQuietly(['last_used_at' => now()]);

        return $key;
    }

    /**
     * Whether this key is currently valid (not expired).
     */
    public function isValid(): bool
    {
        return ! $this->expires_at || $this->expires_at->isFuture();
    }
}
