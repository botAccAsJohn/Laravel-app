<?php

namespace App\Jobs\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentService;

/**
 * Step 1 of the post-checkout chain.
 * Charges the customer via PaymentService.
 * If this fails, the whole chain stops — stock is never reserved,
 * no invoice is generated, and no confirmation email is sent.
 */
class ChargePayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10; // seconds between retries

    public function __construct(public readonly Order $order) {}

    public function handle(PaymentService $payment): void
    {
        Log::channel('orders')->info("Chain Step 1 — Charging payment for Order #{$this->order->order_number}");

        // Simulate payment charge (replace with real gateway call)
        $result = $payment->charge((int) $this->order->final_amount);

        // Mark order as paid
        $this->order->update([
            'status'     => 'confirmed',
            'payment_ref' => $result,
        ]);

        Log::channel('orders')->info("Chain Step 1 ✓ — Payment confirmed for Order #{$this->order->order_number}. Ref: {$result}");
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('orders')->error("Chain Step 1 ✗ — Payment FAILED for Order #{$this->order->order_number}: {$e->getMessage()}");

        // Roll back: revert order to pending so it can be retried
        $this->order->update(['status' => 'pending']);
    }
}
