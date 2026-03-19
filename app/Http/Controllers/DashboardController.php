<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        Log::info('Dashboard accessed', [
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'time'       => now()->toDateTimeString(),
        ]);
        return "Dashboard Controller Invoked";
    }
}
