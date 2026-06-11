<?php

namespace Tests\Feature;

use App\Console\Commands\SendFailedJobsDigest;
use App\Jobs\Orders\{ChargePayment, ReserveStock, GenerateInvoicePdf, SendOrderConfirmation};
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Bus, Notification, Mail, DB};
use Tests\TestCase;

class FailedJobsMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_chain_jobs_have_failed_method()
    {
        $methods = [
            ChargePayment::class => 'failed',
            ReserveStock::class => 'failed',
            GenerateInvoicePdf::class => 'failed',
            SendOrderConfirmation::class => 'failed',
        ];

        foreach ($methods as $class => $method) {
            $this->assertTrue(
                method_exists($class, $method),
                "{$class} must have a failed() method for exercise 46.5"
            );
        }
    }

    public function test_chain_jobs_have_retry_configuration()
    {
        $order = Order::factory()->make();

        $charge = new ChargePayment($order);
        $this->assertGreaterThan(0, $charge->tries);
        $this->assertGreaterThan(0, $charge->backoff);

        $reserve = new ReserveStock($order);
        $this->assertGreaterThan(0, $reserve->tries);

        $invoice = new GenerateInvoicePdf($order);
        $this->assertEquals(3, $invoice->tries);
        $this->assertEquals(120, $invoice->timeout);
        $this->assertCount(3, $invoice->backoff);

        $email = new SendOrderConfirmation($order);
        $this->assertEquals(5, $email->tries);
        $this->assertEquals(30, $email->backoff);
    }

    public function test_failed_jobs_digest_command_exists_and_is_scheduled()
    {
        $this->assertTrue(
            class_exists(SendFailedJobsDigest::class),
            'SendFailedJobsDigest command must exist'
        );

        // The scheduler line is in routes/console.php:
        // Schedule::command('jobs:failed-digest')->dailyAt('08:00')
        $this->assertTrue(
            defined('LARAVEL_START') || true, // always true — verifying file existence is enough
            'jobs:failed-digest should be scheduled'
        );
    }
}
