<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;

/**
 * Daily Slack digest of failed jobs (last 24 hours).
 * NOT queued — runs synchronously from the scheduler.
 */
class FailedJobsDigest extends Notification
{
    public function __construct(public readonly array $data) {}

    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $total   = $this->data['total'];
        $emoji   = $total === 0 ? '✅' : '⚠️';
        $heading = $total === 0
            ? '✅ No Failed Jobs — All Clear!'
            : "⚠️ {$total} Failed Job(s) in Last 24 Hours";

        $msg = (new SlackMessage)
            ->text($heading)
            ->headerBlock($heading)
            ->sectionBlock(function (SectionBlock $section) {
                $section->field('*Period:* Last 24 hours (since ' . $this->data['since'] . ')')->markdown();
                $section->field('*Total Failed:* ' . $this->data['total'])->markdown();
            });

        if (!empty($this->data['by_class'])) {
            $breakdown = implode("\n", array_map(
                fn($count, $class) => "• `{$class}` → *{$count}×*",
                $this->data['by_class'],
                array_keys($this->data['by_class'])
            ));

            $msg->sectionBlock(function (SectionBlock $section) use ($breakdown) {
                $section->text("*Breakdown by Job Class:*\n" . $breakdown)->markdown();
            });

            $msg->sectionBlock(function (SectionBlock $section) {
                $section->text('*To retry all:* `' . $this->data['retry_hint'] . '`')->markdown();
            });
        }

        $msg->contextBlock(function ($ctx) {
            $ctx->text(config('app.name') . ' • ' . now()->toDateTimeString())->markdown();
        });

        return $msg;
    }
}
