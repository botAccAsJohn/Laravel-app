<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateAdminReport extends Command
{
    protected $signature = 'report:admin {--type=sales : sales,inventory,customers} {--format=csv : csv,pdf,json}';

    protected $description = 'Generate admin report';

    public function handle()
    {
        $type = strtolower((string) $this->option('type'));
        $format = strtolower((string) $this->option('format'));

        $allowedTypes = ['sales', 'inventory', 'customers'];
        $allowedFormats = ['csv', 'json', 'pdf'];

        if (!in_array($type, $allowedTypes, true)) {
            $this->error('Invalid --type. Use: sales, inventory, customers');
            return self::FAILURE;
        }

        if (!in_array($format, $allowedFormats, true)) {
            $this->error('Invalid --format. Use: csv, json, pdf');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info(" Generating {$type} report ({$format})...");
        $this->newLine();

        $progress = $this->output->createProgressBar(3);
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progress->start();

        $progress->setMessage('Collecting data...');
        $data = $this->buildReportData($type);
        $progress->advance();

        $progress->setMessage('Formatting report...');
        $contents = $this->formatReport($type, $format, $data);
        $progress->advance();

        $progress->setMessage('Saving file...');
        $filename = sprintf('%s_%s.%s', $type, now()->format('Ymd_His'), $format);
        $absolutePath = storage_path('app/reports/' . $filename);

        if (!File::isDirectory(dirname($absolutePath))) {
            File::makeDirectory(dirname($absolutePath), 0755, true, true);
        }

        File::put($absolutePath, $contents);
        $progress->advance();

        $progress->setMessage('Done!');
        $progress->finish();
        $this->newLine(2);

        $this->line("<info>Report saved to:</info> {$absolutePath}");
        $this->newLine();

        $this->showSummary($type, $data);

        return self::SUCCESS;
    }

    private function buildReportData(string $type): array
    {
        return match ($type) {
            'sales' => $this->salesReportData(),
            'inventory' => $this->inventoryReportData(),
            'customers' => $this->customersReportData(),
        };
    }

    private function salesReportData(): array
    {

        $orderStats = DB::table('orders')
            ->where('status', 'delivered')
            ->selectRaw('SUM(total_amount) as total_revenue, COUNT(*) as order_count')
            ->first();


        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('SUM(order_items.quantity) as qty_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('qty_sold')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'name' => $row->name,
                'qty_sold' => (int) $row->qty_sold,
            ])
            ->all();

        return [
            'total_revenue' => (float) ($orderStats->total_revenue ?? 0),
            'order_count'   => (int)   ($orderStats->order_count   ?? 0),
            'top_products'  => $topProducts,
        ];
    }

    private function inventoryReportData(): array
    {
        $lowStockItems = DB::table('products')
            ->whereBetween('quantity', [1, 5])
            ->orderBy('quantity')
            ->get(['name', 'quantity'])
            ->map(fn($row) => [
                'name' => $row->name,
                'stock' => (int) $row->quantity,
            ])
            ->all();

        $outOfStock = DB::table('products')
            ->where('quantity', '<=', 0)
            ->count();

        $totalValue = DB::table('products')
            ->select(DB::raw('SUM(quantity * price) as total_value'))
            ->value('total_value');

        return [
            'low_stock_items' => $lowStockItems,
            'out_of_stock' => (int) $outOfStock,
            'total_value' => (float) ($totalValue ?? 0),
        ];
    }

    private function customersReportData(): array
    {
        $newRegistrations = DB::table('users')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->count();

        $topBuyers = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select('users.name', DB::raw('SUM(orders.total_amount) as spent'))
            ->where('orders.status', 'delivered')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('spent')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'name' => $row->name,
                'spent' => (float) $row->spent,
            ])
            ->all();

        $inactiveUsers = DB::table('users')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('orders')
                    ->whereColumn('orders.user_id', 'users.id')
                    ->where('orders.placed_at', '>=', now()->subDays(90)); // ✅ correct column
            })
            ->count();

        return [
            'new_registrations' => (int) $newRegistrations,
            'top_buyers' => $topBuyers,
            'inactive_users' => (int) $inactiveUsers,
        ];
    }

    private function formatReport(string $type, string $format, array $data): string
    {
        return match ($format) {
            'json' => json_encode([
                'type' => $type,
                'generated_at' => now()->toDateTimeString(),
                'data' => $data,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),

            'csv' => $this->toCsv($type, $data),

            'pdf' => $this->toPdf($type, $data),
        };
    }

    private function toCsv(string $type, array $data): string
    {
        $rows = [];

        if ($type === 'sales') {
            $rows[] = ['Metric', 'Value'];
            $rows[] = ['Total Revenue', $data['total_revenue']];
            $rows[] = ['Order Count', $data['order_count']];
            $rows[] = ['Top Products', ''];
            foreach ($data['top_products'] as $product) {
                $rows[] = ['', $product['name'] . ' (' . $product['qty_sold'] . ')'];
            }
        }

        if ($type === 'inventory') {
            $rows[] = ['Metric', 'Value'];
            $rows[] = ['Out of Stock', $data['out_of_stock']];
            $rows[] = ['Total Value', $data['total_value']];
            $rows[] = ['Low Stock Items', ''];
            foreach ($data['low_stock_items'] as $item) {
                $rows[] = ['', $item['name'] . ' (' . $item['stock'] . ')'];
            }
        }

        if ($type === 'customers') {
            $rows[] = ['Metric', 'Value'];
            $rows[] = ['New Registrations', $data['new_registrations']];
            $rows[] = ['Inactive Users', $data['inactive_users']];
            $rows[] = ['Top Buyers', ''];
            foreach ($data['top_buyers'] as $buyer) {
                $rows[] = ['', $buyer['name'] . ' (' . $buyer['spent'] . ')'];
            }
        }

        $csv = [];
        foreach ($rows as $row) {
            $csv[] = implode(',', array_map(fn($value) => '"' . str_replace('"', '""', (string) $value) . '"', $row));
        }

        return implode(PHP_EOL, $csv);
    }

    private function toPdf(string $type, array $data): string
    {
        $pdf = Pdf::loadView('layouts.admin-report', [
            'type' => $type,
            'generatedAt' => now()->toDateTimeString(),
            'data' => $data,
        ]);

        return $pdf->output();
    }

    private function showSummary(string $type, array $data): void
    {
        if ($type === 'sales') {
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Revenue', number_format($data['total_revenue'], 2)],
                    ['Order Count', $data['order_count']],
                    ['Top Products', count($data['top_products'])],
                ]
            );
            return;
        }

        if ($type === 'inventory') {
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Low Stock Items', count($data['low_stock_items'])],
                    ['Out of Stock', $data['out_of_stock']],
                    ['Total Value', number_format($data['total_value'], 2)],
                ]
            );
            return;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['New Registrations', $data['new_registrations']],
                ['Top Buyers', count($data['top_buyers'])],
                ['Inactive Users', $data['inactive_users']],
            ]
        );
    }
}
