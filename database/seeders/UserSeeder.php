<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function () {
            $password = Hash::make('password');

            // Define admin data
            $admins = [
                [
                    'name' => 'Admin One',
                    'email' => 'admin@example.com',
                    'password' => $password,
                    'role' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Admin Two',
                    'email' => 'admin2@example.com',
                    'password' => $password,
                    'role' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ];

            $users = [
                [
                    'name' => 'User One',
                    'email' => 'user@example.com',
                    'password' => $password,
                    'role' => 'user',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ];

            User::insert($admins);
            User::insert($users);

            // Generate 50 customer data in memory
            $customers = User::factory()->count(50)->make()->map(function ($user) {
                // Laravel make() doesn't include hidden fields by default in toArray(),
                // so we make them visible.
                $data = $user->makeVisible(['password', 'remember_token'])->toArray();
                $data['role'] = 'user';
                $data['created_at'] = now();
                $data['updated_at'] = now();
                return $data;
            })->toArray();

            // Bulk insert customers
            User::insert($customers);
        });
    }
}
