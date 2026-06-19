<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\ExternalApiException;
use Illuminate\Support\Facades\Log;

class ExternalApiService
{
    protected PendingRequest $client;

    public function __construct()
    {
        $this->client = Http::baseUrl(config('services.external_api.base_url'))
            ->withToken(config('services.external_api.token'))
            ->withHeaders([
                'Accept'       => 'application/json',
                'X-App-Source' => 'laravel-app',
            ])
            ->timeout(config('services.external_api.timeout', 30))
            ->retry(config('services.external_api.retry', 3), 100);
    }

    public function getUsers(): array
    {
        try {
            $response = $this->client->get('/users')
                ->throw()
                ->throwIf(fn ($response) => empty($response->json()), new \Exception("Empty users response from API"));
                
            return $response->json();
        } catch (RequestException $e) {
            Log::error('External API returned an error: ' . $e->getMessage());
            throw new ExternalApiException('The external service returned an error. Please try again later.');
        } catch (ConnectionException $e) {
            Log::error('External API unreachable: ' . $e->getMessage());
            throw new ExternalApiException('Could not connect to the external service. Please try again later.');
        } catch (\Exception $e) {
            Log::error('External API invalid content: ' . $e->getMessage());
            throw new ExternalApiException('The external service returned an unexpected response. Please try again later.');
        }
    }

    public function getUser(int $id): array
    {
        try {
            $response = $this->client->get("/users/{$id}")
                ->throw()
                ->throwIf(fn ($response) => empty($response->json()), new \Exception("Empty user response from API"));
                
            return $response->json();
        } catch (RequestException $e) {
            Log::error('External API returned an error: ' . $e->getMessage());
            throw new ExternalApiException('The external service returned an error. Please try again later.');
        } catch (ConnectionException $e) {
            Log::error('External API unreachable: ' . $e->getMessage());
            throw new ExternalApiException('Could not connect to the external service. Please try again later.');
        } catch (\Exception $e) {
            Log::error('External API invalid content: ' . $e->getMessage());
            throw new ExternalApiException('The external service returned an unexpected response. Please try again later.');
        }
    }

    public function createUser(array $data): array
    {
        try {
            $response = $this->client->post('/users', $data)
                ->throw()
                ->throwIf(fn ($response) => empty($response->json()), new \Exception("Empty create user response from API"));
                
            return $response->json();
        } catch (RequestException $e) {
            Log::error('External API returned an error: ' . $e->getMessage());
            throw new ExternalApiException('The external service returned an error. Please try again later.');
        } catch (ConnectionException $e) {
            Log::error('External API unreachable: ' . $e->getMessage());
            throw new ExternalApiException('Could not connect to the external service. Please try again later.');
        } catch (\Exception $e) {
            Log::error('External API invalid content: ' . $e->getMessage());
            throw new ExternalApiException('The external service returned an unexpected response. Please try again later.');
        }
    }
}
