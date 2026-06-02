# Supervisor Queue Worker Configuration

**Exercise 46.6 — Worker Supervision & Production Deployment**

This document describes the Supervisor configuration for running Laravel queue workers as managed services on production servers.

## Overview

Supervisor is a process manager that keeps queue workers running indefinitely. When a worker crashes or is gracefully restarted (e.g., during deployment), Supervisor automatically restarts it.

**Why Supervisor?**
- Queue workers are long-running PHP processes that would otherwise die on errors.
- Manual restarts are impractical; Supervisor handles this automatically.
- Workers are grouped into "pools" (default, notifications, pdfs, analytics) with different priorities and timeouts.

## Architecture

We have **4 worker pools**, each handling different queue types:

| Pool | Queues | Count | Timeout | Max-Time | Purpose |
|------|--------|-------|---------|----------|---------|
| **default** | realtime, default, slack, webhooks | 4 workers | 90s | 3600s | High-priority broadcast events |
| **notifications** | notifications | 2 workers | 120s | 3600s | Email/SMS/push notifications |
| **pdfs** | pdfs | 1 worker | 180s | 3600s | PDF generation and reports |
| **analytics** | analytics | 1 worker | 240s | 3600s | Data aggregation (lowest priority) |

**Total: 8 workers across 4 pools.**

---

## Installation

### 1. Install Supervisor on the server

```bash
sudo apt update
sudo apt install supervisor
```

### 2. Copy configuration files

```bash
sudo cp deploy/supervisor/*.conf /etc/supervisor/conf.d/
```

### 3. Reload Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
```

### 4. Start all workers

```bash
sudo supervisorctl start all
```

---

## Monitoring & Management

### Check status

```bash
sudo supervisorctl status
```

**Expected output:**
```
laravel-worker-analytics:laravel-worker-analytics_00    RUNNING   pid 12345, uptime 2:34:56
laravel-worker-default:laravel-worker-default_00        RUNNING   pid 12346, uptime 1:23:45
laravel-worker-default:laravel-worker-default_01        RUNNING   pid 12347, uptime 1:23:45
...
```

### Restart a specific worker

```bash
sudo supervisorctl restart laravel-worker-notifications:*
```

### Stop a worker

```bash
sudo supervisorctl stop laravel-worker-pdfs:*
```

### Tail logs in real-time

```bash
tail -f storage/logs/worker-default.log
tail -f storage/logs/worker-notifications.log
```

### Monitor queue depth

```bash
php artisan queue:monitor redis:default,redis:notifications,redis:pdfs,redis:analytics
```

---

## Deployment Workflow

### Before deploying

1. Review the changes being deployed, especially any Job class modifications.
2. Verify all queue workers are running:
   ```bash
   sudo supervisorctl status | grep RUNNING
   ```

### During deployment

The `deploy/deploy.sh` script automatically handles worker restarts:

```bash
bash deploy/deploy.sh
```

**Key steps:**
1. Application goes into maintenance mode
2. Code is pulled and dependencies installed
3. Migrations run
4. Caches are rebuilt
5. **`php artisan queue:restart` is called** ← workers gracefully restart
6. Frontend assets are built
7. Application exits maintenance mode

### What happens when `queue:restart` is called

1. A timestamp is written to Redis cache
2. Each active worker polls this cache key after each job
3. When a worker detects a newer timestamp, it:
   - Finishes its current job gracefully
   - Exits cleanly
   - Supervisor automatically restarts it with the new code

**Result:** Zero-downtime worker restart. Jobs are never dropped; the queue is never paused.

---

## Worker Recycling (Important!)

Each worker has two recycling limits:

| Option | Default | Reason |
|--------|---------|--------|
| `--max-time=3600` | 1 hour | Prevents memory fragmentation from accumulating |
| `--max-jobs=500` (notifications) | Per-pool | Some jobs allocate large data (CSV exports, images) |

**Why recycle?**
- PHP is a long-running process when used as a queue worker
- Static singletons, cached class maps, open sockets, and memory fragmentation accumulate
- The 1-hour ceiling keeps each process in a predictable, tested state
- Prevents a single worker from consuming gigabytes of RAM over time

---

## Configuration File Structure

Each `laravel-worker-*.conf` file defines:

### Identity
```ini
[program:laravel-worker-default]
process_name = %(program_name)s_%(process_num)02d  ; laravel-worker-default_00, _01, etc.
user = forge                                         ; Non-root user
```

### Command
```ini
command = php /var/www/html/artisan queue:work redis \
    --queue=realtime,default,slack,webhooks \
    --sleep=3 \                    ; Poll interval (seconds)
    --tries=3 \                    ; Max retry attempts per job
    --backoff=10 \                 ; Delay before retry (seconds)
    --timeout=90 \                 ; Max execution time per job
    --max-time=3600 \              ; Worker restarts after 1 hour
    --max-jobs=1000                ; Worker restarts after 1000 jobs
```

### Scaling & Restart
```ini
numprocs = 4                 ; Run 4 parallel processes
autostart = true             ; Start automatically when Supervisor starts
autorestart = true           ; Restart if the process dies
startretries = 5             ; Retry up to 5 times on startup failure
stopwaitsecs = 90            ; Wait 90s for graceful shutdown before SIGKILL
```

### Logging
```ini
stdout_logfile = /var/www/html/storage/logs/worker-default.log
stdout_logfile_maxbytes = 50MB    ; Rotate when file exceeds 50 MB
stdout_logfile_backups = 5        ; Keep 5 rotated logs
```

---

## Queue Priority

Workers check queues in order. The **default** pool prioritizes:

```bash
--queue=realtime,default,slack,webhooks
```

This means:
1. Realtime broadcasts drain first (sub-second latency for customers)
2. Then general work (invoices, imports)
3. Then Slack notifications
4. Finally webhooks

Other pools handle non-critical work independently.

---

## Troubleshooting

### A worker keeps restarting

**Symptom:** `BACKOFF` status in supervisorctl; logs show repeated crashes.

**Cause:** Job class has a fatal error or infinite loop.

**Fix:**
1. Check logs: `tail -f storage/logs/worker-*.log`
2. Fix the offending Job class
3. Deploy (queue:restart will pick up the fix)

### Jobs are piling up in a queue

**Symptom:** `php artisan queue:monitor` shows growing queue depth.

**Cause:** Workers are too slow or there aren't enough.

**Fix:**
```bash
# Increase pool size for that queue
# Edit deploy/supervisor/laravel-worker-*.conf
# Change: numprocs = 2  →  numprocs = 4

sudo supervisorctl reread
sudo supervisorctl update
```

### Graceful shutdown isn't working

**Symptom:** Workers still killing jobs when you call `sudo supervisorctl stop`.

**Cause:** `stopwaitsecs` is too short for your longest-running jobs.

**Fix:**
```ini
stopwaitsecs = 300  ; Increase from 120s to 300s
```

Then reload:
```bash
sudo supervisorctl reread
sudo supervisorctl update
```

---

## Alerts & Monitoring

### Set up alerts for failed workers

Monitor the security log channel for failed jobs:

```bash
# Manual check
php artisan queue:failed

# Daily digest to Slack (scheduled at 08:00)
php artisan schedule:work
# Runs: php artisan jobs:failed-digest
```

### Monitor memory usage

```bash
# Check which workers are using most memory
ps aux | grep 'queue:work' | sort -k4 -n
```

If a worker exceeds 500MB, something is leaking. Check the job code.

### Monitor queue depth

```bash
# Set up a cron job to monitor
*/5 * * * * php /var/www/html/artisan queue:monitor redis:default >> /var/log/queue-monitor.log
```

---

## Example: Adding a New Queue

If you add a new queue type (e.g., `sms`), follow these steps:

### 1. Create a new worker pool config

```bash
cp deploy/supervisor/laravel-worker-notifications.conf \
   deploy/supervisor/laravel-worker-sms.conf
```

### 2. Edit the new config

```ini
[program:laravel-worker-sms]
process_name = %(program_name)s_%(process_num)02d
user = forge

command = php /var/www/html/artisan queue:work redis \
    --queue=sms \
    --sleep=3 \
    --tries=2 \
    --timeout=60 \
    --max-time=3600 \
    --max-jobs=500

numprocs = 1
priority = 250  ; Between notifications (200) and pdfs (300)

...
```

### 3. Deploy

```bash
# Copy to server
sudo cp deploy/supervisor/laravel-worker-sms.conf /etc/supervisor/conf.d/

# Reload
sudo supervisorctl reread
sudo supervisorctl update

# Start
sudo supervisorctl start laravel-worker-sms:*
```

---

## Testing Locally

For local development, you can run workers manually without Supervisor:

```bash
# Single worker, all queues
php artisan queue:work redis --queue=default,notifications,pdfs

# Or use the sync driver to process jobs immediately
php artisan queue:work sync
```

---

## References

- [Laravel Queue Documentation](https://laravel.com/docs/11.x/queues)
- [Supervisor Documentation](http://supervisord.org/introduction.html)
- [Exercise 46.6 — Worker Supervision](../../VALIDATION_REPORT.md#module-46---queues)

---

**Created:** 2026-05-30  
**Last Updated:** 2026-05-30  
**Maintainer:** DevOps Team
