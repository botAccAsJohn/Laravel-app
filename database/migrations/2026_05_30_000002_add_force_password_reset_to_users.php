<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exercise 52.3 — Forced Password Reset flag.
     *
     * A nullable boolean on users.
     *   - NULL / false  → normal user, no restriction.
     *   - true          → user must reset password before using the app.
     *
     * Indexed so the middleware check (`WHERE id = ? AND force_password_reset = 1`)
     * hits the primary key and does not scan.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('force_password_reset')
                  ->default(false)
                  ->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('force_password_reset');
        });
    }
};
