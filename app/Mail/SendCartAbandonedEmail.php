<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendCartAbandonedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public object|array $cart,
        public $user,
        public float $cartTotal,
        public int $itemCount,
        public $cartModels = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You left something in your cart! 🛒',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.cart-abandoned',
            with: [
                'userName' => $this->user->name,
                'cartItems' => $this->cart,
                'cartTotal' => $this->cartTotal,
                'itemCount' => $this->itemCount,
                'cartModels' => $this->cartModels,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
