<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\DB;

class ImportProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-products {file : Path to the CSV file}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Import products from a CSV file using LazyCollection for memory efficiency';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return 1;
        }

        $this->info("Starting import from $filePath...");

        $metrics = [
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        $startTime = microtime(true);
        $initialMemory = memory_get_usage();

        // Count total lines for progress bar (excluding header)
        $totalLines = $this->countLines($filePath) - 1;
        $bar = $this->output->createProgressBar($totalLines);
        $bar->start();

        LazyCollection::make(function () use ($filePath) {
            $handle = fopen($filePath, 'r');
            $header = fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                yield array_combine($header, $row);
            }

            fclose($handle);
        })
        ->chunk(500)
        ->each(function (LazyCollection $chunk) use (&$metrics, $bar) {
            DB::transaction(function () use ($chunk, &$metrics, $bar) {
                foreach ($chunk as $row) {
                    $validator = Validator::make($row, [
                        'name' => 'required|string|max:255',
                        'price' => 'required|numeric|min:0',
                        'category_id' => 'required|exists:categories,id',
                        'slug' => 'required|string|unique:products,slug,' . ($this->getProductIdBySlug($row['slug'] ?? '') ?? 'NULL'),
                        'quantity' => 'integer|min:0',
                    ]);

                    if ($validator->fails()) {
                        $metrics['errors']++;
                        $this->newLine();
                        $this->error("Validation failed for product: " . ($row['name'] ?? 'Unknown'));
                        $this->line(implode(', ', $validator->errors()->all()));
                        $bar->advance();
                        continue;
                    }

                    $data = [
                        'name' => $row['name'],
                        'description' => $row['description'] ?? null,
                        'price' => $row['price'],
                        'discount_price' => $row['discount_price'] ?? null,
                        'tags' => json_decode($row['tags'] ?? '[]', true),
                        'category_id' => $row['category_id'],
                        'image_path' => ($row['image_path'] ?? null) ?: '/products/default.jpg',
                        'is_active' => $row['is_active'] ?? true,
                        'quantity' => $row['quantity'] ?? 0,
                    ];

                    $product = Product::where('slug', $row['slug'])->first();

                    if ($product) {
                        $product->update($data);
                        $metrics['updated']++;
                    } else {
                        $data['slug'] = $row['slug'];
                        Product::create($data);
                        $metrics['created']++;
                    }

                    $bar->advance();
                }
            });
        });

        $bar->finish();
        $this->newLine(2);

        $endTime = microtime(true);
        $peakMemory = memory_get_peak_usage();
        $memoryUsed = ($peakMemory - $initialMemory) / 1024 / 1024;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Created', $metrics['created']],
                ['Updated', $metrics['updated']],
                ['Errors', $metrics['errors']],
                ['Total Processed', $metrics['created'] + $metrics['updated'] + $metrics['errors']],
                ['Time Elapsed', number_format($endTime - $startTime, 2) . ' seconds'],
                ['Peak Memory Height', number_format($memoryUsed, 2) . ' MB'],
            ]
        );

        $this->info("Import completed!");

        return 0;
    }

    private function countLines($file)
    {
        $lineCount = 0;
        $handle = fopen($file, "r");
        while (!feof($handle)) {
            fgets($handle);
            $lineCount++;
        }
        fclose($handle);
        return $lineCount;
    }

    private function getProductIdBySlug($slug)
    {
        return Product::where('slug', $slug)->value('id');
    }
}
