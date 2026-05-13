<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;

/**
 * Posted to #support when a customer submits a support request.
 *
 * Includes action buttons (Assign / In-Progress / Close) that hit
 * /api/slack/interactions — validated by VerifySlackSignature middleware.
 */
class NewSupportTicket extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket)
    {
        $this->onQueue('slack');
    }

    public function via(object $notifiable): array
    {
        return ['slack', 'database'];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $priorityEmoji = match ($this->ticket->priority) {
            'critical' => ':rotating_light:',
            'high'     => ':red_circle:',
            'medium'   => ':large_orange_circle:',
            default    => ':white_circle:',
        };

        $interactionBaseUrl = config('app.url') . '/api/slack/interactions';

        return (new SlackMessage)
            ->text("{$priorityEmoji} New Support Ticket: {$this->ticket->subject}")
            ->headerBlock("{$priorityEmoji} New Support Ticket")
            ->sectionBlock(function ($section) {
                $section->text(
                    "*Subject:* {$this->ticket->subject}\n" .
                    "*Customer:* {$this->ticket->customer_name}\n" .
                    "*Priority:* " . ucfirst($this->ticket->priority)
                )->markdown();
            })
            ->sectionBlock(function ($section) {
                $section->text("*Description:*\n" . \Illuminate\Support\Str::limit($this->ticket->description, 300))->markdown();
            })
            ->actionsBlock(function ($actions) use ($interactionBaseUrl) {
                $actions->button('Assign to me')->url("{$interactionBaseUrl}?action=assign&ticket_id={$this->ticket->id}");
                $actions->button('Mark in progress')->url("{$interactionBaseUrl}?action=in_progress&ticket_id={$this->ticket->id}");
                $actions->button('Close')->url("{$interactionBaseUrl}?action=close&ticket_id={$this->ticket->id}")->danger();
            })
            ->contextBlock(function ($ctx) {
                $ctx->text("*Ticket #:* {$this->ticket->id}")->markdown();
                $ctx->text('*Time:* ' . now()->toDateTimeString())->markdown();
            });
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'     => 'New Support Ticket',
            'message'   => "Ticket #{$this->ticket->id}: {$this->ticket->subject} ({$this->ticket->priority})",
            'ticket_id' => $this->ticket->id,
            'priority'  => $this->ticket->priority,
        ];
    }
}
