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

        if ($valid && $this->hasher->needsRehash($user->getAuthPassword())) {
            // Re-fetch the plain password from the submitted credentials.
            $plain = $credentials['password'];
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
                    'user_id' => $user->getAuthIdentifier(),
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
        return match (true) {
            str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2b$') => 'bcrypt',
            str_starts_with($hash, '$argon2id$') => 'argon2id',
            str_starts_with($hash, '$argon2i$') => 'argon2i',
            default => 'unknown',
        };
    }

    // Remember Me token — always fresh from DB
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return parent::retrieveByToken($identifier, $token);
    }

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
