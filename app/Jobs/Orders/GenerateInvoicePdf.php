<?php

namespace App\Jobs\Orders;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class GenerateInvoicePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [10, 30, 60];

    public function __construct(public readonly Order $order)
    {
    }

    public function handle(OrderService $service): void
    {
        Log::channel('orders')->info("Chain Step 3 — Generating invoice PDF for Order #{$this->order->order_number}");

        $path = $service->generateInvoiceAndReturnPath($this->order);

        if (!$path) {
            // generateInvoiceAndReturnPath already logs the error internally via rescue().
            // We throw here so the chain registers this as a failure.
            throw new \RuntimeException("Invoice generation returned null for Order #{$this->order->order_number}");
        }

        // Store the invoice path on the order for future download links
        $this->order->update(['invoice_path' => $path]);

        Log::channel('orders')->info("Chain Step 3 ✓ — Invoice saved at {$path} for Order #{$this->order->order_number}");
    }

    public function failed(\Throwable $e): void
    {
        Log::channel('orders')->error(
            "GenerateInvoicePdf FAILED for Order #{$this->order->order_number}: {$e->getMessage()}"
        );

        // Notify admin via Slack — non-critical but admin should know to regenerate manually
        try {
            \Illuminate\Support\Facades\Notification::route(
                'slack',
                config('services.slack.channels.bot_testing', '#bot-testing')
            )->notify(new \App\Notifications\FailedJobAlert([
                            'job' => self::class,
                            'queue' => $this->queue ?? 'default',
                            'error' => "Invoice generation failed for Order #{$this->order->order_number}: {$e->getMessage()}",
                            'failed_at' => now()->toDateTimeString(),
                        ]));
        } catch (\Throwable $notifyEx) {
            Log::error("GenerateInvoicePdf: Slack alert failed — {$notifyEx->getMessage()}");
        }


    }
}
