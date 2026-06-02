<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exercise 52.2 — Password History table.
     *
     * Stores the last N bcrypt hashes for each user so that
     * newly chosen passwords can be checked against them.
     *
     * Security notes
     * ──────────────
     * • Hashes (not plain-text) are stored so that a database breach
     *   does NOT expose previous passwords — an attacker still needs
     *   to crack each bcrypt hash individually.
     * • `created_at` lets us order and prune to keep only the most
     *   recent N entries per user.
     */
    public function up(): void
    {
        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete(); // clean up when user is deleted
            $table->string('password');  // bcrypt hash — never plain-text
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_histories');
    }
};
