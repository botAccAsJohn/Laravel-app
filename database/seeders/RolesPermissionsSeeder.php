<?php
// database/seeders/RolesPermissionsSeeder.php
// Exercise 50.4 — Seeds roles, permissions, and their associations.

namespace Database\Seeders;

use App\Models\{Permission, Role};
use Illuminate\Database\Seeder;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Permissions ───────────────────────────────────────────────────────
        $permissions = [
            // Products
            ['name' => 'manage_products',   'display_name' => 'Manage Products',   'group' => 'products',  'description' => 'Create, edit, delete products'],
            ['name' => 'view_products',     'display_name' => 'View Products',     'group' => 'products',  'description' => 'Browse product catalogue'],
            ['name' => 'import_products',   'display_name' => 'Import Products',   'group' => 'products',  'description' => 'Import products via CSV/Excel'],

            // Orders
            ['name' => 'place_order',       'display_name' => 'Place Order',       'group' => 'orders',    'description' => 'Place new orders in the store'],
            ['name' => 'manage_orders',     'display_name' => 'Manage Orders',     'group' => 'orders',    'description' => 'Update and manage all orders'],
            ['name' => 'refund_orders',     'display_name' => 'Refund Orders',     'group' => 'orders',    'description' => 'Issue refunds on orders'],
            ['name' => 'view_orders',       'display_name' => 'View Orders',       'group' => 'orders',    'description' => 'View all customer orders'],

            // Analytics & Reports
            ['name' => 'view_reports',      'display_name' => 'View Reports',      'group' => 'reports',   'description' => 'Access sales and order reports'],
            ['name' => 'manage_reports',    'display_name' => 'Manage Reports',    'group' => 'reports',   'description' => 'Archive and delete report files'],
            ['name' => 'view_analytics',    'display_name' => 'View Analytics',    'group' => 'reports',   'description' => 'Access sales analytics dashboard'],

            // Users
            ['name' => 'manage_users',      'display_name' => 'Manage Users',      'group' => 'users',     'description' => 'Assign roles and manage user accounts'],
            ['name' => 'impersonate_users', 'display_name' => 'Impersonate Users', 'group' => 'users',     'description' => 'Log in as any customer'],

            // Support
            ['name' => 'manage_support',    'display_name' => 'Manage Support',    'group' => 'support',   'description' => 'Handle support tickets'],
            ['name' => 'view_logs',         'display_name' => 'View Logs',         'group' => 'support',   'description' => 'View application and security logs'],

            // Communication
            ['name' => 'send_alerts',       'display_name' => 'Send Alerts',       'group' => 'comms',     'description' => 'Send manual admin alerts'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p['name']], $p);
        }

        // ── Roles & their permission sets ─────────────────────────────────────
        $roles = [
            [
                'role'        => ['name' => 'admin',    'display_name' => 'Administrator', 'description' => 'Full system access'],
                'permissions' => Permission::pluck('name')->toArray(), // all permissions
            ],
            [
                'role'        => ['name' => 'manager',  'display_name' => 'Store Manager',  'description' => 'Manages products, orders, and reports'],
                'permissions' => ['manage_products', 'view_products', 'import_products', 'place_order', 'manage_orders', 'view_orders', 'view_reports', 'manage_reports', 'view_analytics', 'manage_support', 'send_alerts'],
            ],
            [
                'role'        => ['name' => 'support',  'display_name' => 'Support Agent',  'description' => 'Handles customer support and order queries'],
                'permissions' => ['view_products', 'view_orders', 'refund_orders', 'manage_support', 'view_logs'],
            ],
            [
                // Standard customers can browse products AND place orders.
                // Users with a restricted role (no place_order) will be blocked by
                // OrderPolicy::create() and StoreOrderRequest::authorize().
                'role'        => ['name' => 'customer', 'display_name' => 'Customer',        'description' => 'Standard authenticated customer'],
                'permissions' => ['view_products', 'place_order'],
            ],
        ];

        foreach ($roles as $entry) {
            $role = Role::firstOrCreate(['name' => $entry['role']['name']], $entry['role']);

            $permissionIds = Permission::whereIn('name', $entry['permissions'])->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
