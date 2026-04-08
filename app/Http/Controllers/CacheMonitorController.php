<?php

namespace App\Http\Controllers;

use App\Services\CacheMonitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CacheMonitorController extends Controller
{
    public function __construct(private CacheMonitorService $monitor) {}

    /**
     * Display the cache performance monitor dashboard.
     */
    public function index(): View
    {
        $stats = $this->monitor->stats();

        return view('admin.cache-monitor', compact('stats'));
    }

    /**
     * Clear all application-level cache keys.
     */
    public function clear(): RedirectResponse
    {
        $cleared = $this->monitor->clearAll();

        $count = count($cleared);

        return redirect()
            ->route('admin.cache.index')
            ->with('success', "Cache cleared! {$count} key(s) removed.");
    }
}
