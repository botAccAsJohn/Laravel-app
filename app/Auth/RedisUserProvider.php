<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;

class RedisUserProvider extends EloquentUserProvider
{
    protected string $modelClass;
    protected string $cachePrefix;
    protected string $cacheTag;
    protected int $ttl = 3600;

    /**
     * Fields cached when no model-specific list is provided.
     * NOTE: `role` is intentionally absent — it is User-specific and
     * does not exist on the Admin model (or any future model).
     * Models should declare getAuthCacheFields() to opt in to a precise list.
     */
    protected array $cacheFields = [
        'id',
        'name',
        'email',
        'preferred_locale',
        'email_verified_at',
        'remember_token',
    ];

    public function __construct(\Illuminate\Contracts\Hashing\Hasher $hasher, $model)
    {
        parent::__construct($hasher, $model);
        $this->modelClass = $model;
        $classBase = class_basename($model);
        $this->cachePrefix = 'auth_' . strtolower($classBase);
        $this->cacheTag = strtolower($classBase) . 's';
    }

    // Fetch user by ID (used on every request)
    public function retrieveById($identifier): ?Authenticatable
    {
        $cacheKey = "{$this->cachePrefix}:{$identifier}";

        $cached = Cache::tags([$this->cacheTag])->get($cacheKey);

        if ($cached) {
            return $this->buildUserFromCache($cached);
        }

        $user = parent::retrieveById($identifier);

        if ($user) {
            Cache::tags([$this->cacheTag])->put(
                $cacheKey,
                $this->extractCacheData($user),
                $this->ttl
            );
        }

        return $user;
    }

    // Login attempt — ALWAYS hits DB
    // Password comparison happens here
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        return parent::retrieveByCredentials($credentials);
    }

    /**
     * Password validation.
     *
     * Exercise 53.2 — Transparent rehashing (bcrypt → argon2id migration)
     * ───────────────────────────────────────────────────────────────────
     * Why Hash::check() not ==
     * ─────────────────────────
     * Hash::check() delegates to PHP's password_verify(), which uses a
     * CONSTANT-TIME comparison to prevent timing side-channel attacks.
     *
     * With a normal string comparison ($a == $b or strcmp), PHP returns as
     * soon as the first differing byte is found. An attacker who can measure
     * HTTP response latency with microsecond precision can learn HOW CLOSE
     * their guess is to the real hash (the longer the match, the longer the
     * response). Constant-time comparison always examines every byte,
     * so response time reveals NOTHING about partial matches.
     *
     * Migration strategy: bcrypt → argon2id
     * ──────────────────────────────────────
     * 1. Set HASH_DRIVER=argon2id in .env (or config/hashing.php).
     * 2. DO NOT run a bulk password update — plain-text passwords are not
     *    stored anywhere, so you cannot re-hash without user interaction.
     * 3. On every SUCCESSFUL login:
     *    a. Hash::check($plain, $stored_hash) verifies the old bcrypt hash.
     *    b. Hash::needsRehash($stored_hash) returns TRUE because the stored
     *       hash has a different algorithm identifier (\$2y\$ vs \$argon2id\$)
     *       or different parameters.
     *    c. We call Hash::make($plain) to create a new argon2id hash and
     *       silently save it to the DB. The user never sees this.
     * 4. Over time, as users log in, their hashes are transparently upgraded.
     *    Users who never log in keep their bcrypt hashes — still safe, just
     *    not upgraded. A forced-reset policy (Exercise 52.3) can be used to
     *    flush remaining bcrypt users if required by compliance.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        // If user came from Redis (no password cached), re-fetch fresh from DB.
        if (empty($user->getAuthPassword())) {
            $freshUser = parent::retrieveById($user->getAuthIdentifier());

            if (!$freshUser) {
                return false;
            }
            $valid = parent::validateCredentials($freshUser, $credentials);
        } else {
            $valid = parent::validateCredentials($user, $credentials);
        }

        // ── Exercise 53.2: Transparent rehash on successful login ────────────
        // Only rehash if credentials are VALID. We never touch passwords on
        // failed logins (that would open a denial-of-service vector).
        if ($valid && $this->hasher->needsRehash($user->getAuthPassword())) {
            // Re-fetch the plain password from the submitted credentials.
            $plain   = $credentials['password'];
            $newHash = $this->hasher->make($plain);

            // Persist the new hash (silently, in the background of the request).
            $userModel = $this->modelClass;
            $userModel::where('id', $user->getAuthIdentifier())
                ->update(['password' => $newHash]);

            // Bust the Redis cache so the next request sees the new hash.
            $cacheKey = "{$this->cachePrefix}:{$user->getAuthIdentifier()}";
            \Illuminate\Support\Facades\Cache::tags([$this->cacheTag])->forget($cacheKey);

            \Illuminate\Support\Facades\Log::channel('security')->info(
                '[Hash] Password transparently rehashed on login',
                [
                    'user_id'  => $user->getAuthIdentifier(),
                    'old_algo' => $this->detectAlgo($user->getAuthPassword()),
                    'new_algo' => config('hashing.driver'),
                ]
            );
        }

        return $valid;
    }

    /**
     * Detect the algorithm used in a stored hash (for logging only).
     */
    private function detectAlgo(string $hash): string
    {
        return match(true) {
            str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2b$') => 'bcrypt',
            str_starts_with($hash, '$argon2id$') => 'argon2id',
            str_starts_with($hash, '$argon2i$')  => 'argon2i',
            default => 'unknown',
        };
    }

    // Remember Me token — always fresh from DB
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return parent::retrieveByToken($identifier, $token);
    }

    // Helper: Extract only safe fields from model
    // If the model declares getAuthCacheFields(), use that list.
    // This keeps the provider generic and lets each model own its schema.
    protected function extractCacheData(Authenticatable $user): array
    {
        $fields = method_exists($user, 'getAuthCacheFields')
            ? $user->getAuthCacheFields()
            : $this->cacheFields;

        return collect($fields)
            ->mapWithKeys(fn($field) => [$field => $user->{$field}])
            ->toArray();
    }

    // Helper: Rebuild Model from cached array
    protected function buildUserFromCache(array $data): Authenticatable
    {
        $class = $this->modelClass;
        $model = new $class();
        $model->setRawAttributes($data);
        $model->exists = true;

        return $model;
    }
}
