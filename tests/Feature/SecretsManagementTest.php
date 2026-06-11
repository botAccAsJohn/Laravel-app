<?php

namespace Tests\Feature;

use App\Providers\SecretsVaultServiceProvider;
use Tests\TestCase;

class SecretsManagementTest extends TestCase
{
    public function test_secrets_vault_provider_exists()
    {
        $this->assertTrue(class_exists(SecretsVaultServiceProvider::class));
    }

    public function test_secrets_vault_provider_has_register_method()
    {
        $code = file_get_contents(app_path('Providers/SecretsVaultServiceProvider.php'));
        $this->assertStringContainsString('loadSecretsFromVault', $code);
        $this->assertStringContainsString("environment('production', 'staging')", $code);
        $this->assertStringContainsString('SLACK_SIGNING_SECRET', $code);
        $this->assertStringContainsString('EXTERNAL_API_TOKEN', $code);
        $this->assertStringContainsString('DB_PASSWORD', $code);
    }

    public function test_encrypted_production_env_exists()
    {
        $this->assertFileExists(base_path('.env.production.encrypted'));
    }

    public function test_secrets_management_runbook_exists()
    {
        $path = base_path('docs/runbooks/secrets-management.md');
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('Secrets Management', $content);
        $this->assertStringContainsString('HashiCorp Vault', $content);
        $this->assertStringContainsString('env:encrypt', $content);
        $this->assertStringContainsString('env:decrypt', $content);
        $this->assertStringContainsString('LARAVEL_ENV_ENCRYPTION_KEY', $content);
    }
}
