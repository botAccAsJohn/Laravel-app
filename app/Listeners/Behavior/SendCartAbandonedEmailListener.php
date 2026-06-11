<?php

namespace App\Listeners\Behavior;

use App\Mail\SendCartAbandonedEmail;
use App\Services\CartService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendCartAbandonedEmailListener implements ShouldQueue
{
    public function __construct(
        protected CartService $cartService
    ) {}
    public $queue = 'emails';
    public $tries = 3;
    public $backoff = [10, 30, 60];
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        // Abandoned-cart emails are always for authenticated users — build their key directly
        $cartKey    = 'cart:user:' . $event->user->id;
        $cartModels = $this->cartService->getCartModels($cartKey);

        Mail::to($event->user->email)->send(
            new SendCartAbandonedEmail(
                $event->cart,
                $event->user,
                $event->cartTotal,
                $event->itemCount,
                $cartModels
            )
        );
    }
}
