## Exercise 15.1 – Optimization Commands

### `php artisan config:cache`

- Combines all configuration files into one cached file
- Improves performance by reducing file loading on each request
- `.env` is not loaded at runtime after caching
- `env()` should only be used inside config files

### `php artisan route:cache`

- Creates a cached version of all routes
- Speeds up route registration process
- Useful for applications with many routes

### `php artisan view:cache`

- Compiles all Blade templates in advance
- Avoids compiling views during runtime
- Improves response speed

### `php artisan optimize`

- Runs multiple optimization tasks together
- Caches configuration, routes, views, and events
- Used for preparing application for production

## What breaks if a route uses a closure?

- Route caching fails if any route uses a closure
- Closures cannot be serialized by PHP
- Must replace closures with controller methods
- Otherwise, `route:cache` command will throw an error
