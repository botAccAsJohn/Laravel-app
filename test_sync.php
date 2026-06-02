<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(42);

// Simulate the controller
$newRoleIds = collect([1]); // say they checked role 1
$syncData = $newRoleIds->mapWithKeys(fn ($id) => [
    $id => [
        'assigned_by' => 1,
        'assigned_at' => now(),
    ],
])->all();

var_dump($syncData);

$user->roles()->sync($syncData);

$users = App\Models\User::with('roles')->where('id', 42)->get();
var_dump($users->first()->roles->pluck('id')->toArray());
