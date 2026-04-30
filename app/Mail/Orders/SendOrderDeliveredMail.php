<?php

namespace App\Mail\Orders;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOrderDeliveredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $customerName;
    public $orderTotal;
    public $itemsCount;
    public $orderId;

    /**
     * Create a new message instance.
     */
    public function __construct($order = null)
    {
        if ($order) {
            $this->customerName = $order->user->name ?? 'Customer';
            $this->orderTotal  = $order->total_amount ?? 0;
            $this->itemsCount  = $order->items ? $order->items->count() : 0;
            $this->orderId     = $order->id ?? 'Unknown';
        } else {
            // Dummy data for testing the UI
            $this->customerName = 'John Doe';
            $this->orderTotal  = 249.50;
            $this->itemsCount  = 3;
            $this->orderId     = 'DEL-98452';
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('send@updates.vinayxdev.me', 'vinay'),
            subject: 'Your Order Has Been Delivered',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.order-delivered',
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
