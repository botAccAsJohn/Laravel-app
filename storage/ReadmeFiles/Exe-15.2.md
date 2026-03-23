## Exercise 15.2 – Storage Linking

### `php artisan storage:link`

- Creates a symbolic link from `public/storage` to `storage/app/public`
- Allows files stored in Laravel’s storage folder to be publicly accessible
- Makes it possible to serve uploaded files through the browser

### Upload file and access publicly

- Files should be stored in `storage/app/public` using Laravel’s file storage system
- After running the command, those files become accessible via `public/storage`
- Example:
    - File path → `storage/app/public/image.jpg`
    - Public URL → `http://your-domain.com/storage/image.jpg`

- Commonly used for user uploads like images, documents, etc.

### Why it is important

- Keeps uploaded files organized and secure
- Separates private and public storage
- Ensures proper file access without exposing the entire storage directory
