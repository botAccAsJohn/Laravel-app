<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Services\WebhookSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HashingApiKeysWebhooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_key_model_uses_sha256_hashing()
    {
        $code = file_get_contents(app_path('Models/ApiKey.php'));
        $this->assertStringContainsString("hash('sha256'", $code);
        $this->assertStringContainsString('random_bytes', $code);
    }

    public function test_api_key_plain_text_never_stored()
    {
        $code = file_get_contents(app_path('Models/ApiKey.php'));
        $this->assertStringContainsString('key_hash', $code);
        $this->assertStringContainsString('NEVER the plain-text', $code);
    }

    public function test_api_key_uses_hash_equals_timing_safe_comparison()
    {
        $code = file_get_contents(app_path('Models/ApiKey.php'));
        $this->assertStringContainsString('hash_equals', $code);
    }

    public function test_webhook_service_uses_hmac_sha256()
    {
        $code = file_get_contents(app_path('Services/WebhookSignatureService.php'));
        $this->assertStringContainsString('hash_hmac', $code);
        $this->assertStringContainsString('sha256', $code);
    }

    public function test_webhook_service_uses_hash_equals_for_verification()
    {
        $code = file_get_contents(app_path('Services/WebhookSignatureService.php'));
        $this->assertStringContainsString('hash_equals', $code);
    }

    public function test_webhook_service_has_replay_attack_protection()
    {
        $code = file_get_contents(app_path('Services/WebhookSignatureService.php'));
        $this->assertStringContainsString('maxAgeSeconds', $code);
        $this->assertStringContainsString('Replay attack', $code);
    }

    public function test_slack_middleware_uses_hash_hmac()
    {
        $code = file_get_contents(app_path('Http/Middleware/VerifySlackSignature.php'));
        $this->assertStringContainsString('hash_hmac', $code);
    }

    public function test_api_key_documentation_explains_why_not_bcrypt()
    {
        $code = file_get_contents(app_path('Models/ApiKey.php'));
        $this->assertStringContainsString('bcrypt', $code);
        $this->assertStringContainsString('200', $code);
        $this->assertStringContainsString('160 bits', $code);
    }

    public function test_webhook_service_documents_hash_table()
    {
        $code = file_get_contents(app_path('Services/WebhookSignatureService.php'));
        $this->assertStringContainsString('API Keys vs Passwords', $code);
        $this->assertStringContainsString('NEVER', $code);
    }

    public function test_key_generation_returns_plain_text_only_once()
    {
        $user = \App\Models\User::factory()->create();
        [$model, $plain] = ApiKey::generate($user, 'Test Key');

        $this->assertStringStartsWith('sk-', $plain);
        $this->assertEquals(43, strlen($plain));
        $this->assertNotEquals($plain, $model->key_hash);
        $this->assertTrue(hash_equals(
            hash('sha256', $plain),
            $model->key_hash
        ));
    }
}
