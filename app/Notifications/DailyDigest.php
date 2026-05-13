<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;

class DailyDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public array $data) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text('📊 ' . __('Daily Business Digest'))
            ->headerBlock('📊 ' . __('Daily Business Digest'))
            ->sectionBlock(function (SectionBlock $section) {
                $section->text('*' . __('Summary for') . ':* ' . now()->subDay()->format('M d, Y'))->markdown();
            })
            ->dividerBlock()
            ->sectionBlock(function (SectionBlock $section) {
                $section->field('*' . __('Orders') . ':* ' . $this->data['orders_count'])->markdown();
                $section->field('*' . __('Revenue') . ':* $' . number_format($this->data['revenue'], 2))->markdown();
                $section->field('*' . __('New Customers') . ':* ' . $this->data['new_customers'])->markdown();
                $section->field('*' . __('Failed Jobs') . ':* ' . $this->data['failed_jobs'])->markdown();
            })
            ->sectionBlock(function (SectionBlock $section) {
                $section->text('*' . __('Low Stock Products') . ':* ' . $this->data['low_stock_count'])->markdown();
            })
            ->contextBlock(function ($context) {
                $context->text('*' . config('app.name') . '* • ' . now()->toDateTimeString())->markdown();
            });
    }
}
