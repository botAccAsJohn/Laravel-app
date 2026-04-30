# Laravel Storage Disks: Local vs Public

---

## 🔹 1. Local Disk

### ✅ Definition

The local disk is used for storing files that should **NOT be publicly accessible**.

### 📁 Storage Location

storage/app/private (or storage/app)

### 🔐 Access

- Only via backend (PHP / Laravel code)
- Not accessible directly via browser

### 📌 Example Use Cases

- User documents (private)
- Logs
- Reports
- Sensitive files

### 🧠 Key Behavior

- Files are stored relative to the defined root directory
- Cannot be accessed via URL
- Requires controller logic to download/view

> Laravel docs confirm that local storage is tied to a root directory and accessed programmatically

---

## 🔹 2. Public Disk

### ✅ Definition

The public disk is used for storing files that should be **accessible via browser (URL)**.

### 📁 Storage Location

storage/app/public

### 🌐 Exposed Via

public/storage (symbolic link)

### 🔗 Accessible URL Format

http://your-app.com/storage/file.jpg

---

### ⚙️ Important Setup

Run the following command:

```bash
php artisan storage:link
```

This creates:

public/storage → storage/app/public

Without this, files won’t be accessible publicly.

📌 Example Use Cases
Images (profile pictures, product images)
Videos
PDFs for download
Static assets

Public disk is intended for files that must be served directly to users

| Feature              | Local Disk                     | Public Disk               |
| -------------------- | ------------------------------ | ------------------------- |
| Purpose              | Private storage                | Public file access        |
| Accessibility        | Only via backend (code)        | Accessible via URL        |
| Storage Path         | storage/app/private            | storage/app/public        |
| Browser Access       | ❌ No                          | ✅ Yes                    |
| Symbolic Link Needed | ❌ No                          | ✅ Yes (`storage:link`)   |
| Security             | High (protected)               | Lower (public exposure)   |
| Typical Use Cases    | Logs, reports, sensitive files | Images, downloads, assets |
| Driver Used          | local                          | local                     |
| Visibility           | Private                        | Public                    |

---

## 🔹 3. Custom Storage Disks in config/filesystems.php

```php
        'reports' => [
            'driver' => 'local',
            'root' => storage_path('app/reports'),
            'throw' => false,
        ],
```
