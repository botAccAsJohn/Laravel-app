# 🛡️ SECURITY & PROJECT ROADMAP

---

## 🚨 SECURITY AUDIT & HARDENING (CRITICAL)

### Public Route Protection

- [ ] **Protect Admin Exports**: Move `Route::get('/export-products', ...)` inside the `auth` and `role:admin` middleware groups in `web.php`.
- [ ] **Secure Test Routes**: Protect or remove `Route::get('/test-slack', ...)` in `web.php`.
- [ ] **Secure API Endpoints**: Apply authentication middleware (e.g., `auth:sanctum`) to all routes in `api.php`, especially `/downloadInvoice` and `/external-users`.

### CSRF & Middleware Hardening

- [ ] **Restore CSRF Protection**: Remove `/products` and `/products/*` from the `validateCsrfTokens` exception list in `bootstrap/app.php`.
- [ ] **Restrict Log Access**: Move the `/logs` route in `web.php` into the `role:admin` middleware group to prevent non-admin users from viewing system logs.
- [ ] **Global Security Headers**: Ensure `ReqContextMiddleware` or a new middleware sets appropriate security headers (X-Frame-Options, etc.).

### Authorization & Logic Refactoring

- [ ] **Implement OrderPolicy**: Create `app/Policies/OrderPolicy.php` and define `view`, `update`, and `delete` permissions.
- [ ] **Refactor OrderController**: Replace all manual `$user->role === 'admin'` checks with `$this->authorize()` calls.
- [ ] **Standardize Admin Checks**: Use the `role:admin` middleware consistently across all controllers instead of mixed manual checks.
- [ ] **Fix API Placeholders**: In `api.php`, replace hardcoded IDs (like `Order::find(1)`) with proper route model binding or authenticated user context.

### Scalability & Best Practices

- [ ] **Role/Permission Package**: Evaluate and potentially integrate `spatie/laravel-permission` for more granular control as the app grows.
- [ ] **Audit Logging**: Implement a system to log sensitive admin actions (deleting products, exporting data).
- [ ] **Email Verification**: Ensure all sensitive "write" routes require the `verified` middleware.

---

## ⚠️ REMAINING & INCOMPLETE FEATURES

### CRITICAL - MUST COMPLETE:

1. **Product Image Cache Invalidation (Module 28)**
    - Issue: When product image is updated, the product page cache doesn't refresh
    - Fix: Add cache invalidation in ProductService::update() when image changes
2. **OrderPlaced Event Broadcasting (Module 27)**
    - Required: Implement `ShouldBroadcast` interface for `OrderPlaced` event.
3. **Real-time Admin Notifications (Module 27)**
    - Missing: Toast notification and dashboard display when order is placed.
4. **Scheduled Tasks (Module 26.4)**
    - Missing: Abandoned cart reminders, birthday emails, status updates, and log cleanup.
5. **Presence Channel (Module 27.5)**
    - Missing: Online customers tracker and admin tracker page.
6. **Concurrency Features (Module 30)**
    - Missing: `Concurrency::run()` and `defer()` for post-checkout tasks.
7. **Signed Routes (Module 33.4)**
    - Missing: Temporary signed routes for invoices with 10-minute expiry.
8. **RTL Language Support (Module 36.4)**
    - Missing: Conditional RTL CSS loading for Arabic/Hindi.

### HIGH PRIORITY:

9. **Coupon Code System**: Create commands and service logic for managing coupons.
10. **Invoice Ownership Check**: Verify ownership and file existence in `OrderController@invoice`.
11. **Translations**: Add `lang/ar/validation.php` and `lang/hi/validation.php`.
12. **Collection Filtering**: Verify all product filters (price, category, stock) are working.

---

## ✅ COMPLETED FEATURES

- ✅ Module 26: Artisan Commands (4 commands)
- ✅ Module 27: Broadcasting Events (OrderStatusUpdated, ProductStockChanged)
- ✅ Module 28: Cache (remember, tags)
- ✅ Module 29: Collections (ProductCollection)
- ✅ Module 31: Context (ReqContextMiddleware)
- ✅ Module 32: Events & Observers
- ✅ Module 33: File Storage (Images, invoices)
- ✅ Module 34: Helpers (format_price, etc.)
- ✅ Module 35: HTTP Client (ExternalApiService)
- ✅ Module 36: Localization (SetLocale)

---

## 🔧 NOTES FOR IMPLEMENTATION:

- Activate `broadcast(new OrderPlaced($order))->toOthers();` in `OrderController`.
- Update `BROADCAST_CONNECTION` to `pusher` or `reverb` in production.
- Ensure Cache driver is set to `redis` or `memcached` for cache tags support.

---

## ⚠️ ADDITIONAL REMAINING EXERCISES (MODULES 1-36)

- [ ] **Exercise 3.1**: Create `DIRECTORY_STRUCTURE.md` or update `DOCS.md` with a detailed project layout.
- [ ] **Exercise 15.1**: Document and verify optimization commands (`optimize`, `config:cache`, etc.).
- [ ] **Exercise 15.2**: Verify and document `php artisan storage:link` setup.
- [ ] **Exercise 33.5**: Expand `ReportManagerController` into a full-featured **Admin File Manager**.
- [ ] **Exercise 36.3**: Implement pluralisation logic in language files (e.g., `hi.json`).

