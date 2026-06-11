<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== ROLES (live) ===\n";
foreach (Role::withCount('permissions')->get() as $r) {
    printf("  id=%d name=%s display_name=%s perms=%d\n",
        $r->id, var_export($r->name, true), var_export($r->display_name, true), $r->permissions_count);
}

echo "\n=== role_user pivot rows (count): ".DB::table('role_user')->count()." ===\n";
foreach (DB::table('role_user')->limit(10)->get() as $row) {
    echo "  user_id={$row->user_id} role_id={$row->role_id} assigned_by=".var_export($row->assigned_by ?? null, true)."\n";
}

echo "\n=== TEST assignment: give first role to user id=1 ===\n";
$user = User::find(1);
$role = Role::orderBy('id')->first();
if ($user && $role) {
    try {
        $user->roles()->syncWithoutDetaching([
            $role->id => ['assigned_by' => null, 'assigned_at' => now()],
        ]);
        $fresh = User::with('roles')->find(1);
        echo "  assigned role id={$role->id} (name=".var_export($role->name,true).")\n";
        echo "  user id=1 now has roles: ".$fresh->roles->pluck('name')->implode(', ')." (count ".$fresh->roles->count().")\n";
    } catch (\Throwable $e) {
        echo "  ASSIGN FAILED: ".get_class($e).": ".$e->getMessage()."\n";
    }
}
