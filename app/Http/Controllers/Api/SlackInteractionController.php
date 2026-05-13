<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Http, Log};

/**
 * Handles interactive Slack actions (button clicks) from support ticket notifications.
 *
 * Protected by VerifySlackSignature middleware (validates HMAC against the signing secret).
 * Updates ticket status and posts a confirmation message back to the Slack thread.
 */
class SlackInteractionController extends Controller
{
    public function handle(Request $request)
    {
        // Slack sends interactive payloads as a JSON-encoded "payload" field
        $payload = json_decode($request->input('payload'), true);

        // URL-based actions (from simple link buttons) come as query params
        $action   = $payload['actions'][0]['action_id'] ?? $request->query('action');
        $ticketId = $request->query('ticket_id') ?? ($payload['actions'][0]['value'] ?? null);

        if (!$action || !$ticketId) {
            return response()->json(['error' => 'Missing action or ticket_id'], 400);
        }

        $ticket = SupportTicket::findOrFail($ticketId);

        $update = match ($action) {
            'assign' => [
                'status'      => 'assigned',
                'assigned_to' => auth()->id(),
                'message'     => '✅ Ticket assigned to ' . (auth()->user()->name ?? 'an admin'),
            ],
            'in_progress' => [
                'status'  => 'in_progress',
                'message' => '🔄 Ticket marked as in progress',
            ],
            'close' => [
                'status'    => 'closed',
                'closed_at' => now(),
                'message'   => '🔒 Ticket closed',
            ],
            default => null,
        };

        if (!$update) {
            return response()->json(['error' => 'Unknown action'], 400);
        }

        $message = $update['message'];
        unset($update['message']);

        $ticket->update($update);

        Log::channel('admin')->info("Support Ticket #{$ticket->id} updated via Slack", [
            'action' => $action,
            'status' => $ticket->status,
        ]);

        // Post a thread reply back to Slack confirming the action
        $this->postSlackThreadReply($payload, $message, $ticket);

        return response()->json([
            'response_action' => 'clear',
            'text'            => $message,
        ]);
    }

    /**
     * Post a confirmation message back into the same Slack thread.
     */
    private function postSlackThreadReply(?array $payload, string $message, SupportTicket $ticket): void
    {
        // If we don't have a payload with channel/ts info, we can't reply in-thread
        if (!$payload || !isset($payload['channel']['id'], $payload['message']['ts'])) {
            return;
        }

        $token = config('services.slack.notifications.bot_user_oauth_token');

        if (!$token) {
            return;
        }

        rescue(function () use ($payload, $message, $ticket, $token) {
            Http::withToken($token)->post('https://slack.com/api/chat.postMessage', [
                'channel'   => $payload['channel']['id'],
                'thread_ts' => $payload['message']['ts'],
                'text'      => "{$message} — Ticket #{$ticket->id} ({$ticket->status})",
            ]);
        }, function ($e) {
            Log::channel('admin')->error('Failed to post Slack thread reply: ' . $e->getMessage());
        });
    }
}
