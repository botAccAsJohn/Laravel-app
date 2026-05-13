<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates that incoming requests from Slack are authentic
 * by verifying the HMAC-SHA256 signature against the Slack signing secret.
 *
 * @see https://api.slack.com/authentication/verifying-requests-from-slack
 */
class VerifySlackSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signingSecret = config('services.slack.signing_secret');

        if (!$signingSecret) {
            abort(500, 'Slack signing secret is not configured.');
        }

        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');

        // Reject if headers are missing
        if (!$timestamp || !$signature) {
            abort(403, 'Missing Slack signature headers.');
        }

        // Reject requests older than 5 minutes (replay attack protection)
        if (abs(time() - (int) $timestamp) > 300) {
            abort(403, 'Slack request timestamp is too old.');
        }

        // Compute the expected signature
        $baseString       = "v0:{$timestamp}:{$request->getContent()}";
        $expectedSignature = 'v0=' . hash_hmac('sha256', $baseString, $signingSecret);

        // Timing-safe comparison
        if (!hash_equals($expectedSignature, $signature)) {
            abort(403, 'Invalid Slack signature.');
        }

        return $next($request);
    }
}
