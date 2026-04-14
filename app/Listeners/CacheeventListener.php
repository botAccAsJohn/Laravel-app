<?php

namespace App\Listeners;

use Cache;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;

class CacheeventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handleMiss(CacheMissed $event)
    {
        Log::channel("RedisInteraction")->alert("Cache Miss Happened . " . $event->key);
    }
    public function handleHit(CacheHit $event)
    {
        Log::channel("RedisInteraction")->alert("Cache Hit Happened . " . $event->key);
    }
}
