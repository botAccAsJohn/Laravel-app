import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/products.js",
                "resources/js/cart.js",
                "resources/js/orderStatus.js",
                "resources/js/customer.js",
                "resources/js/browsing.js",
                "resources/js/broadcast.js",
            ],
            refresh: true,
        }),
    ],
});
