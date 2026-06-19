<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Exercise 53.3 — Webhook Signature Service
 *
 * Centralises HMAC-SHA256 signing and verification for outgoing and incoming
 * webhooks. Used by:
 *   - Outgoing webhooks: sign payloads sent to customer webhook_url endpoints.
 *   - Incoming webhooks: verify signatures on payloads received from partners.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * Why HMAC-SHA256 (not just SHA-256) for webhooks?
 * ──────────────────────────────────────────────────────────────────────────
 * Plain SHA-256 hashes a message with NO secret. Anyone who receives the
 * payload can recompute the same hash — it proves nothing about the sender.
 *
 * HMAC-SHA256 = hash(secret || message) — the secret is mixed into the hash
 * at a structural level (padding inner and outer). Only the sender (who knows
 * the secret) can produce a valid HMAC. A receiver can verify authenticity
 * by recomputing with the shared secret and comparing with hash_equals().
 *
 * ──────────────────────────────────────────────────────────────────────────
 * Why hash_equals() and NOT == for HMAC comparison?
 * ──────────────────────────────────────────────────────────────────────────
 * == performs a byte-by-byte comparison and returns FALSE as soon as the
 * first mismatch is found. An attacker who can measure response latency can
 * craft requests that reveal how many leading bytes of the real HMAC their
 * forged signature shares — a timing side-channel attack that can eventually
 * reconstruct the correct HMAC without knowing the secret.
 *
 * hash_equals() is implemented in C and always iterates over the FULL length
 * of both strings before returning. Response time is constant regardless of
 * where the mismatch occurs.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * API Keys vs Passwords — Different Hashing Strategies (Deliverable)
 * ──────────────────────────────────────────────────────────────────────────
 * | Property          | Passwords (argon2id)     | API Keys (SHA-256)       |
 * |-------------------|--------------------------|--------------------------|
 * | Input source      | Human-chosen (weak)      | Cryptographically random  |
 * | Brute-force risk  | HIGH without slow hash   | ZERO (160-bit entropy)   |
 * | Hash speed        | 250–500 ms intentionally | < 1 µs (fast)            |
 * | Verification freq | Once per login session   | Every API request        |
 * | Salting needed?   | YES (auto by argon2id)   | No (random key = salt)   |
 * | Use Hash::make()? | YES                      | NEVER — too slow         |
 *
 * Never use Hash::make() (bcrypt/argon2id) for API keys because:
 *   1. It would add 200–500 ms latency to EVERY API request.
 *   2. The security benefit is zero — a 160-bit random key cannot be
 *      dictionary-attacked regardless of hash speed.
 */
class WebhookSignatureService
{
    private const ALGORITHM = 'sha256';
    private const VERSION   = 'v1';           // prefix for versioned signatures

    /**
     * Sign an outgoing webhook payload.
     *
     * Returns the full signature header value: "v1=<hmac_hex>"
     *
     * Usage:
     *   $sig = $service->sign($payload, $secret);
     *   $client->withHeader('X-Signature', $sig)->post($url, $payload);
     */
    public function sign(string $payload, string $secret): string
    {
        $hmac = hash_hmac(self::ALGORITHM, $payload, $secret);
        return self::VERSION . '=' . $hmac;
    }

    /**
     * Verify an incoming webhook signature header.
     *
     * @param string $payload       Raw request body (MUST be the raw body,
     *                              not parsed JSON — parsing can alter bytes).
     * @param string $secret        The shared HMAC secret for this endpoint.
     * @param string $signature     The header value (e.g. "v1=abc123...").
     * @param int    $maxAgeSeconds Reject payloads older than this (default: 5 min).
     * @param int|null $timestamp   Unix timestamp from a separate timestamp header.
     *
     * @return bool
     */
    public function verify(
        string $payload,
        string $secret,
        string $signature,
        int    $maxAgeSeconds = 300,
        ?int   $timestamp     = null
    ): bool {
        // ── Step 1: Replay attack protection ───────────────────────────────
        // If a timestamp header is provided, reject stale requests.
        // An attacker who captures a valid webhook cannot replay it after the
        // window expires, even with the correct HMAC.
        if ($timestamp !== null && abs(time() - $timestamp) > $maxAgeSeconds) {
            Log::channel('security')->warning('[Webhook] Replay attack detected — stale timestamp', [
                'age_seconds' => abs(time() - $timestamp),
                'max_allowed' => $maxAgeSeconds,
            ]);
            return false;
        }

        // ── Step 2: Compute expected HMAC ───────────────────────────────────
        $expected = $this->sign($payload, $secret);

        // ── Step 3: Timing-safe comparison ─────────────────────────────────
        // hash_equals() prevents timing side-channel attacks.
        return hash_equals($expected, $signature);
    }

    /**
     * Verify a raw HMAC hex value (without the version prefix).
     *
     * Useful for simple partner integrations that send just the hex HMAC.
     *
     * Example: compare with hash_equals(hash_hmac('sha256', $body, $secret), $raw)
     */
    public function verifyRaw(string $payload, string $secret, string $rawHmac): bool
    {
        $computed = hash_hmac(self::ALGORITHM, $payload, $secret);

        // hash_equals() — constant-time, mandatory for HMAC comparison.
        return hash_equals($computed, $rawHmac);
    }

    /**
     * Generate a secure random webhook secret for a new endpoint.
     *
     * 32 random bytes = 256 bits of entropy.
     * Returned as hex (64 chars) for easy copy/paste into .env.
     */
    public function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }
}
