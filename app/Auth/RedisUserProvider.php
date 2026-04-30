<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;

class RedisUserProvider extends EloquentUserProvider
{
    protected int $ttl = 3600;
    protected array $cacheFields = [
        'id',
        'name',
        'email',
        'role',
        'preferred_locale',
        'email_verified_at',
        'remember_token',
    ];

    // Fetch user by ID (used on every request)
    public function retrieveById($identifier): ?Authenticatable
    {
        $cacheKey = "auth_user:{$identifier}";

        $cached = Cache::tags(['users'])->get($cacheKey);

        if ($cached) {
            return $this->buildUserFromCache($cached);
        }

        $user = parent::retrieveById($identifier);

        if ($user) {
            Cache::tags(['users'])->put(
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

    // Password validation
    // If user came from Redis (no password), re-fetch from DB
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

    // Remember Me token — always fresh from DB
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return parent::retrieveByToken($identifier, $token);
    }

    // Helper: Extract only safe fields from model
    protected function extractCacheData(Authenticatable $user): array
    {
        return collect($this->cacheFields)
            ->mapWithKeys(fn($field) => [$field => $user->{$field}])
            ->toArray();
    }

    // Helper: Rebuild User model from cached array
    protected function buildUserFromCache(array $data): User
    {
        $user = new User();
        $user->setRawAttributes($data);
        $user->exists = true;

        return $user;
    }
}