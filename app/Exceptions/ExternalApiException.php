<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ExternalApiException extends Exception
{
    public function render(Request $request): JsonResponse|Response
    {
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
            ], 500);
        }

        return response()->view('errors.external-api', [
            'message' => $this->getMessage(),
        ], 500);
    }
}
