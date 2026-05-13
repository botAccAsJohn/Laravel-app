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
        // Hydrate full models to get images and other details
        $cartModels = $this->cartService->getCartModels($event->user->id);

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
