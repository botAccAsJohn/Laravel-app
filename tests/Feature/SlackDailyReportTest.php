<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Carbon;
use App\Notifications\DailyDigest;
use Illuminate\Support\Facades\DB;

class SlackDailyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_daily_digest_to_slack_correctly()
    {
        Notification::fake();

        // 1. Setup Yesterday's Data
        $yesterday = Carbon::yesterday();
        
        // Yesterday's Orders
        Order::factory()->count(5)->create([
            'placed_at' => $yesterday,
            'total_amount' => 100.00
        ]);

        // Today's Orders (should NOT be counted)
        Order::factory()->count(2)->create([
            'placed_at' => now(),
            'total_amount' => 50.00
        ]);

        // New Customers Yesterday
        User::factory()->count(3)->create([
            'created_at' => $yesterday
        ]);

        // Low Stock Products
        Product::factory()->count(2)->create([
            'quantity' => 5
        ]);
        Product::factory()->count(3)->create([
            'quantity' => 50
        ]);

        // 2. Run Command
        $this->artisan('slack:daily-digest')
             ->expectsOutput("Collecting metrics for: " . $yesterday->toDateString())
             ->assertSuccessful();

        // 3. Assert Notification Sent
        Notification::assertSentOnDemand(DailyDigest::class, function ($notification, $channels, $notifiable) {
            $data = $notification->data;
            
            return $notifiable->routes['slack'] === '#leadership' &&
                   $data['orders_count'] === 5 &&
                   $data['revenue'] === 500.0 &&
                   $data['new_customers'] === 3 &&
                   $data['low_stock_count'] === 2;
        });
    }

    public function test_it_sends_to_bot_testing_when_preview_option_is_used()
    {
        Notification::fake();

        $this->artisan('slack:daily-digest --preview')
             ->assertSuccessful();

        Notification::assertSentOnDemand(DailyDigest::class, function ($notification, $channels, $notifiable) {
            return $notifiable->routes['slack'] === '#bot-testing';
        });
    }
}
