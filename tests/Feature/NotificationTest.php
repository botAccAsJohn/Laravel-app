<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderShipped;
use App\Notifications\OrderConfirmation;
use App\Notifications\NewOrderReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that OrderShipped notification is sent when an admin marks an order as shipped.
     */
    public function test_order_shipped_notification_is_sent_to_user_when_order_is_marked_as_shipped(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'confirmed'
        ]);

        $this->actingAs($admin)
            ->patch(route('orders.update', $order), [
                'status' => 'shipped'
            ]);

        Notification::assertSentTo(
            $user,
            OrderShipped::class,
            function ($notification, $channels) use ($order) {
                return $notification->order->id === $order->id;
            }
        );
    }

    /**
     * Test that OrderShipped notification is NOT sent when status is changed to something else.
     */
    public function test_order_shipped_notification_is_not_sent_on_non_shipped_update(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'confirmed'
        ]);

        $this->actingAs($admin)
            ->patch(route('orders.update', $order), [
                'status' => 'processing'
            ]);

        Notification::assertNotSentTo($user, OrderShipped::class);
    }


    /**
     * Test that OrderConfirmation notification is sent on demand for guest checkouts.
     */
    public function test_guest_checkout_sends_on_demand_notification(): void
    {
        Notification::fake();

        $guestEmail = 'guest@example.com';
        $order = Order::factory()->create([
            'user_id' => null, // No user
        ]);
        $order->guest_email = $guestEmail;

        $event = new \App\Events\Orders\OrderPlaced($order);
        $listener = new \App\Listeners\Orders\NotifyOrderPlacedListener();
        $listener->handle($event);

        Notification::assertSentOnDemand(OrderConfirmation::class, function ($notification, $channels, $notifiable) use ($guestEmail) {
            return $notifiable->routes['mail'] === $guestEmail;
        });
    }

    /**
     * Test via() method returns expected channels.
     */
    public function test_notification_via_channels(): void
    {
        $user = new User(['role' => 'user']);
        $order = new Order();
        $notification = new NewOrderReceived($order);

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
        $this->assertContains(\App\Notifications\Channels\WebhookChannel::class, $channels);
    }

    /**
     * Test toArray() payload content.
     */
    public function test_order_shipped_notification_payload_content(): void
    {
        $order = Order::factory()->make(['id' => 123]);
        $notification = new OrderShipped($order);

        $payload = $notification->toArray(new User());

        $this->assertEquals('Order Shipped', $payload['title']);
        $this->assertStringContainsString('123', $payload['message']);
        $this->assertEquals(123, $payload['data']['order_id']);
    }
}
