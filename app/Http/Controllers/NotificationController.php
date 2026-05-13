<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    /**
     * Display a listing of all notifications.
     */
    public function index()
    {

        $notifications = auth()->user()->notifications()->paginate(10);
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Display a listing of unread notifications.
     */
    public function unread()
    {

        $notifications = auth()->user()->unreadNotifications()->paginate(10);
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Display the specified notification and redirect.
     */
    public function show(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);

        if (!$notification->read_at) {
            $notification->markAsRead();
            $this->clearUnreadCache();
        }

        // Try to get IDs first to reconstruct a fresh URL (avoids stale absolute URLs from DB)
        $orderId = $notification->data['order_id'] ?? $notification->data['data']['order_id'] ?? null;
        $productId = $notification->data['product_id'] ?? $notification->data['data']['product_id'] ?? null;

        if ($orderId) {
            $url = route('orders.show', $orderId);
        } elseif ($productId) {
            $url = route('products.show', $productId);
        } else {
            // Fallback to the stored URL if no specific ID is found
            $url = $notification->data['url']
                ?? $notification->data['data']['url']
                ?? route('notifications.index');
        }

        return redirect($url);
    }

    /**
     * Mark a specific notification as read.
     */
    public function update(Request $request, string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        $this->clearUnreadCache();

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->clearUnreadCache();

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Helper to clear the cached unread count.
     */
    private function clearUnreadCache(): void
    {
        Cache::forget('unread_count_' . auth()->id());
    }
}
