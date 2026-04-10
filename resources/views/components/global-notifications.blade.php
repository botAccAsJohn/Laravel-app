@auth
@php
$isAdmin = Auth::user()->role === 'admin';
$userId = Auth::id();
@endphp

<!-- Global Real-Time Notifications -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script type="module">
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            if (!window.Echo) return;

            const isAdmin = {
                {
                    $isAdmin ? 'true' : 'false'
                }
            };
            const userId = '{{ $userId }}';

            if (isAdmin) {
                // Admin: Listen for all new orders across the platform
                window.Echo.private('admin.orders')
                    .listen('.order.placed', function(data) {
                        // Exclude the dashboard since it handles its own feed and toasts
                        if (!window.location.pathname.includes('/dashboard')) {
                            Toastify({
                                text: `✨ NEW ORDER RECEIVED\n#${data.orderId} by ${data.customerName}\nValue: Rs. ${parseFloat(data.orderTotal).toFixed(2)}`,
                                duration: 8000,
                                gravity: 'top',
                                position: 'right',
                                stopOnFocus: true,
                                style: {
                                    background: "linear-gradient(135deg, #1e293b 0%, #0f172a 100%)",
                                    borderRadius: "16px",
                                    borderLeft: "4px solid #6366f1",
                                    padding: "20px 24px",
                                    fontSize: "14px",
                                    fontWeight: "600",
                                    color: "#f8fafc",
                                    whiteSpace: "pre-line",
                                    cursor: "pointer"
                                },
                                onClick: function() {
                                    window.location.href = `/orders/${data.orderId}`;
                                }
                            }).showToast();
                        }
                    });
            } else {
                // Customer: Listen for status updates on any of their orders
                window.Echo.private('App.Models.User.' + userId)
                    .listen('.status.updated', function(data) {
                        // Exclude the specific order show page because it handles the toast AND badge updates locally
                        const isOnSpecificOrderPage = window.location.pathname === `/orders/${data.order_id}`;

                        if (!isOnSpecificOrderPage) {
                            Toastify({
                                text: `🔔 Order Update\nYour Order #${data.order_id} is now: ${data.label}`,
                                duration: 8000,
                                gravity: 'top',
                                position: 'right',
                                style: {
                                    background: "linear-gradient(135deg, #10b981 0%, #059669 100%)",
                                    borderRadius: "16px",
                                    borderLeft: "4px solid #fff",
                                    padding: "20px 24px",
                                    fontSize: "14px",
                                    fontWeight: "600",
                                    color: "#fff",
                                    whiteSpace: "pre-line",
                                    cursor: "pointer"
                                },
                                onClick: function() {
                                    window.location.href = `/orders/${data.order_id}`;
                                }
                            }).showToast();
                        }
                    });
            }
        }, 500);
    });
</script>
@endauth