<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DailyDigest;

class SlackDailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slack:daily-digest {--preview : Post to #bot-testing instead of #leadership}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Post a daily summary into Slack using the scheduler';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $yesterday = Carbon::yesterday();
        $this->info("Collecting metrics for: " . $yesterday->toDateString());

        // 1. Data Collection (Module 29: Using Eloquent Collections)
        $orders = Order::whereDate('placed_at', $yesterday)->get();

        // Fetching all users and filtering in collection (Module 29 style)
        $newCustomers = User::all()->filter(function ($user) use ($yesterday) {
            return $user->created_at && $user->created_at->isSameDay($yesterday);
        });

        $failedJobsCount = DB::table('failed_jobs')->count();

        // Custom Collection Method (Module 29: ProductCollection)
        $lowStockProducts = Product::all()->lowStock(10);

        // 2. Build Summary using Collection Methods
        $summary = [
            'orders_count'    => $orders->count(),
            'revenue'         => (float) $orders->sum('total_amount'),
            'new_customers'   => $newCustomers->count(),
            'failed_jobs'     => $failedJobsCount,
            'low_stock_count' => $lowStockProducts->count(),
        ];

        // 3. Display Table in Console for QA
        $this->table(
            ['Metric', 'Value'],
            [
                ['Yesterday\'s Orders', $summary['orders_count']],
                ['Yesterday\'s Revenue', '$' . number_format($summary['revenue'], 2)],
                ['New Customers', $summary['new_customers']],
                ['Failed Jobs', $summary['failed_jobs']],
                ['Low Stock Products', $summary['low_stock_count']],
            ]
        );

        // 4. Determine Target Channel
        $channel = $this->option('preview')
            ? config('services.slack.channels.bot_testing', '#new-channel')
            : config('services.slack.channels.leadership', '#leadership');

        $this->info("Dispatching digest to Slack channel: " . $channel);

        // 5. Send On-demand Notification (Module 26: Scheduler Logic)
        Notification::route('slack', $channel)
            ->notify(new DailyDigest($summary));

        $this->info("Daily digest sent successfully!");
    }
}
