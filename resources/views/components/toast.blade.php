@auth
@php
$isAdmin = Auth::user()->role === 'admin';
$userId = Auth::id();
@endphp

<!-- Universal Toast Notifications (Real-Time) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script type="module">
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            if (!window.Echo) return;

            const isAdmin = {{ $isAdmin ? 'true' : 'false' }};
            const userId = '{{ $userId }}';

            if (isAdmin) {
                // Admin: Listen across the platform
                window.Echo.private('admin.orders')
                    .listen('.order.placed', function(data) {
                        // Universal toast for Admin
                        Toastify({
                            text: `✨ NEW ORDER RECEIVED\n#${data.orderId} by ${data.customerName}\nValue: ₨ ${parseFloat(data.orderTotal).toFixed(2)}`,
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
                                boxShadow: "0 10px 15px -3px rgba(0,0,0,0.1)",
                                cursor: "pointer"
                            },
                            onClick: function() {
                                window.location.href = `/orders/${data.orderId}`;
                            }
                        }).showToast();
                    });
            } else {
                // Customer: Listen on any page
                window.Echo.private('App.Models.User.' + userId)
                    .listen('.status.updated', function(data) {
                        // Customer Dashboard/Show handles its own specific badge UI, but we still trigger the toast globally!
                        // Removed the exclusionary if statements so this is truly a Universal Toast module.

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
                                boxShadow: "0 10px 15px -3px rgba(0,0,0,0.1)",
                                cursor: "pointer"
                            },
                            onClick: function() {
                                window.location.href = `/orders/${data.order_id}`;
                            }
                        }).showToast();
                    });
            }
        }, 500);
    });
</script>
@endauth