<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\{DB, Log, Notification};
use App\Notifications\FailedJobAlert;

/**
 * Exercise 46.5 — JobFailed event listener.
 *
 * Laravel automatically fires Illuminate\Queue\Events\JobFailed whenever a
 * queued job exhausts all its retries. This listener provides CENTRALISED
 * failure reporting — one place catches ALL job failures regardless of which
 * queue or connection they came from.
 *
 * Registered in AppServiceProvider (or EventServiceProvider) via:
 *   Event::listen(JobFailed::class, HandleFailedJob::class);
 */
class HandleFailedJob
{
    public function handle(JobFailed $event): void
    {
        $jobName  = get_class($event->job);
        $queue    = $event->job->getQueue() ?? 'default';
        $error    = $event->exception->getMessage();
        $failedAt = now()->toDateTimeString();

        Log::channel('orders')->critical("JOB FAILED [{$jobName}] on queue [{$queue}]: {$error}");

        // Send instant Slack alert for critical job failures
        try {
            $slackChannel = config('services.slack.channels.bot_testing', '#bot-testing');

            Notification::route('slack', $slackChannel)
                ->notify(new FailedJobAlert([
                    'job'       => $jobName,
                    'queue'     => $queue,
                    'error'     => $error,
                    'failed_at' => $failedAt,
                ]));
        } catch (\Throwable $e) {
            // Never let the notifier itself crash the listener
            Log::error("HandleFailedJob: Could not send Slack alert — {$e->getMessage()}");
        }
    }
}
