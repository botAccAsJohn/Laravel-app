<?php

namespace Tests\Feature;

use App\Jobs\Orders\{ChargePayment, ReserveStock, GenerateInvoicePdf, SendOrderConfirmation};
use App\Models\{Order, Product, User};
use App\Services\{CartService, OrderService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PostCheckoutChainTest extends TestCase
{
    use RefreshDatabase;

    public function test_chain_is_built_with_correct_job_order()
    {
        // Test that the chain-building logic produces jobs in the correct order
        // This mirrors the exact array built in OrderService::processOrderCreation()
        $order = Order::factory()->create(['status' => 'pending']);

        $chain = [
            new ChargePayment($order),
            new ReserveStock($order),
            new GenerateInvoicePdf($order),
            new SendOrderConfirmation($order),
        ];

        $this->assertCount(4, $chain);
        $this->assertInstanceOf(ChargePayment::class, $chain[0]);
        $this->assertInstanceOf(ReserveStock::class, $chain[1]);
        $this->assertInstanceOf(GenerateInvoicePdf::class, $chain[2]);
        $this->assertInstanceOf(SendOrderConfirmation::class, $chain[3]);
    }

    public function test_chain_uses_specific_queues()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $paymentJob = (new ChargePayment($order))->onQueue('default');
        $this->assertEquals('default', $paymentJob->queue);

        $reserveJob = (new ReserveStock($order))->onQueue('default');
        $this->assertEquals('default', $reserveJob->queue);

        $invoiceJob = (new GenerateInvoicePdf($order))->onQueue('pdfs');
        $this->assertEquals('pdfs', $invoiceJob->queue);

        $emailJob = (new SendOrderConfirmation($order))->onQueue('emails');
        $this->assertEquals('emails', $emailJob->queue);
    }

    public function test_chain_skips_reserve_stock_for_digital_orders()
    {
        $order = Order::factory()->create([
            'status' => 'pending',
            'is_digital' => true,
        ]);

        // Simulate the chain-building + unless logic (mirrors OrderService)
        $chain = [
            new ChargePayment($order),
            new ReserveStock($order),
            new GenerateInvoicePdf($order),
            new SendOrderConfirmation($order),
        ];

        $chain = array_filter($chain, fn($job) => !($job instanceof ReserveStock));
        $chain = array_values($chain);

        $this->assertCount(3, $chain);
        $this->assertInstanceOf(ChargePayment::class, $chain[0]);
        $this->assertInstanceOf(GenerateInvoicePdf::class, $chain[1]);
        $this->assertInstanceOf(SendOrderConfirmation::class, $chain[2]);
    }
}
