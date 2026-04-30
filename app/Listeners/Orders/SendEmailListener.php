<?php

namespace App\Listeners\Orders;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Mail\Orders\{SendOrderPlacedMail, SendOrderConfirmationMail, SendOrderDeliveredMail, SendOrderShippedMail, SendOrderPaidMail};
use Illuminate\Support\Facades\Mail;
use App\Events\Orders\{OrderPlaced, OrderConfirmation, OrderDelivered, OrderShipped, OrderPaid};
use Illuminate\Support\Facades\Log;

class SendEmailListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(object $event): void
    {
        if ($event instanceof OrderPlaced) {
            $orderService = new \App\Services\OrderService(new \App\Services\CartService());
            $invoicePath = $orderService->generateInvoiceAndReturnPath($event->order);
            Mail::to(env('ADMIN_EMAIL'))->send(new SendOrderPlacedMail($event->order, $invoicePath)); //to : $event->order->user->email
        } elseif ($event instanceof OrderConfirmation) {
            Mail::to(env('ADMIN_EMAIL'))->send(new SendOrderConfirmationMail($event->order)); //to : $event->order->user->email
        } elseif ($event instanceof OrderDelivered) {
            Mail::to(env('ADMIN_EMAIL'))->send(new SendOrderDeliveredMail($event->order)); //to : $event->order->user->email
        } elseif ($event instanceof OrderShipped) {
            Mail::to(env('ADMIN_EMAIL'))->send(new SendOrderShippedMail($event->order)); //to : $event->order->user->email
        } elseif ($event instanceof OrderPaid) {
            Mail::to(env('ADMIN_EMAIL'))->send(new SendOrderPaidMail($event->order)); //to : $event->order->user->email
        }
    }

    public function failed(object $event, \Throwable $exception): void
    {
        Log::channel('orders')->error("Failed to send order placed email for Order #{$event->order->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
