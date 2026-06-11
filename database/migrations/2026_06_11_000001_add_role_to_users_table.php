<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reconciliation migration: the original users table migration was edited to
// add a `role` column after it had already run on the live database, so the
// live `users` table is missing it. Eloquent's full $user->save() (e.g. in
// LocaleController) writes `role`, which fails on the live schema.
//
// Idempotent + reversible (Module 42.3). Additive only — never edit a migration
// that has already run anywhere; add a new one instead (Module 42 pitfall).

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('user'); // 'admin' or 'user'
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
