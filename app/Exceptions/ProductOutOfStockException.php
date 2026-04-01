<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;

class ProductOutOfStockException extends Exception
{
    public function __construct(private string $productName, private int $productId, private int $requestedQty, private int $availableQty)
    {
        parent::__construct(
            "Product \"{$productName}\" (ID:{$productId}) out of stock. " .
                "Requested: {$requestedQty}, Available: {$availableQty}.",
            422
        );
    }

    // ── report() — called when exception is LOGGED ──────────────
    // Return false to skip logging entirely, or write your own log here
    public function report()
    {
        Log::warning('Stock Issue', [
            'product_id'    => $this->productId,
            'product_name'  => $this->productName,
            'requested_qty' => $this->requestedQty,
            'available_qty' => $this->availableQty,
            'timestamp'     => now()->toIso8601String(),
        ]);
    }

    // ── render() — called to build the HTTP response ────────────
    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status'  => false,
                'error'   => 'out_of_stock',
                'message' => "Sorry, \"{$this->productName}\" is out of stock.",
                'data'    => [
                    'product_id'    => $this->productId,
                    'requested_qty' => $this->requestedQty,
                    'available_qty' => $this->availableQty,
                ],
            ], 422);
        }

        return redirect()
            ->back()
            ->with(
                'error',
                "\"{$this->productName}\" is out of stock. " .
                    "Only {$this->availableQty} left, you requested {$this->requestedQty}."
            );
    }
}
