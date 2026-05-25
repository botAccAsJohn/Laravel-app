<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Notification, Mail};
use App\Notifications\FailedJobsDigest;
use App\Mail\FailedJobsDigestMail;

/**
 * Exercise 46.5 — Scheduled daily digest of failed jobs.
 *
 * Scheduled in routes/console.php to run daily at 08:00.
 * Collects failed jobs from the last 24 hours and sends a summary to Slack.
 *
 * Usage:
 *   php artisan jobs:failed-digest          (sends to configured channel)
 *   php artisan jobs:failed-digest --preview (sends to #bot-testing instead)
 */
class SendFailedJobsDigest extends Command
{
    protected $signature = 'jobs:failed-digest {--preview : Send to #bot-testing instead of #ops-alerts}';
    protected $description = 'Send a daily Slack and email digest of failed jobs from the last 24 hours';

    public function handle(): int
    {
        $since = now()->subDay();

        // Read directly from the failed_jobs table
        $jobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', $since)
            ->orderBy('failed_at', 'desc')
            ->get(['uuid', 'queue', 'payload', 'exception', 'failed_at']);

        $total = $jobs->count();

        // Parse job class names from the serialised payload
        $byJobClass = $jobs
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return $payload['displayName'] ?? 'Unknown';
            })
            ->countBy()
            ->sortDesc();

        $this->info("Failed jobs in the last 24 hours: {$total}");
        if ($total > 0) {
            $this->table(['Job Class', 'Count'], $byJobClass->map(fn($c, $k) => [$k, $c])->toArray());
        }

        $digestData = [
            'total'      => $total,
            'by_class'   => $byJobClass->toArray(),
            'since'      => $since->toDateTimeString(),
            'retry_hint' => 'php artisan queue:retry all',
        ];

        // 1. Send Slack Notification
        $channel = $this->option('preview')
            ? config('services.slack.channels.bot_testing', '#bot-testing')
            : config('services.slack.channels.ops_alerts', '#ops-alerts');

        Notification::route('slack', $channel)->notify(new FailedJobsDigest($digestData));
        $this->info("Digest sent to Slack channel: {$channel}");

        // 2. Send Admin Email
        $adminEmail = config('mail.admin_email') ?: 'admin@example.com';
        Mail::to($adminEmail)->send(new FailedJobsDigestMail($digestData));
        $this->info("Digest email sent to: {$adminEmail}");

        return self::SUCCESS;
    }
}
