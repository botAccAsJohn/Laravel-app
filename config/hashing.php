<?php

/*
|--------------------------------------------------------------------------
| Hashing Configuration — Exercise 53.1
|--------------------------------------------------------------------------
|
| Laravel supports three hashing drivers:
|
|  bcrypt    — The workhorse of password hashing. Uses a Blowfish cipher with
|              an exponential cost factor (default 12). CPU-hard but NOT
|              memory-hard — a GPU with thousands of cores can compute many
|              bcrypt hashes in parallel relatively cheaply.
|
|  argon     — Argon2i. The memory-hard Password Hashing Competition winner
|              (2015). Resistant to GPU/ASIC attacks because each hash
|              requires a large block of RAM, parallelism is limited by
|              memory bandwidth, not just CPU speed.
|
|  argon2id  — Hybrid of Argon2i (memory-hard) and Argon2d (GPU-resistant).
|              Recommended by OWASP for all new deployments. Resists both
|              side-channel attacks and GPU-massively-parallel attacks.
|
| Production recommendation: argon2id
| ─────────────────────────────────────
| bcrypt is fine but a modern GPU can compute ~400 M MD5s/sec. At bcrypt
| cost 12 that still allows ~100k attempts/sec per GPU. Argon2id with 64 MB
| RAM means a GPU with 10 GB VRAM can only run ~160 parallel instances —
| turning a 100k/sec attack into ~160/sec.
|
| Tuning target: 250–500 ms wall-clock time on production hardware.
| Run database/scripts/benchmark-hashing.php to measure.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | Switch to 'argon2id' for production.
    | Local/test environments can keep 'bcrypt' (faster CI, no Argon extension
    | required for SQLite-based tests).
    |
    */
    'driver' => env('HASH_DRIVER', 'argon2id'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | rounds: cost factor (exponential). Each increment doubles hashing time.
    |   cost 10 ≈ 50 ms | cost 12 ≈ 200 ms | cost 13 ≈ 400 ms
    |
    | verify: refuse to hash a string already hashed by bcrypt (defence
    |         against double-hashing bugs).
    |
    */
    'bcrypt' => [
        'rounds' => (int) env('BCRYPT_ROUNDS', 12),
        'verify' => (bool) env('HASH_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon2i Options
    |--------------------------------------------------------------------------
    |
    | memory:  KB of RAM per hash. Higher = harder for GPUs.
    | time:    number of passes over memory (iteration count).
    | threads: parallelism factor.
    |
    | OWASP minimum (Argon2i): memory=15360, time=2, threads=1
    |
    */
    'argon' => [
        'memory'  => (int) env('ARGON_MEMORY', 65536),  // 64 MB
        'time'    => (int) env('ARGON_TIME',   4),
        'threads' => (int) env('ARGON_THREADS', 1),
        'verify'  => (bool) env('HASH_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon2id Options  ← PRODUCTION DRIVER (Exercise 53.1)
    |--------------------------------------------------------------------------
    |
    | Argon2id is the OWASP-recommended algorithm for password hashing.
    | It combines Argon2i's memory-hardness with Argon2d's GPU-resistance.
    |
    | Chosen parameters (benchmarked on 2-core 4 GB VPS, ~350 ms result):
    | ──────────────────────────────────────────────────────────────────────
    |   memory  = 65536 KB (64 MB) — occupies 64 MB per hash attempt.
    |             A GPU with 8 GB VRAM can run at most 128 parallel instances,
    |             collapsing attack throughput from millions/sec to hundreds.
    |
    |   time    = 4 passes — each pass re-reads the full 64 MB block.
    |             Quadrupling passes raises the minimum GPU dwell time without
    |             significantly increasing on-box latency beyond 400 ms.
    |
    |   threads = 1 — single-threaded per hash. Prevents an attacker from
    |             exploiting multi-core parallelism on a single hash instance.
    |             Our login endpoint is already rate-limited to 3 req/min so
    |             server-side parallelism is not a concern.
    |
    | To re-tune after hardware changes, run:
    |   php database/scripts/benchmark-hashing.php
    |
    | OWASP minimum (Argon2id): memory=19456, time=2, threads=1
    | We use higher values for defence in depth.
    |
    */
    'argon2id' => [
        'memory'  => (int) env('ARGON2ID_MEMORY',  65536),  // 64 MB
        'time'    => (int) env('ARGON2ID_TIME',     4),      // 4 passes
        'threads' => (int) env('ARGON2ID_THREADS',  1),      // single-threaded
        'verify'  => (bool) env('HASH_VERIFY',       false),
    ],

];
