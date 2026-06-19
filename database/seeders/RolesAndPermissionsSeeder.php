<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Permission, Role};

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]
            ->forgetCachedPermissions();

        $permissions = [
            // products
            ['name' => 'manage_products',  'display_name' => 'Manage Products',  'group' => 'products',  'description' => 'Create, edit, delete products and manage inventory'],
            ['name' => 'view_products',    'display_name' => 'View Products',    'group' => 'products',  'description' => 'Browse and search product catalog'],
            ['name' => 'import_products',  'display_name' => 'Import Products',  'group' => 'products',  'description' => 'Bulk import products via CSV or file upload'],

            // orders
            ['name' => 'manage_orders',    'display_name' => 'Manage Orders',    'group' => 'orders',    'description' => 'View, update, and process customer orders'],
            ['name' => 'refund_orders',    'display_name' => 'Refund Orders',    'group' => 'orders',    'description' => 'Issue refunds for completed orders'],
            ['name' => 'place_order',      'display_name' => 'Place Order',      'group' => 'orders',    'description' => 'Create new orders from the storefront'],

            // reports & analytics
            ['name' => 'view_reports',     'display_name' => 'View Reports',     'group' => 'reports',   'description' => 'Access sales analytics and reports'],
            ['name' => 'view_analytics',   'display_name' => 'View Analytics',   'group' => 'reports',   'description' => 'Access sales analytics dashboards'],
            ['name' => 'manage_reports',   'display_name' => 'Manage Reports',   'group' => 'reports',   'description' => 'Create, export, and schedule reports'],

            // users
            ['name' => 'manage_users',     'display_name' => 'Manage Users',     'group' => 'users',     'description' => 'View and manage user accounts and roles'],
            ['name' => 'assign_roles',     'display_name' => 'Assign Roles',     'group' => 'users',     'description' => 'Assign and revoke roles from users'],
            ['name' => 'impersonate_users','display_name' => 'Impersonate Users','group' => 'users',     'description' => 'Log in as another user for debugging'],

            // admin
            ['name' => 'view_admin_dashboard', 'display_name' => 'View Admin Dashboard', 'group' => 'admin', 'description' => 'Access the admin control panel'],
            ['name' => 'view_logs',        'display_name' => 'View Logs',        'group' => 'admin',     'description' => 'View application and security logs'],
            ['name' => 'send_alerts',      'display_name' => 'Send Alerts',      'group' => 'admin',     'description' => 'Send email alerts to users'],

            // cache
            ['name' => 'manage_cache',     'display_name' => 'Manage Cache',     'group' => 'cache',     'description' => 'Clear and monitor application cache'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['name' => $p['name'], 'guard_name' => 'web'],
                [
                    'display_name' => $p['display_name'],
                    'group'        => $p['group'],
                    'description'  => $p['description'],
                ]
            );
        }

        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['name' => $p['name'], 'guard_name' => 'admin'],
                [
                    'display_name' => $p['display_name'],
                    'group'        => $p['group'],
                    'description'  => $p['description'],
                ]
            );
        }

        $roles = [
            'customer' => [
                'display_name' => 'Customer',
                'description'  => 'Default role for storefront customers. Can browse products and place orders.',
                'permissions'  => ['view_products', 'place_order'],
            ],
            'support' => [
                'display_name' => 'Support',
                'description'  => 'Customer support agent. Can view orders, issue refunds, and assist customers.',
                'permissions'  => ['view_products', 'manage_orders', 'refund_orders'],
            ],
            'manager' => [
                'display_name' => 'Manager',
                'description'  => 'Store manager with broad access to products, orders, reporting, analytics, and refunds.',
                'permissions'  => ['manage_products', 'manage_orders', 'view_reports', 'refund_orders', 'view_analytics', 'manage_reports', 'import_products'],
            ],
        ];

        foreach ($roles as $roleName => $config) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                [
                    'display_name' => $config['display_name'],
                    'description'  => $config['description'],
                ]
            );
            $role->syncPermissions(
                Permission::whereIn('name', $config['permissions'])->where('guard_name', 'web')->get()
            );
        }

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description'  => 'Full system administrator with unrestricted access to all features.',
            ]
        );
        $adminRole->syncPermissions(
            Permission::where('guard_name', 'admin')->get()
        );
    }
}
