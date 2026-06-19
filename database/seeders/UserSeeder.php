<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function () {
            $password = Hash::make('password');
            $now = now();

            // Create the primary user using Eloquent so observers/events can fire if added later
            $userOne = User::create([
                'name' => 'User One',
                'email' => 'user@example.com',
                'password' => $password,
                'created_at' => $now,
                'updated_at' => $now,
                'email_verified_at' => $now,
            ]);

            $customerRole = \App\Models\Role::where('name', 'customer')->first();
            if ($customerRole) {
                $userOne->roles()->attach($customerRole->id);
            }

            User::factory()->count(50)->create([
                'created_at' => $now,
                'updated_at' => $now,
                'email_verified_at' => $now,
            ]);
        });
    }
}