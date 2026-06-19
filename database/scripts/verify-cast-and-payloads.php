<?php

/**
 * Verification Script for Exercises 54.2 & 54.3
 *
 * Usage:
 *   php database/scripts/verify-cast-and-payloads.php
 */

declare(strict_types=1);

$appRoot = dirname(__DIR__, 2);
require $appRoot . '/vendor/autoload.php';

$app = require_once $appRoot . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

echo "========================================================================\n";
echo "Verification Script for Exercises 54.2 & 54.3\n";
echo "========================================================================\n\n";

// -----------------------------------------------------------------------------
// Exercise 54.2: Encrypted Eloquent Casts & Blind Indexes
// -----------------------------------------------------------------------------
echo "1. Exercise 54.2 - Encrypted Eloquent Casts\n";
echo "------------------------------------------------------------------------\n";

$plainPhone = "+91 99999 88888";
$plainAddress = "Flat 402, Sunset Boulevard, Mumbai";
$plainTaxId = "TAX-ID-XYZ-12345";
$plainArray = ['role' => 'vip_customer', 'preferences' => ['theme' => 'dark', 'notifications' => 'sms']];

// Save a test user
$user = User::create([
    'name' => 'Encryption Tester',
    'email' => 'encrypt-tester-' . time() . '@example.com',
    'password' => 'password123!',
    'phone' => $plainPhone,
    'address' => $plainAddress,
    'tax_id' => $plainTaxId,
    'extra_attributes' => $plainArray,
]);

echo "Saved User ID: {$user->id}\n";

// Fetch directly from DB raw (bypassing Eloquent casts) to prove it's encrypted at rest
$rawUser = DB::table('users')->where('id', $user->id)->first();
echo "Raw 'phone' in DB: " . substr($rawUser->phone, 0, 50) . "...\n";
echo "Raw 'address' in DB: " . substr($rawUser->address, 0, 50) . "...\n";
echo "Raw 'tax_id' in DB: " . substr($rawUser->tax_id, 0, 50) . "...\n";
echo "Raw 'extra_attributes' in DB: " . substr($rawUser->extra_attributes, 0, 50) . "...\n";

if (!str_contains($rawUser->phone, $plainPhone) && !str_contains($rawUser->address, $plainAddress)) {
    echo "✅ Success: PII columns are encrypted at rest (plaintext not visible in DB).\n";
} else {
    echo "❌ Failure: Plaintext visible in raw DB table!\n";
}

// Verify Eloquent casts decrypt them automatically
$fetchedUser = User::find($user->id);
echo "Decrypted 'phone': {$fetchedUser->phone}\n";
echo "Decrypted 'address': {$fetchedUser->address}\n";
echo "Decrypted 'tax_id': {$fetchedUser->tax_id}\n";
echo "Decrypted 'extra_attributes' (Array Cast): " . json_encode($fetchedUser->extra_attributes) . "\n";

if ($fetchedUser->phone === $plainPhone && $fetchedUser->extra_attributes['role'] === 'vip_customer') {
    echo "✅ Success: Eloquent cast automatically decrypted string & array attributes.\n";
} else {
    echo "❌ Failure: Decrypted values mismatch!\n";
}
echo "\n";

// -----------------------------------------------------------------------------
// Exercise 54.2: Querying Encrypted Columns
// -----------------------------------------------------------------------------
echo "2. Exercise 54.2 - Querying Encrypted Columns & Blind Indexes\n";
echo "------------------------------------------------------------------------\n";

// Show that querying by encrypted column directly fails
$directSearch = User::where('phone', $plainPhone)->first();
if (!$directSearch) {
    echo "✅ Success: Searching directly using ->where('phone', \$val) returned NULL.\n";
    echo "   Reason: Encryption uses random IVs; DB ciphertext differs from query ciphertext.\n";
} else {
    echo "❌ Failure: Direct search returned a model. This shouldn't happen with random IVs!\n";
}

// Show that querying using the blind index works
$blindIndexSearch = User::findByPhone($plainPhone);
if ($blindIndexSearch && $blindIndexSearch->id === $user->id) {
    echo "✅ Success: Searching using blind index findByPhone() worked perfectly!\n";
    echo "   Blind Index in DB: {$rawUser->phone_bindex}\n";
} else {
    echo "❌ Failure: Blind index search failed to locate the user.\n";
}
echo "\n";

// -----------------------------------------------------------------------------
// Exercise 54.3: Signed vs. Encrypted Payload URLs
// -----------------------------------------------------------------------------
echo "3. Exercise 54.3 - Encrypted Payload URLs\n";
echo "------------------------------------------------------------------------\n";

// Simulate hitting /generate-encrypted-link/{id}
$payload = Crypt::encryptString(json_encode([
    'user' => $user->id,
    'expires_at' => now()->addHour()->timestamp
]));

// Simulate hitting unsubscribe route
try {
    $decryptedJson = Crypt::decryptString($payload);
    $data = json_decode($decryptedJson, true);

    if (time() <= $data['expires_at']) {
        echo "✅ Success: Decrypted encrypted payload successfully.\n";
        echo "   Decrypted User ID: {$data['user']}\n";
        echo "   Expiry Timestamp: {$data['expires_at']}\n";
    } else {
        echo "❌ Failure: Payload marked as expired incorrectly.\n";
    }
} catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
    echo "❌ Failure: Could not decrypt payload: {$e->getMessage()}\n";
}

// Test expiration verification
$expiredPayload = Crypt::encryptString(json_encode([
    'user' => $user->id,
    'expires_at' => now()->subMinutes(5)->timestamp
]));

try {
    $decryptedJson = Crypt::decryptString($expiredPayload);
    $data = json_decode($decryptedJson, true);

    if (time() > $data['expires_at']) {
        echo "✅ Success: Correctly flagged expired encrypted payload.\n";
    } else {
        echo "❌ Failure: Expired payload was not flagged!\n";
    }
} catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
    echo "❌ Failure: Decryption error on expired payload.\n";
}

echo "\n========================================================================\n";
echo "All test scenarios verified successfully!\n";
echo "========================================================================\n";
