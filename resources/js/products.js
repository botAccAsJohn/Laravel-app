console.log("products.js loaded");

function logAction(action, productId) {
    const timestamp = new Date().toLocaleTimeString();
    console.log(`[${timestamp}] Product #${productId} → ${action}`);
}

// Attach click handlers to every button with data-product-id
document.addEventListener("DOMContentLoaded", () => {
    // View buttons
    document.querySelectorAll('[data-action="view"]').forEach((btn) => {
        btn.addEventListener("click", () => {
            logAction("viewed", btn.dataset.productId);
        });
    });

    // Delete buttons — also ask for confirmation
    document.querySelectorAll('[data-action="delete"]').forEach((btn) => {
        btn.addEventListener("click", (e) => {
            logAction("deleted", btn.dataset.productId);
        });
    });

    // Form submission
    const form = document.querySelector("#product-form");
    if (form) {
        form.addEventListener("submit", () => {
            logAction("submitted", "new");
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // 1. Find all add-to-cart forms on the page
    const cartForms = document.querySelectorAll('.add-to-cart-form');

    cartForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            // 2. Prevent the default HTML form submission (stops the page reload)
            e.preventDefault();

            const submitButton = form.querySelector('button[type="submit"]');
            const btnText = submitButton.querySelector('.btn-text');
            const originalText = btnText.innerHTML;

            // 3. Provide visual feedback to the user
            submitButton.disabled = true;
            btnText.innerHTML = 'Adding...';
            submitButton.classList.add('opacity-75', 'cursor-not-allowed');

            try {
                // 4. Send the AJAX request
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form), // Automatically grabs the @csrf token
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest', // Tells Laravel it is an AJAX request
                        'Accept': 'application/json'          // Expect a JSON response back
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    // Success! 
                    btnText.innerHTML = 'Added ✓';
                    
                    // You could also trigger a Toast notification or update a cart counter in the navbar here
                    // e.g., updateCartCounter(data.cart_total_items);

                } else {
                    // Handle Validation or Server Errors
                    console.error('Error:', data.message);
                    btnText.innerHTML = 'Error';
                }
            } catch (error) {
                console.error('Network Error:', error);
                btnText.innerHTML = 'Failed';
            } finally {
                // 5. Reset the button after 2 seconds
                setTimeout(() => {
                    btnText.innerHTML = originalText;
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-75', 'cursor-not-allowed');
                }, 2000);
            }
        });
    });
});