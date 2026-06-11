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

            $users = [
                [
                    'name' => 'User One',
                    'email' => 'user@example.com',
                    'password' => $password,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ];

            User::insert($users);

            // Generate 50 customer data in memory
            $customers = User::factory()->count(50)->make()->map(function ($user) {
                // Keep a reference to the raw date before toArray format conversions happen
                $emailVerifiedAt = $user->email_verified_at;

                // Laravel make() doesn't include hidden fields by default in toArray(),
                // so we make them visible.
                $data = $user->makeVisible(['password', 'remember_token'])->toArray();
                
                // Force MySQL compatible format if the date exists
                $data['email_verified_at'] = $emailVerifiedAt instanceof Carbon 
                    ? $emailVerifiedAt->format('Y-m-d H:i:s') 
                    : $emailVerifiedAt;

                $data['created_at'] = now();
                $data['updated_at'] = now();
                
                return $data;
            })->toArray();

            // Bulk insert customers
            User::insert($customers);
        });
    }
}