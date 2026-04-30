<?php

namespace App\Events\Behavior;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductReviewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public \App\Models\Review $review;

    public function __construct(\App\Models\Review $review)
    {
        $this->review = $review;
    }
}
