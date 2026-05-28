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
