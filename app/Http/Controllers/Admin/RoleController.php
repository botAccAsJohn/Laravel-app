<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Admin, Permission, Role, User};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth, Gate, Log};
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * List all roles with permission + user counts.
     */
    public function index(): View
    {
        Gate::authorize('manage_users');

        $roles = Role::withCount(['permissions', 'users'])
            ->orderBy('display_name')
            ->get();

        // The 'admin' RBAC role represents App\Models\Admin users, which live in
        // a separate 'admins' table and guard — they are never in the role_user
        // pivot. We override the users_count on that role with the real admin count.
        $adminCount = Admin::withTrashed(false)->count(); // non-deleted admins only
        $roles->each(function (Role $role) use ($adminCount) {
            if ($role->name === 'admin') {
                $role->users_count = $adminCount;
            }
        });

        $permissions = Permission::orderBy('group')->orderBy('display_name')->get()
            ->groupBy('group');

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    /**
     * Show the create-role form.
     */
    public function create(): View
    {
        Gate::authorize('manage_users');

        $permissions = Permission::orderBy('group')->orderBy('display_name')->get()
            ->groupBy('group');

        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a new role and sync its permissions.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage_users');

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:64', 'unique:roles,name', 'regex:/^[a-z0-9_]+$/'],
            'display_name' => ['required', 'string', 'max:128'],
            'description'  => ['nullable', 'string', 'max:512'],
            'permissions'  => ['nullable', 'array'],
            'permissions.*'=> ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'name'         => $validated['name'],
            'display_name' => $validated['display_name'],
            'description'  => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        Log::channel('security')->info('[RBAC] Role created', [
            'admin_email'    => Auth::guard('admin')->user()?->email,
            'role'           => $role->name,
            'permission_ids' => $validated['permissions'] ?? [],
        ]);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Role \"{$role->display_name}\" created.");
    }

    /**
     * Show the edit form for an existing role.
     */
    public function edit(Role $role): View
    {
        Gate::authorize('manage_users');

        $role->load('permissions');

        $permissions = Permission::orderBy('group')->orderBy('display_name')->get()
            ->groupBy('group');

        $assignedPermissionIds = $role->permissions->pluck('id')->toArray();

        // Show the first 10 users who hold this role for context
        $roleUsers = $role->users()->orderBy('name')->limit(10)->get();
        $roleUsersTotal = $role->users()->count();

        return view('admin.roles.edit', compact(
            'role', 'permissions', 'assignedPermissionIds', 'roleUsers', 'roleUsersTotal'
        ));
    }

    /**
     * Update a role's details and permission set.
     * Busts the permission cache for every affected user.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('manage_users');

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:128'],
            'description'  => ['nullable', 'string', 'max:512'],
            'permissions'  => ['nullable', 'array'],
            'permissions.*'=> ['integer', 'exists:permissions,id'],
        ]);

        $oldPermIds = $role->permissions->pluck('id')->sort()->values()->toArray();
        $newPermIds = collect($validated['permissions'] ?? [])->map(fn($id) => (int) $id)->sort()->values()->toArray();

        $role->update([
            'display_name' => $validated['display_name'],
            'description'  => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync($newPermIds);

        // Bust permission cache for all users who hold this role
        $role->users()->each(fn(User $user) => $user->forgetPermissionsCache());

        Log::channel('security')->info('[RBAC] Role updated', [
            'admin_email'  => Auth::guard('admin')->user()?->email,
            'role'         => $role->name,
            'perms_before' => $oldPermIds,
            'perms_after'  => $newPermIds,
        ]);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Role \"{$role->display_name}\" updated.");
    }

    /**
     * Delete a role.
     * Detaches all users and permissions first (pivot cascade handles it,
     * but we bust caches manually before the delete).
     */
    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('manage_users');

        // Protect core roles from accidental deletion
        if (in_array($role->name, ['admin', 'customer'], true)) {
            return back()->with('error', "The \"{$role->display_name}\" role cannot be deleted.");
        }

        // Bust caches before detach so we still have pivot data
        $role->users()->each(fn(User $user) => $user->forgetPermissionsCache());

        $name = $role->display_name;

        Log::channel('security')->info('[RBAC] Role deleted', [
            'admin_email' => Auth::guard('admin')->user()?->email,
            'role'        => $role->name,
            'user_count'  => $role->users()->count(),
        ]);

        $role->delete(); // cascade deletes pivot rows via DB constraint

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Role \"{$name}\" deleted.");
    }
}
