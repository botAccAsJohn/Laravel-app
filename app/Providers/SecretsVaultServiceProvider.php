<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Exercise 54.4 — Secrets Management Service Provider
 *
 * Demonstrates loading application-layer secrets from a secure external Vault
 * (HashiCorp Vault, AWS Secrets Manager, Doppler, GCP Secret Manager) at boot,
 * avoiding storing high-value production credentials in the .env file.
 */
class SecretsVaultServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Only load secrets from Vault in production/staging environments.
        if ($this->app->environment('production', 'staging')) {
            $this->loadSecretsFromVault();
        }
    }

    /**
     * Fetch secrets from the vault and inject them into Laravel's config.
     */
    protected function loadSecretsFromVault(): void
    {
        try {
            // In a real application, you would use the AWS SDK or an HTTP client
            // to fetch secrets from the Vault.
            // Example:
            // $vaultToken = env('VAULT_TOKEN');
            // $response = Http::withToken($vaultToken)
            //     ->get('https://vault.example.com/v1/secret/data/myapp');
            // $secrets = $response->json('data.data');

            // Simulating Vault integration:
            $secrets = $this->fetchMockVaultSecrets();

            if (empty($secrets)) {
                Log::channel('security')->warning('[SecretsVault] Vault returned empty secrets.');
                return;
            }

            // Inject secrets directly into Laravel's configuration registry.
            // These values never touch the disk/env file, living only in memory.
            config([
                'services.slack.signing_secret' => $secrets['SLACK_SIGNING_SECRET'] ?? config('services.slack.signing_secret'),
                'services.external_api.token'   => $secrets['EXTERNAL_API_TOKEN'] ?? config('services.external_api.token'),
                'database.connections.mysql.password' => $secrets['DB_PASSWORD'] ?? config('database.connections.mysql.password'),
            ]);

            Log::info('[SecretsVault] Production secrets loaded successfully from secure vault.');
        } catch (\Throwable $e) {
            // In production, failing to load secrets should halt the application.
            Log::channel('security')->critical('[SecretsVault] Failed to load production secrets: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            if ($this->app->environment('production')) {
                abort(500, 'Secrets Vault configuration failure. Application halted.');
            }
        }
    }

    /**
     * Simulation of external Vault retrieval.
     */
    protected function fetchMockVaultSecrets(): array
    {
        // For demonstration purposes, we return production secrets here.
        // In a real environment, this method would fetch from an HTTP endpoint
        // or a local secure credential daemon (e.g. AWS Instance Metadata Service).
        return [
            'SLACK_SIGNING_SECRET' => 'prod_slack_secret_from_hashicorp_vault_99218',
            'EXTERNAL_API_TOKEN'   => 'prod_api_token_from_aws_secrets_manager_18237',
            'DB_PASSWORD'          => 'SuperSecureDbPasswordFromVault_8829',
        ];
    }
}
