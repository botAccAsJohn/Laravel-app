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
        $hitRate   = $this->hitRate();
        $items     = $this->cachedItems();
        $redisInfo = $this->redisInfo();

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
     * Calculate cache hit rate from today's Redis cache event metrics.
     */
    public function hitRate(): array
    {
        try {
            $date = date('Y-m-d');
            $hits = (int) Redis::get("cache_metrics:hits:{$date}");
            $misses = (int) Redis::get("cache_metrics:misses:{$date}");

            $total = $hits + $misses;
            $rate  = $total > 0 ? round(($hits / $total) * 100, 1) : 0;
            
            $recentRaw = Redis::lrange("cache_metrics:recent", 0, 9); // top 10
            $recent = [];
            foreach ($recentRaw as $raw) {
                $recent[] = json_decode($raw, true);
            }

            return [
                'hits'   => $hits,
                'misses' => $misses,
                'rate'   => $rate,
                'total'  => $total,
                'recent' => $recent,
            ];
        } catch (\Exception $e) {
            return [
                'hits' => 0, 'misses' => 0, 'rate' => 0, 'total' => 0, 'recent' => []
            ];
        }
    }

    /**
     * Inspect all known application cache keys and report their status.
     */
    public function cachedItems(): array
    {
        $items = [];

        foreach ($this->knownKeys as $key => $label) {
            $value = Cache::get($key);
            $exists = $value !== null;

            $size  = 0;
            $count = null;
            $ttl   = null;

            if ($exists) {
                $serialized = serialize($value);
                $size = strlen($serialized);

                // Count elements for collections/arrays
                if ($value instanceof \Illuminate\Support\Collection) {
                    $count = $value->count();
                } elseif (is_array($value)) {
                    $count = count($value);
                } elseif (is_numeric($value)) {
                    $count = (int) $value;
                }

                // Try to get TTL from Redis directly
                $ttl = $this->getKeyTtl($key);
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
            $prefix = config('cache.prefix');

            // Scan for product:single:* keys
            $cursor  = '0';
            $pattern = $prefix . 'products:single:*';
            $found   = [];

            do {
                [$cursor, $keys] = Redis::scan($cursor, 'MATCH', $pattern, 'COUNT', 100);
                $found = array_merge($found, $keys);
            } while ($cursor !== '0');

            foreach ($found as $fullKey) {
                $cleanKey = str_replace($prefix, '', $fullKey);
                $slug     = str_replace('products:single:', '', $cleanKey);
                $ttl      = Redis::ttl($fullKey);

                $items[] = [
                    'key'    => $cleanKey,
                    'label'  => "Product: {$slug}",
                    'exists' => true,
                    'size'   => $this->formatBytes((int) Redis::strlen($fullKey)),
                    'count'  => null,
                    'ttl'    => $ttl > 0 ? $ttl : null,
                ];
            }

            // Scan cart and viewed keys
            $dynamicPatterns = [
                $prefix . 'cart:user:*'    => 'Cart',
                $prefix . 'viewed:user:*'  => 'Recently Viewed',
            ];

            foreach ($dynamicPatterns as $pat => $labelPrefix) {
                $cursor = '0';
                $found  = [];

                do {
                    [$cursor, $keys] = Redis::scan($cursor, 'MATCH', $pat, 'COUNT', 100);
                    $found = array_merge($found, $keys);
                } while ($cursor !== '0');

                foreach ($found as $fullKey) {
                    $cleanKey = str_replace($prefix, '', $fullKey);
                    $userId   = preg_replace('/.*:(\d+)$/', '$1', $cleanKey);
                    $ttl      = Redis::ttl($fullKey);

                    $items[] = [
                        'key'    => $cleanKey,
                        'label'  => "{$labelPrefix} (User #{$userId})",
                        'exists' => true,
                        'size'   => $this->formatBytes((int) Redis::strlen($fullKey)),
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
            $ttl     = Redis::ttl($fullKey);
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

        foreach (array_keys($this->knownKeys) as $key) {
            if (Cache::forget($key)) {
                $cleared[] = $key;
            }
        }

        // Also flush dynamic keys via Redis SCAN
        try {
            $prefix   = config('cache.prefix');
            $patterns = [
                $prefix . 'products:single:*',
                $prefix . 'cart:user:*',
                $prefix . 'viewed:user:*',
            ];

            foreach ($patterns as $pattern) {
                $cursor = '0';
                do {
                    [$cursor, $keys] = Redis::scan($cursor, 'MATCH', $pattern, 'COUNT', 100);
                    if (!empty($keys)) {
                        Redis::del(...$keys);
                        $cleared = array_merge($cleared, array_map(fn($k) => str_replace($prefix, '', $k), $keys));
                    }
                } while ($cursor !== '0');
            }
        } catch (\Exception $e) {
            // Silently skip on SCAN errors
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
