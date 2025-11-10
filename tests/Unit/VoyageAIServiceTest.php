<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VoyageAIService;

class VoyageAIServiceTest extends TestCase
{
    /**
     * Test that VoyageAIService can be instantiated
     */
    public function test_service_can_be_instantiated(): void
    {
        $service = new VoyageAIService();
        $this->assertInstanceOf(VoyageAIService::class, $service);
    }

    /**
     * Test that service returns correct model name
     */
    public function test_service_returns_correct_model(): void
    {
        $service = new VoyageAIService();
        $this->assertEquals('voyage-3-lite', $service->getModel());
    }

    /**
     * Test that isConfigured returns boolean
     */
    public function test_is_configured_returns_boolean(): void
    {
        $service = new VoyageAIService();
        $this->assertIsBool($service->isConfigured());
    }

    /**
     * Test that testConnection returns array with required keys
     */
    public function test_connection_returns_array(): void
    {
        $service = new VoyageAIService();

        // Skip if API key is not configured
        if (!$service->isConfigured()) {
            $this->markTestSkipped('VOYAGE_AI_API_KEY not configured');
        }

        $result = $service->testConnection();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);

        if ($result['success']) {
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayHasKey('model', $result['data']);
            $this->assertArrayHasKey('embedding_dimensions', $result['data']);
        } else {
            $this->assertArrayHasKey('error', $result);
        }
    }

    /**
     * Test that generateEmbeddings accepts empty array
     */
    public function test_generate_embeddings_validates_empty_input(): void
    {
        $service = new VoyageAIService();

        // Skip if API key is not configured
        if (!$service->isConfigured()) {
            $this->markTestSkipped('VOYAGE_AI_API_KEY not configured');
        }

        $result = $service->generateEmbeddings([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Text inputs are required', $result['error']);
    }

    /**
     * Test that generateEmbeddings returns expected structure
     */
    public function test_generate_embeddings_returns_expected_structure(): void
    {
        $service = new VoyageAIService();

        // Skip if API key is not configured
        if (!$service->isConfigured()) {
            $this->markTestSkipped('VOYAGE_AI_API_KEY not configured');
        }

        $result = $service->generateEmbeddings(['test']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);

        if ($result['success']) {
            $this->assertArrayHasKey('embeddings', $result);
            $this->assertArrayHasKey('count', $result);
            $this->assertArrayHasKey('usage', $result);
            $this->assertIsArray($result['embeddings']);
            $this->assertGreaterThan(0, $result['count']);
        }
    }
}
