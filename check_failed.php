<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = \App\Models\Order::with('user', 'items')->orderByDesc('placed_at')->first();
$admin = \App\Models\User::where('role','admin')->first();

echo "=== Testing ALL toSlack() notifications ===\n\n";

// 1. NewOrderReceived
try {
    $n = new \App\Notifications\NewOrderReceived($order);
    $msg = $n->toSlack($admin);
    $arr = $msg->toArray();
    echo "[OK] NewOrderReceived::toSlack() - " . count($arr['blocks']) . " blocks\n";
} catch (\Throwable $e) {
    echo "[FAIL] NewOrderReceived::toSlack(): " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 2. OrderShipped
try {
    $n = new \App\Notifications\OrderShipped($order);
    $msg = $n->toSlack($admin);
    $arr = $msg->toArray();
    echo "[OK] OrderShipped::toSlack() - " . count($arr['blocks']) . " blocks\n";
} catch (\Throwable $e) {
    echo "[FAIL] OrderShipped::toSlack(): " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 3. ProductLowStock
$product = \App\Models\Product::first();
if ($product) {
    try {
        $n = new \App\Notifications\ProductLowStock($product);
        $msg = $n->toSlack($admin);
        $arr = $msg->toArray();
        echo "[OK] ProductLowStock::toSlack() - " . count($arr['blocks']) . " blocks\n";
    } catch (\Throwable $e) {
        echo "[FAIL] ProductLowStock::toSlack(): " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

// 4. ServerAlertNotification
try {
    $e = new \RuntimeException('Test exception');
    $n = \App\Notifications\ServerAlertNotification::fromException($e);
    $msg = $n->toSlack($admin);
    $arr = $msg->toArray();
    echo "[OK] ServerAlertNotification::toSlack() - " . count($arr['blocks']) . " blocks\n";
} catch (\Throwable $e) {
    echo "[FAIL] ServerAlertNotification::toSlack(): " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 5. Check routing
echo "\n=== Testing Slack Route for Admin ===\n";
$testNotification = new \App\Notifications\NewOrderReceived($order);
$route = $admin->routeNotificationForSlack($testNotification);
echo "NewOrderReceived route: " . (is_string($route) ? substr($route, 0, 40) . '...' : json_encode($route)) . "\n";

$testNotification2 = new \App\Notifications\ProductLowStock(\App\Models\Product::first());
$route2 = $admin->routeNotificationForSlack($testNotification2);
echo "ProductLowStock route: " . (is_string($route2) ? substr($route2, 0, 40) . '...' : json_encode($route2)) . "\n";

echo "\n=== Testing OrderShipped::toMail() ===\n";
try {
    $n = new \App\Notifications\OrderShipped($order);
    $mail = $n->toMail($admin);
    echo "[OK] OrderShipped::toMail() succeeded\n";
} catch (\Throwable $e) {
    echo "[FAIL] OrderShipped::toMail(): " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nDone!\n";
