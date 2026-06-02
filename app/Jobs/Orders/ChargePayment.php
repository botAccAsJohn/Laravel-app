<?php

namespace App\Jobs\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class ChargePayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10; // seconds between retries

    public function __construct(public readonly Order $order)
    {
    }

    public function handle(): void
    {
        Log::channel('orders')->info("Chain Step 1 — Charging payment for Order #{$this->order->order_number}");
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('orders')->error("Chain Step 1 ✗ — Payment FAILED for Order #{$this->order->order_number}: {$e->getMessage()}");

        // Roll back: revert order to pending so it can be retried
        $this->order->update(['status' => 'pending']);
    }
}
