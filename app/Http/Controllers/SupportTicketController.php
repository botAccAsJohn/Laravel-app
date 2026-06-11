<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    /**
     * Show the form for creating a new support ticket.
     */
    public function create()
    {
        return view('support.create');
    }

    /**
     * Store a newly created support ticket.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'priority' => 'nullable|in:low,medium,high,critical',
        ]);

        $user = $request->user();
        // Limit: 3 per day (86400 seconds) by user ID if logged in, else IP
        $key = 'support-tickets:' . ($user ? $user->id : $request->ip());

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $hours = ceil($seconds / 3600);

            Log::channel('security')->warning('Rate limit hit: support ticket submission', [
                'ip' => $request->ip(),
                'user_id' => $user?->id,
                'available_in_seconds' => $seconds,
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'ticket_error' => "You have submitted too many support tickets. Please try again in {$hours} hours ({$seconds} seconds)."
                ]);
        }

        $ticket = new SupportTicket([
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'open',
        ]);

        $saved = RateLimiter::attempt(
            $key,
            3,
            function () use ($ticket) {
                return $ticket->save();
            },
            86400 // 24 hours
        );

        if (!$saved) {
            $seconds = RateLimiter::availableIn($key);
            $hours = ceil($seconds / 3600);

            Log::channel('security')->warning('Rate limit hit: support ticket submission (attempt failed)', [
                'ip' => $request->ip(),
                'user_id' => $user?->id,
                'available_in_seconds' => $seconds,
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'ticket_error' => "You have submitted too many support tickets. Please try again in {$hours} hours ({$seconds} seconds)."
                ]);
        }

        // In a real application, we might fire a notification or dispatch a job here.
        // e.g. event(new \App\Events\SupportTicketCreated($ticket));

        return redirect()->back()->with('success', 'Support ticket created successfully.');
    }
}
