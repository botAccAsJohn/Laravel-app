<?php
// database/seeders/BackfillUserRolesSeeder.php
//
// One-time migration seeder that reads the legacy `users.role` string column
// and inserts matching rows into the `role_user` pivot table so that the new
// RBAC system reflects all existing user assignments.
//
// Safe to run multiple times — uses syncWithoutDetaching so it will NOT
// remove any roles that were already assigned via the new admin UI.
//
// Usage:
//   php artisan db:seed --class=BackfillUserRolesSeeder

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillUserRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Map legacy users.role string → RBAC role name.
        // In this app the legacy column uses 'user' for standard customers.
        $roleMap = [
            'admin'     => 'admin',
            'manager'   => 'manager',
            'support'   => 'support',
            'customer'  => 'customer',
            'user'      => 'customer',   // legacy value used by this app's seeders
        ];

        // Pre-load all roles keyed by name to avoid N+1 queries.
        $roles = Role::whereIn('name', array_values($roleMap))
            ->get()
            ->keyBy('name');

        $assigned = 0;
        $skipped  = 0;

        // Process in chunks to avoid memory exhaustion on large tables.
        User::withTrashed()
            ->whereNotNull('role')
            ->select(['id', 'role'])
            ->chunk(200, function ($users) use ($roleMap, $roles, &$assigned, &$skipped) {
                foreach ($users as $user) {
                    $legacyRole = $user->role;

                    if (! isset($roleMap[$legacyRole])) {
                        $this->command->warn("  Skipped user #{$user->id} — unknown legacy role: '{$legacyRole}'");
                        $skipped++;
                        continue;
                    }

                    $rbacRoleName = $roleMap[$legacyRole];

                    if (! isset($roles[$rbacRoleName])) {
                        $this->command->warn("  Skipped user #{$user->id} — RBAC role not found: '{$rbacRoleName}' (run RolesPermissionsSeeder first)");
                        $skipped++;
                        continue;
                    }

                    $role = $roles[$rbacRoleName];

                    // Check if already assigned to avoid duplicate pivot rows.
                    $alreadyAssigned = DB::table('role_user')
                        ->where('user_id', $user->id)
                        ->where('role_id', $role->id)
                        ->exists();

                    if (! $alreadyAssigned) {
                        DB::table('role_user')->insert([
                            'user_id'     => $user->id,
                            'role_id'     => $role->id,
                            'assigned_by' => null,
                            'assigned_at' => now(),
                        ]);

                        // Bust the user's permission cache so the change takes
                        // effect immediately without waiting for TTL expiry.
                        \Illuminate\Support\Facades\Cache::forget("user_permissions:{$user->id}");

                        $assigned++;
                    }
                }
            });

        Log::channel('security')->info('[Backfill] BackfillUserRolesSeeder completed', [
            'assigned' => $assigned,
            'skipped'  => $skipped,
        ]);

        $this->command->info("  ✓ Backfill complete — {$assigned} role assignments inserted, {$skipped} skipped.");
    }
}
