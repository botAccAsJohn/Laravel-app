<?php

namespace Tests\Feature;

use App\Jobs\Orders\GenerateInvoicePdf;
use App\Models\{Order, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Queue, Storage};
use Tests\TestCase;

class GenerateInvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatchSync_generates_invoice_and_updates_order()
    {
        // 1. Arrange: create a user and order
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-001',
            'status' => 'pending',
            'total_amount' => 100,
            'final_amount' => 100,
        ]);

        Storage::fake('local');

        // 2. Act: dispatchSync bypasses the queue — runs immediately
        GenerateInvoicePdf::dispatchSync($order);

        // 3. Assert: invoice_path was saved on the order
        $order->refresh();
        $this->assertNotNull($order->invoice_path, 'invoice_path should be set after dispatchSync');
        $this->assertStringContainsString('invoices/invoice-ORD-TEST-001', $order->invoice_path);

        // 4. Assert: the PDF file exists on the fake disk
        Storage::disk('local')->assertExists($order->invoice_path);
    }

    public function test_job_has_correct_retry_configuration()
    {
        $job = new GenerateInvoicePdf(new Order());
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->timeout);
        $this->assertEquals([10, 30, 60], $job->backoff);
    }
}
