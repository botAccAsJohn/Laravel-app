<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Handle contact form submission.
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        $ip = $request->ip();
        // Limit: 5 per hour (3600 seconds) per IP to prevent spam
        $key = 'contact-form:' . $ip;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            Log::channel('security')->warning('Rate limit hit: contact form submission', [
                'ip' => $ip,
                'available_in_seconds' => $seconds,
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'contact_error' => "You have sent too many contact requests. Please try again in {$minutes} minutes ({$seconds} seconds)."
                ]);
        }

        $sent = RateLimiter::attempt(
            $key,
            5,
            function () use ($validated) {
                // In a real application, we would send an email here.
                // Mail::to('admin@example.com')->send(new ContactMessage($validated));
                return true;
            },
            3600 // 1 hour decay
        );

        if (!$sent) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            Log::channel('security')->warning('Rate limit hit: contact form submission (attempt failed)', [
                'ip' => $ip,
                'available_in_seconds' => $seconds,
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'contact_error' => "You have sent too many contact requests. Please try again in {$minutes} minutes ({$seconds} seconds)."
                ]);
        }

        return redirect()->back()->with('success', 'Thank you for your message. We will get back to you soon.');
    }
}
