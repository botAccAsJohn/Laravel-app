document.addEventListener("DOMContentLoaded", function () {
    let echoRetries = 0;

    function initBroadcastEcho() {
        if (window.Echo) {
            console.log("Echo initialized, subscribing to: admin.orders");
            window.Echo.private("admin.orders").listen(
                ".order.placed",
                function (data) {
                    console.log("New order received:", data);
                    addOrderToFeed(data);

                    if (window.Toastify) {
                        const feed = document.getElementById("orders-feed");
                        const currencySymbol = feed?.dataset.currency || "$";
                        window
                            .Toastify({
                                text: `✨ NEW ORDER RECEIVED\n#${data.orderId} by ${data.customerName}\nValue: ${currencySymbol} ${parseFloat(data.orderTotal).toFixed(2)}`,
                                duration: 8000,
                                gravity: "top",
                                position: "right",
                                stopOnFocus: true,
                                style: {
                                    background:
                                        "linear-gradient(135deg, #1e293b 0%, #0f172a 100%)",
                                    borderRadius: "16px",
                                    borderLeft: "4px solid #6366f1",
                                    padding: "20px 24px",
                                    fontSize: "14px",
                                    fontWeight: "600",
                                    color: "#f8fafc",
                                    whiteSpace: "pre-line",
                                    cursor: "pointer",
                                },
                                onClick: function () {
                                    window.location.href = `/orders/${data.orderId}`;
                                },
                            })
                            .showToast();
                    }
                },
            );
        } else if (echoRetries < 10) {
            echoRetries++;
            setTimeout(initBroadcastEcho, 500);
        }
    }

    function addOrderToFeed(data) {
        const feed = document.getElementById("orders-feed");
        const emptyMsg = document.getElementById("no-orders-msg");
        if (!feed) return;
        if (emptyMsg) emptyMsg.remove();

        const currencySymbol = feed.dataset.currency || "$";

        const item = document.createElement("div");
        item.className =
            "group bg-white border border-gray-100 p-5 rounded-2xl hover:border-indigo-100 hover:bg-gray-50/50 transition-all duration-500 flex justify-between items-center opacity-0 translate-y-[-20px] shadow-sm hover:shadow-md";
        item.innerHTML = `
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center font-bold">
                    #${String(data.orderId).slice(-2)}
                </div>
                <div>
                    <p class="font-black text-gray-900 group-hover:text-indigo-600 transition-colors">${data.customerName}</p>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">${data.itemsCount} Products | Total ${currencySymbol}${data.orderTotal}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-amber-100">Pending</span>
                <a href="/orders/${data.orderId}" class="p-2 bg-gray-50 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        `;
        feed.prepend(item);
        setTimeout(
            () => item.classList.remove("opacity-0", "translate-y-[-20px]"),
            50,
        );
    }

    initBroadcastEcho();
});
