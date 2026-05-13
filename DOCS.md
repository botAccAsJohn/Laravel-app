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
p queue:work --queue=emails,default
```
