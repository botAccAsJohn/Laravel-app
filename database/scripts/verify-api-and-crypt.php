<?php

/**
 * Exercise 53.3 & 54.1 — API Keys, Webhook Signatures, and Encryption Verification
 *
 * Usage:
 *   php database/scripts/verify-api-and-crypt.php
 */

declare(strict_types=1);

$appRoot = dirname(__DIR__, 2);
require $appRoot . '/vendor/autoload.php';

$app = require_once $appRoot . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ApiKey;
use App\Services\WebhookSignatureService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Encryption\DecryptException;

echo "========================================================================\n";
echo "Verification Script for Exercises 53.3 & 54.1\n";
echo "========================================================================\n\n";

// Find or create a test user
$user = User::first();
if (!$user) {
    echo "Creating a test user for demonstration...\n";
    $user = User::create([
        'name' => 'Security Demo User',
        'email' => 'security-demo@example.com',
        'password' => bcrypt('password123!'),
    ]);
}

// -----------------------------------------------------------------------------
// Exercise 53.3: API Key Generation and Verification
// -----------------------------------------------------------------------------
echo "1. Exercise 53.3 - API Key Hashing & Verification\n";
echo "------------------------------------------------------------------------\n";

[$apiKeyModel, $plainKey] = ApiKey::generate($user, 'Demo Testing Key');
echo "Generated key name: {$apiKeyModel->name}\n";
echo "Plaintext API Key (one-time return): {$plainKey}\n";
echo "Stored Prefix: {$apiKeyModel->key_prefix}\n";
echo "Stored SHA-256 Hash in Database: " . DB::table('api_keys')->where('id', $apiKeyModel->id)->value('key_hash') . "\n";

// Verify the valid key
$foundKey = ApiKey::findByPlainKey($plainKey);
if ($foundKey && $foundKey->id === $apiKeyModel->id) {
    echo "✅ Verification SUCCESS: Found valid key via timing-safe lookup.\n";
} else {
    echo "❌ Verification FAILED: Could not find valid key.\n";
}

// Try verifying with a modified key (invalid)
$invalidKey = $plainKey . 'x';
$foundInvalidKey = ApiKey::findByPlainKey($invalidKey);
if (!$foundInvalidKey) {
    echo "✅ Verification SUCCESS: Correctly rejected invalid key.\n";
} else {
    echo "❌ Verification FAILED: Accepted an invalid key!\n";
}
echo "\n";

// -----------------------------------------------------------------------------
// Exercise 53.3: Webhook Signatures
// -----------------------------------------------------------------------------
echo "2. Exercise 53.3 - Webhook HMAC Signature Verification\n";
echo "------------------------------------------------------------------------\n";

$webhookService = new WebhookSignatureService();
$payload = json_encode(['event' => 'order.created', 'id' => 1001, 'amount' => 250.50]);
$secret = $webhookService->generateSecret();
echo "Generated Webhook Shared Secret: {$secret}\n";

$signature = $webhookService->sign($payload, $secret);
echo "Generated Signature Header: {$signature}\n";

// Verify signature
$isValidWebhook = $webhookService->verify($payload, $secret, $signature);
if ($isValidWebhook) {
    echo "✅ Webhook signature verified successfully with hash_equals().\n";
} else {
    echo "❌ Webhook signature verification failed.\n";
}

// Verify with modified payload (tampering attempt)
$tamperedPayload = json_encode(['event' => 'order.created', 'id' => 1001, 'amount' => 99999.99]);
$isValidTampered = $webhookService->verify($tamperedPayload, $secret, $signature);
if (!$isValidTampered) {
    echo "✅ Webhook successfully rejected tampered payload.\n";
} else {
    echo "❌ Security Alert: Verified a tampered payload!\n";
}
echo "\n";

// -----------------------------------------------------------------------------
// Exercise 54.1: APP_KEY and Encrypter Basics
// -----------------------------------------------------------------------------
echo "3. Exercise 54.1 - APP_KEY & Crypt Basics\n";
echo "------------------------------------------------------------------------\n";

$secretData = "Top Secret Core Information";
echo "Original Data: {$secretData}\n";

$encrypted = Crypt::encryptString($secretData);
echo "Encrypted Ciphertext: " . substr($encrypted, 0, 60) . "...\n";

$decrypted = Crypt::decryptString($encrypted);
echo "Decrypted Data: {$decrypted}\n";

if ($decrypted === $secretData) {
    echo "✅ Encryption/Decryption verified.\n";
} else {
    echo "❌ Decryption value mismatch!\n";
}
echo "\n";

// Demonstrate what happens if key is changed
echo "4. Exercise 54.1 - Demonstration of Key Mismatch / Rotation Failure\n";
echo "------------------------------------------------------------------------\n";

// We instantiate a new Encrypter with a different key to simulate changing the APP_KEY
$differentKey = random_bytes(32);
$differentEncrypter = new \Illuminate\Encryption\Encrypter($differentKey, 'aes-256-cbc');

try {
    echo "Attempting to decrypt ciphertext with a different key...\n";
    $differentEncrypter->decryptString($encrypted);
    echo "❌ Error: Successfully decrypted with the wrong key! (Security breach)\n";
} catch (DecryptException $e) {
    echo "✅ Successfully caught expected DecryptException: {$e->getMessage()}\n";
    echo "   (This simulates what happens if APP_KEY is changed without re-encrypting data.)\n";
}

echo "\n========================================================================\n";
echo "All exercises verified!\n";
echo "========================================================================\n";
