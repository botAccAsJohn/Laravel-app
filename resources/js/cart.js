document.addEventListener('DOMContentLoaded', function () {
    const actionSection = document.getElementById('product-action-section');
    if (!actionSection) return;

    const productId = actionSection.dataset.productId;
    const isActive = actionSection.dataset.isActive === 'true';
    let echoRetries = 0;
    
    function initStockEcho() {
        if (window.Echo) {
            console.log('Echo initialized, subscribing to: product.' + productId);
            window.Echo.channel('product.' + productId)
                .listen('.stock.changed', function (e) {
                    const newStock = e.new_stock;
                    const topBadge = document.getElementById('top-stock-badge');
                    const qtyInput = document.getElementById('qty');
                    const cartWrapper = document.getElementById('add-to-cart-wrapper');
                    const outOfStockWrapper = document.getElementById('out-of-stock-wrapper');
                    const outOfStockText = document.getElementById('unavailable-text');
                    
                    if (newStock > 0) {
                        if (topBadge) {
                            topBadge.innerHTML = `<span class="text-xl font-bold text-green-600">In Stock</span>`;
                        }
                        
                        if (qtyInput) {
                            qtyInput.max = newStock;
                            if (parseInt(qtyInput.value) > newStock) {
                                qtyInput.value = newStock;
                            }
                        }
                        
                        if (isActive) {
                            if (cartWrapper) {
                                cartWrapper.classList.remove('hidden');
                                cartWrapper.classList.add('block');
                            }
                            if (outOfStockWrapper) {
                                outOfStockWrapper.classList.remove('block');
                                outOfStockWrapper.classList.add('hidden');
                            }
                        }
                    } else {
                        if (topBadge) {
                            topBadge.innerHTML = `<span class="text-xl font-bold text-red-500">Currently unavailable</span>`;
                        }
                        
                        if (outOfStockText) {
                            outOfStockText.textContent = 'Currently out of stock.';
                        }
                        
                        if (cartWrapper) {
                            cartWrapper.classList.remove('block');
                            cartWrapper.classList.add('hidden');
                        }
                        if (outOfStockWrapper) {
                            outOfStockWrapper.classList.remove('hidden');
                            outOfStockWrapper.classList.add('block');
                        }
                    }
                });
        } else if (echoRetries < 10) {
            echoRetries++;
            setTimeout(initStockEcho, 500);
        }
    }

    initStockEcho();
});
