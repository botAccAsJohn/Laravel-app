<?php

// namespace App\Auth;

// use App\Models\User;
// use Illuminate\Auth\EloquentUserProvider;
// use Illuminate\Contracts\Auth\Authenticatable;
// use Illuminate\Support\Facades\Cache;

// class RedisUserProvider extends EloquentUserProvider
// {
//     protected int $ttl = 3600; // Cache for 1 hour

//     public function retrieveById($identifier): ?Authenticatable
//     {
//         $cacheKey = "auth_user:{$identifier}";

//         // Step 1: Try Redis first
//         $user = Cache::tags(['users'])->remember($cacheKey, $this->ttl, function () use ($identifier) {
//             // Step 2: Not in Redis → fetch from DB
//             return parent::retrieveById($identifier);
//         });

//         return $user instanceof Authenticatable ? $user : null;
//     }

//     public function retrieveByCredentials(array $credentials): ?Authenticatable
//     {
//         // Credentials (email+password) lookup — always hit DB
//         // No point caching this as it's a one-time login action
//         return parent::retrieveByCredentials($credentials);
//     }

//     public function retrieveByToken($identifier, $token): ?Authenticatable
//     {
//         // "Remember me" token — always fresh from DB
//         return parent::retrieveByToken($identifier, $token);
//     }
// }


namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;

class RedisUserProvider extends EloquentUserProvider
{
    protected int $ttl = 3600;

    // ✅ Safe fields only — no password
    protected array $cacheFields = [
        'id',
        'name',
        'email',
        'role',
        'email_verified_at',
        'remember_token',
    ];

    // ─────────────────────────────────────────────
    // Fetch user by ID (used on every request)
    // ─────────────────────────────────────────────
    public function retrieveById($identifier): ?Authenticatable
    {
        $cacheKey = "auth_user:{$identifier}";

        // Step 1: Check Redis first
        $cached = Cache::tags(['users'])->get($cacheKey);

        if ($cached) {
            return $this->buildUserFromCache($cached);
        }

        // Step 2: Not in Redis → fetch from DB
        $user = parent::retrieveById($identifier);

        if ($user) {
            // Step 3: Store only safe fields in Redis
            Cache::tags(['users'])->put(
                $cacheKey,
                $this->extractCacheData($user),
                $this->ttl
            );
        }

        return $user;
    }

    // ─────────────────────────────────────────────
    // Login attempt — ALWAYS hits DB
    // Password comparison happens here
    // ─────────────────────────────────────────────
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        return parent::retrieveByCredentials($credentials);
    }

    // ─────────────────────────────────────────────
    // Password validation
    // If user came from Redis (no password), re-fetch from DB
    // ─────────────────────────────────────────────
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (empty($user->getAuthPassword())) {
            $freshUser = parent::retrieveById($user->getAuthIdentifier());

            if (!$freshUser) {
                return false;
            }

            return parent::validateCredentials($freshUser, $credentials);
        }

        return parent::validateCredentials($user, $credentials);
    }

    // ─────────────────────────────────────────────
    // Remember Me token — always fresh from DB
    // ─────────────────────────────────────────────
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return parent::retrieveByToken($identifier, $token);
    }

    // ─────────────────────────────────────────────
    // Helper: Extract only safe fields from model
    // ─────────────────────────────────────────────
    protected function extractCacheData(Authenticatable $user): array
    {
        return collect($this->cacheFields)
            ->mapWithKeys(fn($field) => [$field => $user->{$field}])
            ->toArray();
    }

    // ─────────────────────────────────────────────
    // Helper: Rebuild User model from cached array
    // ─────────────────────────────────────────────
    protected function buildUserFromCache(array $data): User
    {
        $user = new User();
        $user->setRawAttributes($data);
        $user->exists = true;

        return $user;
    }
}