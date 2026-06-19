<?php

/**
 * Exercise 53.1 — Hashing Algorithm Benchmark Script
 *
 * Usage:
 *   php database/scripts/benchmark-hashing.php
 *
 * Purpose:
 *   Measure the wall-clock time for each hashing algorithm/configuration on
 *   the current hardware. Use results to tune config/hashing.php so that
 *   hashing takes 250–500 ms (balances security with user-facing latency).
 *
 * Why 250–500 ms?
 *   • < 100 ms: Too fast — online attackers with offline DB dumps can test
 *               millions of guesses per second per GPU.
 *   • 250–500 ms: Imperceptible to users (login latency < 600 ms feels
 *                 instant), but slows GPU attacks to hundreds of guesses/sec.
 *   • > 500 ms: Users notice the delay, especially on mobile or slow networks.
 *
 * Run this AFTER any of the following changes:
 *   - Upgrading server hardware (more RAM/CPUs)
 *   - Changing cloud instance type
 *   - Modifying argon2id parameters in config/hashing.php
 *   - Switching PHP versions (argon2 performance varies by build)
 */

declare(strict_types=1);

// ── Bootstrap ────────────────────────────────────────────────────────────────
// Run from the project root (php database/scripts/benchmark-hashing.php).
$appRoot = dirname(__DIR__, 2);
require $appRoot . '/vendor/autoload.php';

$app = require_once $appRoot . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;

// ── Configuration sets to test ────────────────────────────────────────────────
$plain = 'BenchmarkPassword123!'; // Does not matter — we only time Hash::make()

$configs = [

    // ── Bcrypt ───────────────────────────────────────────────────────────────
    'bcrypt (cost=10)'  => fn() => bcrypt($plain, ['rounds' => 10]),
    'bcrypt (cost=12)'  => fn() => bcrypt($plain, ['rounds' => 12]),
    'bcrypt (cost=13)'  => fn() => bcrypt($plain, ['rounds' => 13]),
    'bcrypt (cost=14)'  => fn() => bcrypt($plain, ['rounds' => 14]),

    // ── Argon2i ──────────────────────────────────────────────────────────────
    'argon2i (m=32M t=2)'  => fn() => password_hash($plain, PASSWORD_ARGON2I,  ['memory_cost' => 32768,  'time_cost' => 2, 'threads' => 1]),
    'argon2i (m=64M t=4)'  => fn() => password_hash($plain, PASSWORD_ARGON2I,  ['memory_cost' => 65536,  'time_cost' => 4, 'threads' => 1]),

    // ── Argon2id (RECOMMENDED — OWASP) ───────────────────────────────────────
    'argon2id (m=19M t=2)' => fn() => password_hash($plain, PASSWORD_ARGON2ID, ['memory_cost' => 19456,  'time_cost' => 2, 'threads' => 1]),
    'argon2id (m=32M t=2)' => fn() => password_hash($plain, PASSWORD_ARGON2ID, ['memory_cost' => 32768,  'time_cost' => 2, 'threads' => 1]),
    'argon2id (m=64M t=2)' => fn() => password_hash($plain, PASSWORD_ARGON2ID, ['memory_cost' => 65536,  'time_cost' => 2, 'threads' => 1]),
    'argon2id (m=64M t=4)' => fn() => password_hash($plain, PASSWORD_ARGON2ID, ['memory_cost' => 65536,  'time_cost' => 4, 'threads' => 1]),
    'argon2id (m=64M t=6)' => fn() => password_hash($plain, PASSWORD_ARGON2ID, ['memory_cost' => 65536,  'time_cost' => 6, 'threads' => 1]),
    'argon2id (m=128M t=4)'=> fn() => password_hash($plain, PASSWORD_ARGON2ID, ['memory_cost' => 131072, 'time_cost' => 4, 'threads' => 1]),

    // ── Hash::make() with current config ─────────────────────────────────────
    'Hash::make() [current config]' => fn() => Hash::make($plain),
];

// ── Iterations ───────────────────────────────────────────────────────────────
$iterations = (int) ($argv[1] ?? 3); // default: average over 3 runs

// ── Output header ─────────────────────────────────────────────────────────────
$width = 38;
printf("\n%-{$width}s  %8s  %8s  %8s  %s\n",
    'Algorithm', 'Min ms', 'Avg ms', 'Max ms', 'Rating');
printf("%s\n", str_repeat('─', 80));

// ── Sweet-spot range ─────────────────────────────────────────────────────────
$targetMin = 250;
$targetMax = 500;

foreach ($configs as $label => $fn) {
    $times = [];

    for ($i = 0; $i < $iterations; $i++) {
        $start   = hrtime(true);
        $fn();
        $elapsed = (hrtime(true) - $start) / 1_000_000; // ns → ms
        $times[] = $elapsed;
    }

    $avg = array_sum($times) / count($times);
    $min = min($times);
    $max = max($times);

    // Rating
    $rating = match(true) {
        $avg < 50                        => '🔴 Too fast (insecure)',
        $avg >= 50  && $avg < $targetMin => '🟡 Acceptable (could be stronger)',
        $avg >= $targetMin && $avg <= $targetMax => '✅ SWEET SPOT',
        $avg > $targetMax && $avg <= 800 => '🟡 Slightly slow',
        default                          => '🔴 Too slow (UX impact)',
    };

    printf("%-{$width}s  %8.1f  %8.1f  %8.1f  %s\n",
        $label,
        $min,
        $avg,
        $max,
        $rating,
    );
}

printf("%s\n", str_repeat('─', 80));
printf("\nTarget: %d–%d ms  |  Hardware: %s  |  PHP: %s\n\n",
    $targetMin, $targetMax,
    php_uname('m') . ' ' . php_uname('s'),
    PHP_VERSION,
);

printf("After choosing parameters, set in .env:\n");
printf("  HASH_DRIVER=argon2id\n");
printf("  ARGON2ID_MEMORY=65536\n");
printf("  ARGON2ID_TIME=4\n");
printf("  ARGON2ID_THREADS=1\n\n");
