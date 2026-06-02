<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exercise 53.3 — API Key table.
     *
     * Stores SHA-256 hashes of API keys, NEVER the plain-text key.
     *
     * Security design:
     * ─────────────────
     * • The plain-text key is generated once (random_bytes → base64_encode),
     *   returned to the user EXACTLY ONCE, then discarded.
     * • Only hash('sha256', $plainKey) is stored here.
     * • Verification: hash_equals(hash('sha256', $incoming), $stored_hash).
     * • SHA-256 is appropriate here (NOT bcrypt) because:
     *     - API keys are 32+ random bytes (not human-memorable — no dictionary
     *       attacks possible).
     *     - Verification happens on every API request (high-frequency).
     *     - bcrypt at cost 12 takes ~200 ms per check; SHA-256 takes < 1 µs.
     *     - The randomness of the key eliminates the need for salting.
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');                    // human-readable label
            $table->string('key_hash', 64)->unique();  // SHA-256 hex (64 chars)
            $table->string('key_prefix', 8);           // first 8 chars for UI ("sk-abc12…")
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('key_hash'); // fast O(1) lookup on every request
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
