<?php

namespace App\Jobs\Orders;

use App\Mail\Orders\SendOrderConfirmationMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Log, Mail};

class SendOrderConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 30; // Give mail server a moment before retry

    public function __construct(public readonly Order $order)
    {
    }

    public function handle(): void
    {
        Log::channel('orders')->info("Chain Step 4 — Sending confirmation email for Order #{$this->order->order_number}");

        $this->order->loadMissing('user');

        if (!$this->order->user?->email) {
            Log::channel('orders')->warning("Chain Step 4 — No email on user, skipping for Order #{$this->order->order_number}");
            return;
        }

        Mail::to($this->order->user->email)
            ->send(new SendOrderConfirmationMail($this->order));

        Log::channel('orders')->info("Chain Step 4 ✓ — Confirmation email sent for Order #{$this->order->order_number}");
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('orders')->error("Chain Step 4 ✗ — Confirmation email FAILED for Order #{$this->order->order_number}: {$e->getMessage()}");
        // Email failure is non-fatal — order is already confirmed and stock reserved.
    }
}
