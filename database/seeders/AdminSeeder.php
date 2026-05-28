<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $password = Hash::make('password');

        Admin::create([
            'name' => 'Admin One',
            'email' => 'admin@example.com',
            'password' => $password,
            'preferred_locale' => 'en',
        ]);

        Admin::create([
            'name' => 'Admin Two',
            'email' => 'admin2@example.com',
            'password' => $password,
            'preferred_locale' => 'en',
        ]);
    }
}
