<?php

namespace App\Listeners;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Support\Facades\Log;

class CacheEventListener
{
    public function __construct() {}

    public function handleMiss(CacheMissed $event)
    {
        Log::channel("RedisInteraction")->alert("Cache Miss Happened . " . $event->key);
    }
    public function handleHit(CacheHit $event)
    {
        Log::channel("RedisInteraction")->alert("Cache Hit Happened . " . $event->key);
    }
}
