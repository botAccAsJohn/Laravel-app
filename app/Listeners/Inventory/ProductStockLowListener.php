<?php

namespace App\Listeners\Inventory;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Events\Inventory\ProductStockLow;
use App\Mail\ProductStockLowMail;
use Illuminate\Support\Facades\Mail;

class ProductStockLowListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        // Send email to admin
        Mail::to(env('ADMIN_EMAIL'))->send(new ProductStockLowMail($event->product));
    }
}
