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
            ['name' => 'manage_products', 'display_name' => 'Manage Products', 'group' => 'products', 'description' => 'Create, edit, delete products and manage inventory'],
            ['name' => 'view_products',   'display_name' => 'View Products',   'group' => 'products', 'description' => 'Browse and search product catalog'],

            // orders
            ['name' => 'manage_orders',   'display_name' => 'Manage Orders',   'group' => 'orders',   'description' => 'View, update, and process customer orders'],
            ['name' => 'refund_orders',   'display_name' => 'Refund Orders',   'group' => 'orders',   'description' => 'Issue refunds for completed orders'],
            ['name' => 'place_order',     'display_name' => 'Place Order',     'group' => 'orders',   'description' => 'Create new orders from the storefront'],

            // reports
            ['name' => 'view_reports',    'display_name' => 'View Reports',    'group' => 'reports',  'description' => 'Access sales analytics and reports'],

            // users
            ['name' => 'manage_users',    'display_name' => 'Manage Users',    'group' => 'users',    'description' => 'View and manage user accounts and roles'],
            ['name' => 'assign_roles',    'display_name' => 'Assign Roles',    'group' => 'users',    'description' => 'Assign and revoke roles from users'],

            // cache
            ['name' => 'manage_cache',    'display_name' => 'Manage Cache',    'group' => 'cache',    'description' => 'Clear and monitor application cache'],
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
                'description'  => 'Store manager with broad access to products, orders, reporting, and refunds.',
                'permissions'  => ['manage_products', 'manage_orders', 'view_reports', 'refund_orders'],
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
