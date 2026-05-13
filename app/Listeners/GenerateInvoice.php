<?php

namespace App\Listeners;

use App\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class GenerateInvoice implements ShouldQueue
{
    /**
     * Create the event listener.
     * OrderService is injected by the container — NOT the Order model.
     */
    public function __construct(private OrderService $orderService) {}

    /**
     * Handle the event.
     * The order comes from the event, not from the constructor.
     */
    public function handle(object $event): void
    {
        $order = $event->order;

        $path = $this->orderService->generateInvoiceAndReturnPath($order);

        if ($path) {
            $order->update(['invoice_path' => $path]);
            Log::channel('orders')->info("Invoice generated for Order #{$order->id}", [
                'path' => $path,
            ]);
        }
    }
}
