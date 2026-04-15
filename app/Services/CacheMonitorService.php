<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CacheMonitorService
{
    /**
     * The tracked application cache keys and their friendly labels.
     * These are the keys we actively manage in the app.
     */
    private array $knownKeys = [
        'products:all'     => 'All Products List',
        'products:count'   => 'Products Count',
        'categories:all'   => 'All Categories',
    ];

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Full stats snapshot for the monitor page.
     */
    public function stats(): array
    {
        $redisInfo = $this->redisInfo();
        $hitRate   = $this->hitRate($redisInfo);
        $items     = $this->cachedItems();

        return [
            'hit_rate'       => $hitRate,
            'cached_items'   => $items,
            'redis_info'     => $redisInfo,
            'total_keys'     => $redisInfo['keys'] ?? '—',
            'memory_used'    => $redisInfo['memory_used'] ?? '—',
            'uptime_days'    => $redisInfo['uptime_days'] ?? '—',
            'connected_clients' => $redisInfo['connected_clients'] ?? '—',
        ];
    }

    /**
     * Calculate cache hit rate from Redis server's own keyspace stats.
     * These are always accurate — Redis tracks every GET hit/miss natively.
     */
    public function hitRate(array $redisInfo = []): array
    {
        try {
            if (empty($redisInfo)) {
                $redisInfo = $this->redisInfo();
            }

            $hits   = (int) ($redisInfo['hits'] ?? 0);
            $misses = (int) ($redisInfo['misses'] ?? 0);
            $total  = $hits + $misses;
            $rate   = $total > 0 ? round(($hits / $total) * 100, 1) : 0;

            return [
                'hits'   => $hits,
                'misses' => $misses,
                'rate'   => $rate,
                'total'  => $total,
            ];
        } catch (\Exception $e) {
            return [
                'hits' => 0,
                'misses' => 0,
                'rate' => 0,
                'total' => 0,
            ];
        }
    }

    /**
     * Inspect all known application cache keys and report their status.
     */
    public function cachedItems(): array
    {
        $items = [];
        $cacheRedis  = Redis::connection('cache');
        $cachePrefix = config('cache.prefix');

        foreach ($this->knownKeys as $key => $label) {
            $fullRedisKey = $cacheKey = $cachePrefix . $key;
            $exists = (bool) $cacheRedis->exists($fullRedisKey);

            $size  = 0;
            $count = null;
            $ttl   = null;

            if ($exists) {
                // Size from raw Redis (no PHP deserialization cost)
                $size = (int) $cacheRedis->strlen($fullRedisKey);
                $ttl  = $this->getKeyTtl($key);

                // Count elements — pull from cache only for counting
                $value = Cache::get($key);
                if ($value instanceof \Illuminate\Support\Collection) {
                    $count = $value->count();
                } elseif (is_array($value)) {
                    $count = count($value);
                } elseif (is_numeric($value)) {
                    $count = (int) $value;
                }
            }

            $items[] = [
                'key'    => $key,
                'label'  => $label,
                'exists' => $exists,
                'size'   => $this->formatBytes($size),
                'count'  => $count,
                'ttl'    => $ttl,
            ];
        }

        // Also scan for dynamic keys (per-product caches, per-user carts)
        $items = array_merge($items, $this->scanDynamicKeys());

        return $items;
    }

    /**
     * Scan Redis for dynamic per-slug product cache keys and per-user cart/viewed keys.
     */
    private function scanDynamicKeys(): array
    {
        $items = [];

        try {
            $cacheRedis = Redis::connection('cache');
            $cachePrefix = config('cache.prefix');

            $patterns = [
                $cachePrefix . 'products:single:*' => ['strip' => 'products:single:', 'labelFn' => fn($s) => "Product: {$s}"],
                $cachePrefix . 'product:*'         => ['strip' => null, 'labelFn' => fn($s) => 'Filtered Product Page'],
                $cachePrefix . 'cart:user:*'       => ['strip' => null, 'labelFn' => fn($s) => 'Cart (User #' . preg_replace('/.*:(\d+)$/', '$1', $s) . ')'],
                $cachePrefix . 'viewed:user:*'     => ['strip' => null, 'labelFn' => fn($s) => 'Recently Viewed (User #' . preg_replace('/.*:(\d+)$/', '$1', $s) . ')'],
            ];

            foreach ($patterns as $pattern => $opts) {
                $cursor = '0';
                $found  = [];

                do {
                    [$cursor, $keys] = $cacheRedis->scan($cursor, 'MATCH', $pattern, 'COUNT', 100);
                    $found = array_merge($found, $keys ?? []);
                } while ($cursor !== '0');

                foreach ($found as $fullKey) {
                    $cleanKey = str_replace($cachePrefix, '', $fullKey);
                    $label    = ($opts['labelFn'])($cleanKey);
                    $ttl      = $cacheRedis->ttl($fullKey);

                    $items[] = [
                        'key'    => $cleanKey,
                        'label'  => $label,
                        'exists' => true,
                        'size'   => $this->formatBytes((int) $cacheRedis->strlen($fullKey)),
                        'count'  => null,
                        'ttl'    => $ttl > 0 ? $ttl : null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Redis might not support SCAN in some configs — silently skip
        }

        return $items;
    }

    /**
     * Fetch useful Redis INFO stats.
     */
    private function redisInfo(): array
    {
        try {
            $raw = Redis::info();
            $info = [];

            // Flatten the array if it's nested (common with phpredis)
            foreach ($raw as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        $info[$subKey] = $subValue;
                    }
                    // Keep the keyspace section for special handling
                    if ($key === 'Keyspace') {
                        $info['__keyspace'] = $value;
                    }
                } else {
                    $info[$key] = $value;
                }
            }

            // Calculate total keys across all DBs
            $totalKeys = 0;
            if (isset($info['__keyspace'])) {
                foreach ($info['__keyspace'] as $dbStats) {
                    if (is_array($dbStats) && isset($dbStats['keys'])) {
                        $totalKeys += (int) $dbStats['keys'];
                    } elseif (is_string($dbStats) && preg_match('/keys=(\d+)/', $dbStats, $matches)) {
                        $totalKeys += (int) $matches[1];
                    }
                }
            } else {
                // Fallback for flat structure
                foreach ($info as $key => $value) {
                    if (str_starts_with($key, 'db') && preg_match('/keys=(\d+)/', (string)$value, $matches)) {
                        $totalKeys += (int) $matches[1];
                    }
                }
            }

            return [
                'memory_used'       => $this->formatBytes((int) ($info['used_memory'] ?? 0)),
                'memory_peak'       => $this->formatBytes((int) ($info['used_memory_peak'] ?? 0)),
                'keys'              => $totalKeys ?: '—',
                'uptime_days'       => $info['uptime_in_days'] ?? '—',
                'connected_clients' => $info['connected_clients'] ?? '—',
                'hits'              => $info['keyspace_hits'] ?? 0,
                'misses'            => $info['keyspace_misses'] ?? 0,
                'redis_version'     => $info['redis_version'] ?? '—',
                'role'              => $info['role'] ?? '—',
                'evicted_keys'      => $info['evicted_keys'] ?? 0,
                'expired_keys'      => $info['expired_keys'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error("Redis Info Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get TTL for a cache key via Redis.
     */
    private function getKeyTtl(string $key): ?int
    {
        try {
            $prefix  = config('cache.prefix');
            $fullKey = $prefix . $key;
            $ttl     = Redis::connection('cache')->ttl($fullKey);
            return $ttl > 0 ? $ttl : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clear all application-level cache keys (not the entire Redis).
     */
    public function clearAll(): array
    {
        $cleared = [];

        try {
            // Collect what we're about to clear for the report
            foreach (array_keys($this->knownKeys) as $key) {
                if (Cache::has($key)) {
                    $cleared[] = $key;
                }
            }

            // Flush the entire Redis cache store.
            // This correctly handles all prefix logic internally and ONLY clears
            // the cache store — sessions live on a separate Redis connection ('default')
            // and queues live in the database, so neither is affected.
            Cache::store('redis')->getStore()->flush();

            $cleared[] = '+ all dynamic product/cart/viewed page caches';
        } catch (\Exception $e) {
            Log::error('Cache clear failed: ' . $e->getMessage());
        }

        return $cleared;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i     = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
