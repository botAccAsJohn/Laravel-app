<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(42);
$user->roles()->sync([1, 2]);

$users = App\Models\User::with('roles')->where('id', 42)->paginate(20);
$roles = App\Models\Role::with('permissions')->orderBy('display_name')->get();

$view = view('admin.users.index', compact('users', 'roles'))->render();
if (strpos($view, 'No roles') !== false) {
    echo "NO ROLES FOUND IN VIEW\n";
} else {
    echo "ROLES RENDERED IN VIEW\n";
    // extract the part that renders the roles
    preg_match('/<td class="px-6 py-4">(.*?)<\/td>/s', $view, $matches);
    echo trim(strip_tags($matches[1])) . "\n";
}
