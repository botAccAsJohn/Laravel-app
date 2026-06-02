<?php

namespace App\Http\Middleware;

use App\Services\WebhookSignatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates that incoming requests from Slack are authentic
 * by verifying the HMAC-SHA256 signature against the Slack signing secret.
 *
 * Exercise 53.3: Delegates to WebhookSignatureService so the
 * hash_hmac + hash_equals() logic is centralised and reusable for any
 * webhook integration (Stripe, GitHub, custom partners, etc.)
 *
 * @see https://api.slack.com/authentication/verifying-requests-from-slack
 */
class VerifySlackSignature
{
    public function __construct(private readonly WebhookSignatureService $signatures) {}

    public function handle(Request $request, Closure $next): Response
    {
        $signingSecret = config('services.slack.signing_secret');

        if (! $signingSecret) {
            abort(500, 'Slack signing secret is not configured.');
        }

        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');

        if (! $timestamp || ! $signature) {
            abort(403, 'Missing Slack signature headers.');
        }

        // Reject requests older than 5 minutes (replay attack protection).
        if (abs(time() - (int) $timestamp) > 300) {
            abort(403, 'Slack request timestamp is too old.');
        }

        // Slack base string: "v0:<timestamp>:<raw_body>"
        // Expected header format: "v0=<hmac_hex>"
        $baseString        = "v0:{$timestamp}:{$request->getContent()}";
        $expectedSignature = 'v0=' . hash_hmac('sha256', $baseString, $signingSecret);

        // Exercise 53.3: hash_equals() — constant-time comparison.
        // Delegates the raw HMAC comparison to WebhookSignatureService::verifyRaw()
        // so the timing-safe comparison pattern is centralised and tested once.
        $hmacHex = ltrim($signature, 'v0=');
        if (! $this->signatures->verifyRaw($baseString, $signingSecret, $hmacHex)) {
            abort(403, 'Invalid Slack signature.');
        }

        return $next($request);
    }
}
