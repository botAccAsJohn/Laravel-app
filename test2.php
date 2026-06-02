<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(42);
$user->roles()->sync([1, 2]);
dump("Synced 1 and 2 to user 42");

$users = App\Models\User::with('roles')->where('id', 42)->get();
dump($users->first()->roles->pluck('id'));
