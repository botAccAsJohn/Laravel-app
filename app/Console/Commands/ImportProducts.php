<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class ImportProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-products {file : Path to the CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fast PostgreSQL product importer using bulk upserts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Starting import from: {$filePath}");

        DB::disableQueryLog();

        $metrics = [
            'processed' => 0,
            'errors' => 0,
        ];

        $startTime = microtime(true);
        $initialMemory = memory_get_usage();

        // Count total rows (excluding header)
        $totalLines = max($this->countLines($filePath) - 1, 0);

        $bar = $this->output->createProgressBar($totalLines);
        $bar->start();

        /*
        |--------------------------------------------------------------------------
        | Preload category IDs
        |--------------------------------------------------------------------------
        |
        | Avoid running:
        | exists:categories,id
        |
        | for every single row.
        |
        */

        $validCategoryIds = DB::table('categories')
            ->pluck('id')
            ->flip()
            ->toArray();

        DB::beginTransaction();

        try {

            LazyCollection::make(function () use ($filePath) {

                $handle = fopen($filePath, 'r');

                if (!$handle) {
                    throw new \RuntimeException("Unable to open file.");
                }

                $header = fgetcsv($handle);

                while (($row = fgetcsv($handle)) !== false) {

                    // Skip malformed rows
                    if (count($header) !== count($row)) {
                        continue;
                    }

                    yield array_combine($header, $row);
                }

                fclose($handle);
            })
                ->chunk(100)
                ->each(function ($chunk) use (
                    &$metrics,
                    $bar,
                    $validCategoryIds
                ) {

                    $rows = [];

                    $now = now();

                    foreach ($chunk as $row) {

                        /*
                    |--------------------------------------------------------------------------
                    | Basic validation
                    |--------------------------------------------------------------------------
                    */

                        if (
                            empty($row['name']) ||
                            empty($row['slug']) ||
                            !isset($row['price']) ||
                            !is_numeric($row['price'])
                        ) {
                            $metrics['errors']++;
                            $bar->advance();
                            continue;
                        }

                        /*
                    |--------------------------------------------------------------------------
                    | Validate category_id
                    |--------------------------------------------------------------------------
                    */

                        $categoryId = (int) ($row['category_id'] ?? 0);

                        if (!isset($validCategoryIds[$categoryId])) {
                            $metrics['errors']++;
                            $bar->advance();
                            continue;
                        }

                        /*
                    |--------------------------------------------------------------------------
                    | Prepare row
                    |--------------------------------------------------------------------------
                    */

                        $rows[] = [
                            'slug' => trim($row['slug']),
                            'name' => trim($row['name']),
                            'description' => $row['description'] ?? null,

                            'price' => (float) $row['price'],

                            'discount_price' => isset($row['discount_price']) &&
                                $row['discount_price'] !== ''
                                ? (float) $row['discount_price']
                                : null,

                            'tags' => !empty($row['tags'])
                                ? $row['tags']
                                : json_encode([]),

                            'category_id' => $categoryId,

                            'image_path' => $row['image_path'] ?? null,

                            'is_active' => isset($row['is_active'])
                                ? filter_var(
                                    $row['is_active'],
                                    FILTER_VALIDATE_BOOLEAN
                                )
                                : true,

                            'quantity' => isset($row['quantity'])
                                ? (int) $row['quantity']
                                : 0,

                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $metrics['processed']++;

                        $bar->advance();
                    }

                    /*
                |--------------------------------------------------------------------------
                | Bulk Upsert
                |--------------------------------------------------------------------------
                |
                | Requires unique index on slug
                |
                */

                    if (!empty($rows)) {

                        DB::table('products')->upsert(
                            $rows,

                            // Unique key
                            ['slug'],

                            // Columns to update
                            [
                                'name',
                                'description',
                                'price',
                                'discount_price',
                                'tags',
                                'category_id',
                                'image_path',
                                'is_active',
                                'quantity',
                                'updated_at',
                            ]
                        );
                    }
                });

            DB::commit();
        } catch (\Throwable $e) {

            DB::rollBack();

            $this->newLine(2);

            $this->error("Import failed!");
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $bar->finish();

        $this->newLine(2);

        $endTime = microtime(true);

        $peakMemory = memory_get_peak_usage();

        $memoryUsed = ($peakMemory - $initialMemory) / 1024 / 1024;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Processed', number_format($metrics['processed'])],
                ['Errors', number_format($metrics['errors'])],
                ['Time Elapsed', number_format($endTime - $startTime, 2) . ' sec'],
                ['Peak Memory Usage', number_format($memoryUsed, 2) . ' MB'],
            ]
        );

        $this->info('Import completed successfully.');

        return self::SUCCESS;
    }

    /**
     * Count file lines.
     */
    private function countLines(string $file): int
    {
        $lineCount = 0;

        $handle = fopen($file, 'r');

        while (!feof($handle)) {
            fgets($handle);
            $lineCount++;
        }

        fclose($handle);

        return $lineCount;
    }
}
