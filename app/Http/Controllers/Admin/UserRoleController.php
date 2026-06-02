<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Role, User};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth, Gate, Log};
use Illuminate\View\View;

class UserRoleController extends Controller
{
    /**
     * List all customers with their current roles.
     */
    public function index(): View
    {
        Gate::authorize('manage_users');

        $users = User::with('roles')
            ->withTrashed()
            ->orderBy('name')
            ->paginate(20);

        $roles = Role::with('permissions')->orderBy('display_name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the role assignment form for a specific user.
     */
    public function edit(User $user): View
    {
        Gate::authorize('manage_users');

        $user->load('roles.permissions');

        $roles = Role::with('permissions')->orderBy('display_name')->get();
        $userRoleIds = $user->roles->pluck('id')->toArray();

        return view('admin.users.edit_roles', compact('user', 'roles', 'userRoleIds'));
    }


    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage_users');

        $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $newRoleIds = collect($request->input('roles', []))->map(fn($id) => (int) $id);
        $oldRoleIds = $user->roles->pluck('id');

        $added = $newRoleIds->diff($oldRoleIds);
        $removed = $oldRoleIds->diff($newRoleIds);

        // Sync the pivot — this inserts/deletes as needed.
        // We manually attach with assigned_by so the audit trail is on the pivot.
        $syncData = $newRoleIds->mapWithKeys(fn($id) => [
            $id => [
                'assigned_by' => Auth::guard('admin')->id(),
                'assigned_at' => now(),
            ],
        ])->all();

        $user->roles()->sync($syncData);

        // Bust the user's permission cache so new permissions take effect immediately.
        $user->forgetPermissionsCache();

        // ── Audit log ────────────────────────────────────────────────────────
        $admin = Auth::guard('admin')->user();
        $addedNames = Role::whereIn('id', $added)->pluck('name')->toArray();
        $removedNames = Role::whereIn('id', $removed)->pluck('name')->toArray();

        Log::channel('security')->info('[RBAC] Role assignment changed', [
            'admin_email' => $admin?->email ?? 'unknown',
            'admin_id' => $admin?->id,
            'user_email' => $user->email,
            'user_id' => $user->id,
            'roles_added' => $addedNames,
            'roles_removed' => $removedNames,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Roles updated for {$user->name}.");
    }

    /**
     * Quick single-role assign via POST (for the inline toggle in the table).
     */
    public function assignRole(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage_users');

        $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($request->role_id);

        $user->roles()->syncWithoutDetaching([
            $role->id => [
                'assigned_by' => Auth::guard('admin')->id(),
                'assigned_at' => now(),
            ],
        ]);

        $user->forgetPermissionsCache();

        Log::channel('security')->info('[RBAC] Role assigned', [
            'admin_email' => Auth::guard('admin')->user()?->email,
            'user_email' => $user->email,
            'role' => $role->name,
        ]);

        return back()->with('success', "Role '{$role->display_name}' assigned to {$user->name}.");
    }

    /**
     * Revoke a single role from a user.
     */
    public function revokeRole(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage_users');

        $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($request->role_id);
        $user->roles()->detach($role->id);
        $user->forgetPermissionsCache();

        Log::channel('security')->info('[RBAC] Role revoked', [
            'admin_email' => Auth::guard('admin')->user()?->email,
            'user_email' => $user->email,
            'role' => $role->name,
        ]);

        return back()->with('success', "Role '{$role->display_name}' revoked from {$user->name}.");
    }

    /**
     * Generate a signed magic-link URL for a given user.
     */
    public function generateMagicLink(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage_users');

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'magic.login',
            now()->addMinutes(15),
            ['userId' => $user->id]
        );

        Log::channel('security')->info('[ManualAuth] Admin generated magic link', [
            'admin_email' => Auth::guard('admin')->user()?->email,
            'user_email' => $user->email,
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return back()->with('magic_link', $url)->with('magic_link_user', $user->name);
    }
}
