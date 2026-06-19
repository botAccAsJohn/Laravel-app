<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;

/**
 * Instant Slack alert sent by HandleFailedJob for every failed queue job.
 * NOT queued itself (no ShouldQueue) to avoid a failure loop.
 */
class FailedJobAlert extends Notification
{
    public function __construct(public readonly array $data) {}

    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text('🔴 Queue Job Failed')
            ->headerBlock('🔴 Queue Job Failed')
            ->sectionBlock(function (SectionBlock $section) {
                $section->field('*Job:* `' . $this->data['job'] . '`')->markdown();
                $section->field('*Queue:* ' . $this->data['queue'])->markdown();
            })
            ->sectionBlock(function (SectionBlock $section) {
                $section->text('*Error:* ' . $this->data['error'])->markdown();
            })
            ->contextBlock(function ($ctx) {
                $ctx->text(config('app.name') . ' • ' . $this->data['failed_at'])->markdown();
            });
    }
}
