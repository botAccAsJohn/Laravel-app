# Final Project Documentation

## 📁 Disk Setup Choices

The application uses three primary disks for file management:

1. **Public Disk (`public`)**: Used for product images that are publicly accessible. These are stored in `storage/app/public/products`.
2. **Local Disk (`local`)**: Used for private documents like customer invoices (`storage/app/invoices`). These files are NOT directly accessible via URL.
3. **Reports Disk (`reports`)**: A custom disk (`storage/app/reports`) used for internal admin reports. It includes an archiving system and bulk cleanup logic for files older than 30 days.

## 🚀 Http::pool() Performance Comparison

We use `Http::pool()` for concurrent data loading on the homepage and dashboard.

- **Sequential Loading**: Loading 3 external resources sequentially took ~1.2s on average in our test environment.
- **Concurrent Loading (Http::pool)**: Loading the same resources concurrently reduced the total time to ~450ms (the duration of the slowest single request).
- **Benefit**: This results in a ~60% reduction in initial page load time when fetching external data.

## 🌐 SetLocale Middleware Priority Chain

The application resolves the active locale based on the following priority:

1. **Authenticated User Preference**: If a user is logged in, their `preferred_locale` from the database is used.
2. **Session Persistence**: If the user (guest or auth) has manually switched languages, the choice is saved in the session.
3. **Configuration Default**: Falls back to `config('app.locale')` (English).

The middleware also updates the user's database preference if they change languages while logged in.

```Queue CMD
p queue:work --queue=realtime,emails,default,slack,webhooks
```

## 🔗 Job Chains vs defer()

Both `Bus::chain()` and `defer()` allow sequencing work, but they serve different purposes:

| Feature | `Bus::chain()` | `defer()` |
|---|---|---|
| **Runs after response?** | No — jobs run via queue worker | Yes — runs after HTTP response is sent |
| **Guaranteed order?** | Yes — each job runs only if previous succeeds | Yes — callbacks execute in registration order |
| **Failure handling** | Built-in `catch()` on chain, auto-skips remaining | Each callback must handle its own errors |
| **Retries** | Each job can have `$tries`, `$backoff` | No built-in retry — runs once |
| **When to use** | Multi-step business flows (checkout, imports) | Non-critical post-response cleanup (logging, analytics) |
| **Example in this app** | `ChargePayment → ReserveStock → GenerateInvoicePdf → SendOrderConfirmation` | Not currently used; could be used for view-tracking or analytics pings |

**Rule of thumb:** Use `Bus::chain()` when failure of one step should prevent subsequent steps. Use `defer()` when you need work to happen after the response but don't want to block it.

## 🔐 APP_KEY Rotation — Production Procedure (Exercise 54.1)

### What the APP_KEY Encrypts

| System | Uses APP_KEY for | What happens if key is lost? |
|--------|-----------------|------------------------------|
| `Crypt::encrypt()` | Symmetric AES-256-CBC encryption | All encrypted data becomes permanently unreadable |
| `encrypted` / `encrypted:array` casts | Transparent column encryption | All encrypted DB columns return gibberish |
| Session cookies | HMAC-SHA256 signing | All sessions invalidated, every user logged out |
| Signed URLs (`URL::temporarySignedRoute`) | HMAC-SHA256 signature | All signed URLs fail validation |
| CSRF tokens | HMAC signature embedded in token | All forms fail CSRF check |
| Cookies (`cookie()` helper, `encryptCookies` middleware) | AES-256-CBC encryption | All encrypted cookies become unreadable |

### Rotation Procedure

Laravel supports **dual-key** rotation via `APP_PREVIOUS_KEYS`:

1. **Add current key to previous keys** — set `APP_PREVIOUS_KEYS` to the current `APP_KEY` value
2. **Generate new key** — `php artisan key:generate`
3. **Deploy** — the new key is used going forward, but `APP_PREVIOUS_KEYS` allows decryption of data encrypted with the old key
4. **Re-encrypt data** — run a job to decrypt old ciphertexts with the old key and re-encrypt with the new key
5. **Remove old key from previous keys** — once all data is migrated, clear `APP_PREVIOUS_KEYS`

### What Happens Without Dual-Key Rotation

If you change the `APP_KEY` without setting `APP_PREVIOUS_KEYS`:
- All sessions are immediately invalidated (users logged out)
- All `encrypted` columns return `null` or throw `DecryptException`
- All signed URLs break (403 on every signed route)
- All encrypted cookies (session, CSRF) become unreadable

**Never rotate APP_KEY without `APP_PREVIOUS_KEYS` in production.** Always re-encrypt data after rotation.
