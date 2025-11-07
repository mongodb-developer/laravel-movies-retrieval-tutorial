<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoyageAIService
{
    private const API_URL = 'https://api.voyageai.com/v1/embeddings';
    private const MODEL = 'voyage-3-lite';
    private const DEFAULT_TIMEOUT = 30;

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('VOYAGE_AI_API_KEY', '');
    }

    /**
     * Check if the API key is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get the model name
     */
    public function getModel(): string
    {
        return self::MODEL;
    }

    /**
     * Test connection to Voyage AI API
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'VOYAGE_AI_API_KEY is not configured'
            ];
        }

        try {
            $response = $this->makeRequest(['test connection'], 10);

            if ($response['success']) {
                $embedding = $response['data']['data'][0]['embedding'] ?? null;

                return [
                    'success' => true,
                    'data' => [
                        'model' => $response['data']['model'] ?? self::MODEL,
                        'embedding_dimensions' => $embedding ? count($embedding) : null,
                        'usage' => $response['data']['usage'] ?? null,
                    ]
                ];
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('VoyageAI connection test failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate embeddings for multiple text inputs (batch processing)
     *
     * @param array<string> $texts
     * @return array{success: bool, embeddings?: array, count?: int, usage?: array, error?: string}
     */
    public function generateEmbeddings(array $texts): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'VOYAGE_AI_API_KEY is not configured'
            ];
        }

        if (empty($texts)) {
            return [
                'success' => false,
                'error' => 'Text inputs are required'
            ];
        }

        try {
            $response = $this->makeRequest($texts);

            if ($response['success']) {
                $embeddings = $response['data']['data'] ?? [];

                return [
                    'success' => true,
                    'embeddings' => $embeddings,
                    'count' => count($embeddings),
                    'usage' => $response['data']['usage'] ?? null
                ];
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('VoyageAI batch embedding generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Make HTTP request to Voyage AI API
     *
     * @param array<string> $inputs
     * @param int $timeout
     * @return array{success: bool, data?: array, error?: string, status_code?: int}
     */
    private function makeRequest(array $inputs, int $timeout = self::DEFAULT_TIMEOUT): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout($timeout)
        ->post(self::API_URL, [
            'input' => $inputs,
            'model' => self::MODEL,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json()
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? $response->body(),
            'status_code' => $response->status()
        ];
    }
}
