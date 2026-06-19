<?php

// namespace Database\Seeders;

// use App\Models\Admin;
// use Illuminate\Database\Seeder;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

// class AdminSeeder extends Seeder
// {
//     use WithoutModelEvents;

//     public function run(): void
//     {
//         $password = Hash::make('password');

//         Admin::create([
//             'name' => 'Admin One',
//             'email' => 'admin@example.com',
//             'password' => $password,
//             'preferred_locale' => 'en',
//         ]);

//         Admin::create([
//             'name' => 'Admin Two',
//             'email' => 'admin2@example.com',
//             'password' => $password,
//             'preferred_locale' => 'en',
//         ]);
//     }
// }



namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function () {
            $password = Hash::make('password');
            $now = now();

            $adminRole = Role::where('name', 'admin')->first();

            $adminOne = Admin::create([
                'name' => 'Admin One',
                'email' => 'admin@example.com',
                'password' => $password,
                'preferred_locale' => 'en',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($adminRole) {
                $adminOne->roles()->attach($adminRole->id);
            }

            $adminTwo = Admin::create([
                'name' => 'Admin Two',
                'email' => 'admin2@example.com',
                'password' => $password,
                'preferred_locale' => 'en',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($adminRole) {
                $adminTwo->roles()->attach($adminRole->id);
            }
        });
    }
}
