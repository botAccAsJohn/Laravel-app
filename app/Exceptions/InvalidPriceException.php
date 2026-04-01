<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvalidPriceException extends Exception
{
    public function __construct(
        private float  $price,
        private string $reason = 'Price must be greater than zero.',
    ) {
        parent::__construct(
            "Invalid price [{$price}]: {$reason}",
            422
        );
    }

    // Returning false from report() tells Laravel: skip the log entirely
    public function report(): bool
    {
        // Prices come from user input — not worth logging as a warning every time
        // Return false = do not log this exception
        return false;
    }

    public function render(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status'  => false,
                'error'   => 'invalid_price',
                'message' => $this->reason,
                'given'   => $this->price,
            ], 422);
        }

        return redirect()
            ->back()
            ->with('error', "Invalid price ₹{$this->price}: {$this->reason}")
            ->withInput();
    }
}
