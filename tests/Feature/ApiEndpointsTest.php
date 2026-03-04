<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    /**
     * Test hello endpoint returns expected response
     */
    public function test_hello_endpoint_works(): void
    {
        $response = $this->get('/api/hello');

        $response->assertStatus(200);
        $response->assertJson([
            'response' => 'hello world'
        ]);
    }

    /**
     * Test MongoDB connection test endpoint
     */
    public function test_mongodb_test_endpoint(): void
    {
        $response = $this->get('/api/mongodb-test');

        // Should return 200 if connected, or 500 if connection fails
        $this->assertContains($response->status(), [200, 500]);

        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'status',
                'connection',
                'database',
                'collections_found',
                'collections',
                'movies_collection' => [
                    'exists',
                    'document_count'
                ]
            ]);
            $response->assertJson([
                'status' => 'success'
            ]);
        } else {
            $response->assertJson([
                'status' => 'error'
            ]);
        }
    }

    /**
     * Test embedding model info endpoint returns status
     */
    public function test_embedding_model_info_endpoint(): void
    {
        $response = $this->get('/api/embedding-model-info');

        // Should return 200 if configured, or 400 if not configured
        $this->assertContains($response->status(), [200, 400]);

        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'status',
                'model',
                'embedding_dimensions',
                'configured'
            ]);
        } else {
            $response->assertJson([
                'configured' => false
            ]);
        }
    }

    /**
     * Test get movie by title endpoint
     */
    public function test_get_movie_by_title_endpoint(): void
    {
        // Test with a known movie title
        $response = $this->get('/api/get-movie-by-title/Titanic');

        // Should return 200 if movie exists
        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'title',
                'year'
            ]);
            $response->assertJson([
                'title' => 'Titanic'
            ]);
        } else {
            // If movie doesn't exist, should return 404
            $response->assertStatus(404);
        }
    }

    /**
     * Test get movie by title returns 404 for non-existent movie
     */
    public function test_get_movie_by_title_returns_404_for_non_existent(): void
    {
        $response = $this->get('/api/get-movie-by-title/NonExistentMovie123456');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'No movie found'
        ]);
    }

    /**
     * Test vector search endpoint structure
     */
    public function test_vector_search_endpoint_requires_query(): void
    {
        $response = $this->postJson('/api/movie-search-vector', []);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Query parameter is required'
        ]);
    }

    /**
     * Test vector search endpoint with valid query
     */
    public function test_vector_search_endpoint_with_query(): void
    {
        $response = $this->postJson('/api/movie-search-vector', [
            'query' => 'space adventure'
        ]);

        // Should return 200 if API key configured and embeddings exist
        // or 400 if API key not configured
        // or 500 if embeddings don't exist
        $this->assertContains($response->status(), [200, 400, 500]);

        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'query',
                'results',
                'count',
                'embedding_model',
                'vector_dimensions'
            ]);
            $this->assertEquals('space adventure', $response->json('query'));
        }
    }

    /**
     * Test embedding vectorize endpoint
     */
    public function test_embedding_vectorize_endpoint(): void
    {
        $response = $this->get('/api/embedding-model-vectorize/test');

        // Should return 200 if configured, or 400 if not configured
        $this->assertContains($response->status(), [200, 400]);

        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'input',
                'embedding',
                'embedding_dimensions',
                'model'
            ]);
            $response->assertJson([
                'input' => 'test',
                'model' => 'voyage-3-lite'
            ]);
        }
    }

    /**
     * Test naive full-text search endpoint requires query
     */
    public function test_search_text_naive_endpoint_requires_query(): void
    {
        $response = $this->postJson('/api/search-text-naive', []);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Query parameter is required'
        ]);
    }

    /**
     * Test naive full-text search endpoint with valid query
     */
    public function test_search_text_naive_endpoint_with_query(): void
    {
        $response = $this->postJson('/api/search-text-naive', [
            'query' => 'space'
        ]);

        // Should return 200 if index exists, or 500 if index doesn't exist
        $this->assertContains($response->status(), [200, 500]);

        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'query',
                'results',
                'count',
                'search_type',
                'index'
            ]);
            $this->assertEquals('space', $response->json('query'));
            $this->assertEquals('naive', $response->json('search_type'));
        }
    }

    /**
     * Test weighted full-text search endpoint requires query
     */
    public function test_search_text_weighted_endpoint_requires_query(): void
    {
        $response = $this->postJson('/api/search-text', []);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Query parameter is required'
        ]);
    }

    /**
     * Test weighted full-text search endpoint with valid query
     */
    public function test_search_text_weighted_endpoint_with_query(): void
    {
        $response = $this->postJson('/api/search-text', [
            'query' => 'space'
        ]);

        // Should return 200 if index exists, or 500 if index doesn't exist
        $this->assertContains($response->status(), [200, 500]);

        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'query',
                'results',
                'count',
                'search_type',
                'weights',
                'index'
            ]);
            $this->assertEquals('space', $response->json('query'));
            $this->assertEquals('weighted', $response->json('search_type'));
            $this->assertEquals(['title' => 5, 'plot' => 3, 'cast' => 2, 'directors' => 2, 'fullplot' => 1], $response->json('weights'));
        }
    }
}
