<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LifecycleController extends Controller
{
    public function index(Request $request)
    {
        // [STEP 8] Log that the controller method has been reached
        Log::info('[STEP 8] Controller index() reached', [
            'controller' => __CLASS__,
            'method' => __FUNCTION__,
            'time_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2),
        ]);
        return response()->json([
            'message' => 'Lifecycle traced successfully!',
            'step' => 8,
        ]);
    }
}
