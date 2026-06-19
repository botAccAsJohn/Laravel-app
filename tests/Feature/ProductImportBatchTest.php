<?php

namespace Tests\Feature;

use App\Jobs\DispatchProductImportBatch;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Bus, Storage, Cache};
use Tests\TestCase;

class ProductImportBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_product_import_batch_is_configured_correctly()
    {
        $job = new DispatchProductImportBatch('temp/test.csv', 'key_123');

        $this->assertTrue($job->timeout > 0, 'Batch orchestrator should have a timeout');
        $this->assertEquals(1, $job->tries, 'Orchestrator retries are handled per-row, not for the orchestrator');
    }

    public function test_orchestrator_gracefully_handles_missing_file()
    {
        // The orchestrator uses Cache::put to store errors when file is missing
        $job = new DispatchProductImportBatch('temp/nonexistent.csv', 'key_missing');

        // Should not throw — handled gracefully inside handle()
        $job->handle();

        // Error should be stored in cache
        $this->assertNotNull(Cache::get('key_missing'));
    }
}
