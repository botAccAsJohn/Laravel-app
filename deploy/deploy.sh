#!/usr/bin/env bash
# =============================================================================
# deploy.sh — Production deployment script
# Exercise 46.6: Worker Supervision
#
# Usage:
#   bash deploy/deploy.sh
#
# What this script does
# ─────────────────────
# 1.  Enter maintenance mode  (users see the 503 page)
# 2.  Pull latest code
# 3.  Install PHP dependencies
# 4.  Run database migrations
# 5.  Rebuild all Laravel caches (config, route, view, event)
# 6.  Build frontend assets
# 7.  *** queue:restart ***  — tells every running worker to stop after its
#     current job and restart with the new codebase in memory
# 8.  Sync Supervisor configs if the pool definitions changed
# 9.  Exit maintenance mode
#
# WHY queue:restart is mandatory after every deploy
# ─────────────────────────────────────────────────
# A queue worker process loads your application classes into PHP memory at
# startup.  When you deploy new code the worker is still running the OLD
# bytecode. If the new release changed a Job class, a Service, or any class
# the Job depends on, the running worker will execute the new code path with
# stale class definitions already in memory — leading to subtle, hard-to-
# reproduce bugs or fatal errors.
#
# queue:restart writes a timestamp to the cache backend (Redis/database).
# Each worker polls this key after every job. When it detects a change it
# finishes its current job gracefully, exits, and Supervisor immediately
# restarts it — picking up the new codebase from disk.
#
# This is a zero-downtime restart: jobs are never dropped, the queue is never
# paused, and the restart happens worker-by-worker not all at once.
# =============================================================================

set -euo pipefail

APP_DIR="/var/www/html"
PHP="php"
ARTISAN="${PHP} ${APP_DIR}/artisan"
SUPERVISOR_CONF_SRC="${APP_DIR}/deploy/supervisor"
SUPERVISOR_CONF_DST="/etc/supervisor/conf.d"

# Pretty-print helper
step() { echo; echo "──── $1 ────"; }

# ── 1. Maintenance mode ───────────────────────────────────────────────────────
step "Entering maintenance mode"
${ARTISAN} down --retry=5 --secret="$(openssl rand -hex 12)" || true
# --secret generates a bypass token so you can verify the new release while
# the site is down.  Printed to stdout for the deployer to use.

# ── 2. Pull code ──────────────────────────────────────────────────────────────
step "Pulling latest code from main"
git -C "${APP_DIR}" pull origin main

# ── 3. PHP dependencies ───────────────────────────────────────────────────────
step "Installing Composer dependencies"
composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-dev \
    --working-dir="${APP_DIR}"

# ── 4. Database migrations ────────────────────────────────────────────────────
step "Running database migrations"
${ARTISAN} migrate --force

# ── 5. Rebuild Laravel caches ─────────────────────────────────────────────────
step "Rebuilding application caches"
${ARTISAN} config:cache   # Merges all config files → bootstrap/cache/config.php
${ARTISAN} route:cache    # Pre-compiles route list  → bootstrap/cache/routes-v7.php
${ARTISAN} view:cache     # Pre-compiles Blade views → storage/framework/views/
${ARTISAN} event:cache    # Pre-compiles event/listener map

# ── 6. Frontend assets ────────────────────────────────────────────────────────
step "Building frontend assets"
cd "${APP_DIR}"
npm ci --omit=dev
npm run build

# ── 7. Gracefully restart queue workers ───────────────────────────────────────
# This is the critical step after every deploy.
# queue:restart writes a "restart signal" (a Unix timestamp) to the cache.
# Every active worker reads this key after each job.  When the timestamp
# changes the worker finishes its current job, exits cleanly, and Supervisor
# restarts it loading the new code from disk.
#
# If Supervisor is not running (e.g. local dev), this step is still safe —
# artisan simply writes the cache key and exits with 0.
step "Signalling queue workers to gracefully restart"
${ARTISAN} queue:restart
echo "  Workers will restart after their current job finishes."
echo "  Monitor: sudo supervisorctl status"

# ── 8. Sync Supervisor pool configs ───────────────────────────────────────────
# Only runs if the conf files actually changed (diff exits 1 → rsync runs).
# supervisorctl reread    → Supervisor reads the new .conf files.
# supervisorctl update    → Starts/stops pools whose config changed.
#   • New [program:…] blocks are STARTED.
#   • Removed blocks are STOPPED and removed.
#   • Changed blocks are RESTARTED (Supervisor manages the lifecycle).
step "Syncing Supervisor pool configurations"
if ! diff -rq "${SUPERVISOR_CONF_SRC}" "${SUPERVISOR_CONF_DST}" > /dev/null 2>&1; then
    echo "  Supervisor configs changed — reloading"
    sudo rsync -av --delete \
        "${SUPERVISOR_CONF_SRC}/" \
        "${SUPERVISOR_CONF_DST}/"
    sudo supervisorctl reread
    sudo supervisorctl update
else
    echo "  No Supervisor config changes — skipping reload"
fi

# ── 9. Exit maintenance mode ──────────────────────────────────────────────────
step "Exiting maintenance mode"
${ARTISAN} up

echo
echo "✅  Deployment complete!"
echo "    Verify worker pools: sudo supervisorctl status"
echo "    Monitor failed jobs: ${ARTISAN} queue:failed"
echo "    Queue depths:        ${ARTISAN} queue:monitor redis:realtime,redis:emails,redis:default"
