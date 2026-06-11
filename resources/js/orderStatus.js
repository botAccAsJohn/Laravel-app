document.addEventListener('DOMContentLoaded', function () {
    const badge = document.getElementById('order-status-badge');
    if (!badge) return;

    const orderId = badge.dataset.orderId;
    if (!orderId) return;

    let echoRetries = 0;
    
    function initOrderStatusEcho() {
        if (window.Echo) {
            console.log('Echo initialized, subscribing to private channel: order.' + orderId);
            window.Echo.private('order.' + orderId)
                .listen('.status.updated', function (data) {
                    console.log('Order status updated:', data);
                    
                    // 1. Update status badge text
                    badge.textContent = data.label;
                    
                    // 2. Update status badge classes
                    const statusClasses = {
                        'pending': 'bg-yellow-100 text-yellow-700 border-yellow-300',
                        'confirmed': 'bg-blue-100 text-blue-700 border-blue-300',
                        'processing': 'bg-indigo-100 text-indigo-700 border-indigo-300',
                        'shipped': 'bg-purple-100 text-purple-700 border-purple-300',
                        'delivered': 'bg-green-100 text-green-700 border-green-300',
                        'cancelled': 'bg-red-100 text-red-700 border-red-300',
                        'refunded': 'bg-gray-100 text-gray-700 border-gray-300'
                    };
                    
                    badge.className = 'transition-all duration-300 inline-flex items-center px-3 py-1 text-sm font-medium border rounded-full ' + 
                        (statusClasses[data.status] || 'bg-gray-100 text-gray-700 border-gray-300');
                });
        } else if (echoRetries < 10) {
            echoRetries++;
            setTimeout(initOrderStatusEcho, 500);
        }
    }

    setTimeout(initOrderStatusEcho, 500);
});
