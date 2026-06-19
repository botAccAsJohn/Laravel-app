<?php

namespace App\Console\Commands;

use App\Events\Behavior\CartAbandoned;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'cart:remind-abandoned';

    protected $description = 'Scan Redis carts and send reminders for abandoned items';

    public function handle(CartService $cartService)
    {
        $this->info('Scanning Redis for all active carts...');

        $oneHourAgo = now()->timestamp - (60 * 60);

        // Step 1: Pull every cart directly from Redis — no DB involved yet
        $allCarts = $cartService->findAllCarts();

        if (empty($allCarts)) {
            $this->info('No active carts found in Redis.');
            return;
        }

        $this->line('Found ' . count($allCarts) . ' active cart(s) in Redis.');

        // Step 2: Filter — find which user IDs have abandoned items
        $abandonedUserIds = [];
        $cartSummaries    = []; // userId => [cart, cartTotal, itemCount]

        foreach ($allCarts as $userId => $cart) {
            $lastActivity = $cart['_last_activity_at'] ?? 0;

            // Abandoned if the last activity was over an hour ago
            $hasAbandoned = ($lastActivity > 0 && $lastActivity <= $oneHourAgo);

            $cartTotal    = 0;
            $itemCount    = 0;

            foreach ($cart as $key => $item) {
                // Skip the metadata timestamp key
                if ($key === '_last_activity_at') {
                    continue;
                }

                $cartTotal += $item['price'] * $item['quantity'];
                $itemCount += $item['quantity'];
            }

            if ($hasAbandoned) {
                $abandonedUserIds[]     = $userId;
                $cartSummaries[$userId] = compact('cart', 'cartTotal', 'itemCount');
            }
        }

        if (empty($abandonedUserIds)) {
            $this->info('No abandoned carts found (all carts are within the time limit).');
            return;
        }

        $this->line(count($abandonedUserIds) . ' abandoned cart(s) found. Loading users...');

        // Step 3: One single DB query — only for users with abandoned carts
        $users = User::whereIn('id', $abandonedUserIds)->get()->keyBy('id');

        // Step 4: Dispatch CartAbandoned event for each qualifying user
        foreach ($abandonedUserIds as $userId) {
            /** @var User $user */
            $user = $users[$userId] ?? null;

            if (! $user) {
                $this->warn("User #{$userId} not found in DB — skipping.");
                continue;
            }

            // Skip if already reminded within the cooldown window (default 24h)
            if ($cartService->wasReminded($userId)) {
                $this->line("→ User: {$user->name} (ID: {$userId}) — already reminded, skipping.");
                continue;
            }

            $summary = $cartSummaries[$userId];

            $this->line("→ User: {$user->name} (ID: {$userId}) | Items: {$summary['itemCount']} | Total: \${$summary['cartTotal']}");

            Event::dispatch(new CartAbandoned(
                $summary['cart'],
                $user,
                (float) $summary['cartTotal'],
                (int) $summary['itemCount']
            ));

            // Mark as reminded — won't re-send for 24 hours
            $cartService->markAsReminded($userId);
        }

        $this->info('Done. Dispatched reminders for ' . count($abandonedUserIds) . ' user(s).');
    }
}
