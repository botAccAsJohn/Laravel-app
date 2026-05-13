import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});

const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
const userRole = document.querySelector('meta[name="user-role"]')?.getAttribute('content');

if (userId) {
    window.Echo.private(`App.Models.User.${userId}`)
        .notification((notification) => {
            console.log("User Notification:", notification);
            const type = notification.type || "info";
            const title = notification.title || "New notification";
            const message = notification.message || "";
            window.notify(type, title, message, 6000);
            bumpBadge();
        });

    window.Echo.private('admin.orders')
        .listen('.order.placed', (e) => {
            // Align with NewOrderReceived toBroadcast payload
            window.notify('success', e.title || 'New Order!', e.message || `Order #${e.orderId} by ${e.customerName}`, 10000);
        })
        .listen('.status.updated', (e) => {
            window.notify('info', 'Status Updated', `Order #${e.order_id} is now ${e.label}`, 6000);
        });
}