<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(42);
$user->roles()->sync([1]); // Assign role 1

$request = Illuminate\Http\Request::create("/admin/users", 'GET');
$response = app()->handle($request);
$content = $response->getContent();

if (strpos($content, 'Manager') !== false || strpos($content, 'Admin') !== false || strpos($content, 'Support') !== false) {
    echo "Role found in response\n";
} else {
    echo "No roles found in response\n";
}

$user->roles()->sync([]); // remove roles
$request = Illuminate\Http\Request::create("/admin/users", 'GET');
$response = app()->handle($request);
$content = $response->getContent();

if (strpos($content, 'No roles') !== false) {
    echo "Correctly showed no roles\n";
} else {
    echo "Did not show no roles\n";
}
