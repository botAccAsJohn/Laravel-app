<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(42);
$user->load('roles.permissions');

var_dump(get_class($user->roles));
var_dump(is_null($user->roles));
