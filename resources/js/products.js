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
