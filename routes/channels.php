<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Order;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('admin.orders', function ($user) {
    return $user instanceof \App\Models\Admin;
}, ['guards' => ['admin']]);

Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    $order = \Illuminate\Support\Facades\Cache::remember("orders:id:{$orderId}", now()->addMinutes(30), function () use ($orderId) {
        return Order::find($orderId);
    });
    return $order && (int) $order->user_id === (int) $user->id;
}, ['guards' => ['web']]);

Broadcast::channel('store.browsing', function ($user) {
    // We can extract the current page from the referer header 
    // since the auth request is sent from the browser while on the page.
    $fullPath = request()->header('referer');
    $path = $fullPath ? parse_url($fullPath, PHP_URL_PATH) : 'Main Store';
    
    return [
        'id' => $user->id, 
        'name' => $user->name,
        'path' => $path ?: '/'
    ];
}, ['guards' => ['web', 'admin']]);