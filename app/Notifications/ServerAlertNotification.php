<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Support\Facades\{Cache, Log};

/**
 * System Error Alert — sent to Slack when a 5xx error occurs.
 *
 * Routing: → #alerts channel via webhook (see User::routeNotificationForSlack)
 * Throttle: max 1 alert per exception signature per 10 minutes
 *
 * Triggered from: bootstrap/app.php withExceptions() handler
 */
class ServerAlertNotification extends Notification
{
    use Queueable;

    /** Fields that must NEVER leak into Slack payloads. */
    private const REDACTED_KEYS = ['password', 'token', 'secret', 'session', 'cookie', '_token', 'authorization'];

    public function __construct(
        protected string $exceptionClass,
        protected string $message,
        protected string $file,
        protected int    $line,
        protected string $requestUrl,
        protected int    $statusCode = 500,
    ) {
        $this->onQueue('slack');
    }

    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    /**
     * Check if this alert is throttled (same exception signature sent in last 10 minutes).
     * Returns TRUE if throttled (should NOT send), FALSE if OK to send.
     */
    public static function isThrottled(\Throwable $e): bool
    {
        $signature = md5($e::class . ':' . $e->getFile() . ':' . $e->getLine());
        $cacheKey  = "slack_error_alert:{$signature}";

        // Cache::add returns false if the key already exists (= throttled)
        return !Cache::add($cacheKey, true, now()->addMinutes(10));
    }

    /**
     * Build the notification from an exception + request context.
     * Sanitises the request URL to strip sensitive query params.
     */
    public static function fromException(\Throwable $e, ?\Illuminate\Http\Request $request = null): self
    {
        $url = $request ? self::sanitiseUrl($request->fullUrl()) : 'CLI / Queue';

        return new self(
            exceptionClass: $e::class,
            message:        \Illuminate\Support\Str::limit($e->getMessage(), 300),
            file:           $e->getFile(),
            line:           $e->getLine(),
            requestUrl:     $url,
            statusCode:     method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
        );
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $emoji = $this->statusCode >= 500 ? ':red_circle:' : ':warning:';

        return (new SlackMessage)
            ->text("{$emoji} [{$this->statusCode}] {$this->exceptionClass}")
            ->headerBlock("{$emoji} System Error — {$this->statusCode}")
            ->sectionBlock(function ($section) {
                $section->text(
                    "*Exception:* `{$this->exceptionClass}`\n" .
                    "*Message:* {$this->message}"
                )->markdown();
            })
            ->sectionBlock(function ($section) {
                $section->text(
                    "*File:* `{$this->file}`\n" .
                    "*Line:* {$this->line}\n" .
                    "*URL:* {$this->requestUrl}"
                )->markdown();
            })
            ->contextBlock(function ($ctx) {
                $ctx->text('*Environment:* ' . config('app.env'))->markdown();
                $ctx->text('*Time:* ' . now()->toDateTimeString())->markdown();
            });
    }

    /**
     * Strip sensitive query-string params from the URL.
     */
    private static function sanitiseUrl(string $url): string
    {
        $parsed = parse_url($url);

        if (!isset($parsed['query'])) {
            return $url;
        }

        parse_str($parsed['query'], $params);

        foreach (self::REDACTED_KEYS as $key) {
            if (isset($params[$key])) {
                $params[$key] = '***REDACTED***';
            }
        }

        $clean = $parsed['scheme'] . '://' . $parsed['host']
            . ($parsed['path'] ?? '/')
            . '?' . http_build_query($params);

        return $clean;
    }
}