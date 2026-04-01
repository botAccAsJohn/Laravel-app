<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;


class InsufficientPermissionException extends Exception
{
    public function __construct(
        private string $requiredPermission,
        private string $action = '',
    ) {
        parent::__construct(
            "Permission \"{$requiredPermission}\" required to perform: {$action}.",
            403
        );
    }

    public function report(): void
    {
        // Log suspicious permission violations with user context
        Log::warning('Permission denied', [
            'required_permission' => $this->requiredPermission,
            'action'              => $this->action,
            'user_id'             => Auth::id() ?? 'guest',
            'ip'                  => request()->ip(),
            'url'                 => request()->fullUrl(),
        ]);
    }

    public function render(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status'  => false,
                'error'   => 'insufficient_permission',
                'message' => 'You do not have permission to perform this action.',
                'required' => $this->requiredPermission,
            ], 403);
        }

        // For web: show a 403 blade view
        abort(403, 'You do not have permission to perform this action.');
    }
}
