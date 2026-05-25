<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Illuminate\Bus\Batch;
use Throwable;

class DispatchProductImportBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 1;

    public function __construct(
        public readonly string $filePath,
        public readonly string $batchCacheKey,
    ) {}

    public function handle(): void
    {
        $fullPath = Storage::path($this->filePath);

        if (!file_exists($fullPath)) {
            Log::error("DispatchProductImportBatch: File not found at {$fullPath}");
            Cache::put($this->batchCacheKey, ['error' => 'CSV file not found on server.'], 600);
            return;
        }

        $jobs = LazyCollection::make(function () use ($fullPath) {
            $handle = fopen($fullPath, 'r');
            if (!$handle) {
                throw new \RuntimeException("Unable to open CSV file.");
            }

            $headers = fgetcsv($handle);
            if ($headers) {
                $headers = array_map(
                    fn($h) => trim(preg_replace('/[\x{FEFF}\x{200B}]/u', '', $h)),
                    $headers
                );
            }

            while (($row = fgetcsv($handle)) !== false) {
                if (!$headers || count($headers) !== count($row)) {
                    continue;
                }
                yield new ImportProductRowJob(array_combine($headers, $row));
            }

            fclose($handle);
        })->all();

        if (empty($jobs)) {
            Storage::delete($this->filePath);
            Cache::put($this->batchCacheKey, ['error' => 'CSV has no valid rows.'], 600);
            return;
        }

        $filePath = $this->filePath;
        $batchCacheKey = $this->batchCacheKey;

        $batch = Bus::batch($jobs)
            ->then(function (Batch $batch) use ($filePath) {
                Log::info("Batch {$batch->id} completed. Syncing Meilisearch...");
                \Illuminate\Support\Facades\Artisan::queue('scout:import', ['model' => \App\Models\Product::class]);
                Storage::delete($filePath);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($filePath) {
                Log::error("Batch {$batch->id} had failures: " . $e->getMessage());
            })
            ->finally(function (Batch $batch) use ($filePath) {
                Log::info("Batch {$batch->id} finished executing (finally).");
            })
            ->name('CSV Product Import')
            ->allowFailures()
            ->dispatch();

        // Store the real batch ID in cache so the HTTP layer can redirect to progress page
        Cache::put($batchCacheKey, ['batch_id' => $batch->id], 600);

        Log::info("Batch {$batch->id} dispatched with {$batch->totalJobs} jobs.");
    }
}
