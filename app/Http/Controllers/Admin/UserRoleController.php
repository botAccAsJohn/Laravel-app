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

        $roles = Role::with('permissions')->where('guard_name', 'web')->orderBy('display_name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the role assignment form for a specific user.
     */
    public function edit(User $user): View
    {
        Gate::authorize('manage_users');

        $user->load('roles.permissions');

        $roles = Role::with('permissions')->where('guard_name', 'web')->orderBy('display_name')->get();
        $userRoleIds = $user->roles->pluck('id')->toArray();

        // Pass as 'targetUser' to avoid collision with the View::composer in
        // AppServiceProvider that overwrites '$user' with the logged-in admin.
        return view('admin.users.edit_roles', [
            'targetUser'  => $user,
            'roles'       => $roles,
            'userRoleIds' => $userRoleIds,
        ]);
    }


    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage_users');

        $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $newRoleIds = collect($request->input('roles', []))->map(fn($id) => (int) $id);

        // Always preserve the 'customer' base role — it grants core permissions
        // like place_order and should never be accidentally removed.
        $customerRole = Role::where('name', 'customer')->where('guard_name', 'web')->first();
        if ($customerRole && $user->roles->contains('id', $customerRole->id)) {
            $newRoleIds = $newRoleIds->push($customerRole->id)->unique();
        }

        $oldRoleIds = $user->roles->pluck('id');

        $added = $newRoleIds->diff($oldRoleIds);
        $removed = $oldRoleIds->diff($newRoleIds);

        // Sync the pivot — inserts/deletes as needed.
        // Audit trail is captured in the security log below.
        $user->roles()->sync($newRoleIds->all());

        // Bust the user's permission cache so new permissions take effect immediately.
        $user->forgetPermissionsCache();

        // ── Audit log ────────────────────────────────────────────────────────
        $actor = current_user();
        $addedNames = Role::whereIn('id', $added)->pluck('name')->toArray();
        $removedNames = Role::whereIn('id', $removed)->pluck('name')->toArray();

        Log::channel('security')->info('[RBAC] Role assignment changed', [
            'actor_email' => $actor?->email ?? 'unknown',
            'actor_id' => $actor?->id,
            'actor_guard' => current_guard(),
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

        $user->roles()->syncWithoutDetaching([$role->id]);

        $user->forgetPermissionsCache();

        Log::channel('security')->info('[RBAC] Role assigned', [
            'actor_email' => current_user()?->email,
            'actor_guard' => current_guard(),
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
            'actor_email' => current_user()?->email,
            'actor_guard' => current_guard(),
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
            'actor_email' => current_user()?->email,
            'actor_guard' => current_guard(),
            'user_email' => $user->email,
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return back()->with('magic_link', $url)->with('magic_link_user', $user->name);
    }
}
