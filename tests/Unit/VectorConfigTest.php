<?php

namespace Tests\Unit;

use Tests\TestCase;

class VectorConfigTest extends TestCase
{
    /**
     * Test that vector configuration file exists and loads correctly
     */
    public function test_vector_config_exists_and_loads(): void
    {
        $configPath = base_path('config/vector.php');
        $this->assertFileExists($configPath, 'Vector config file should exist');

        $config = require $configPath;
        $this->assertIsArray($config, 'Vector config should return an array');
    }

    /**
     * Test vector configuration has required keys
     */
    public function test_vector_config_has_required_keys(): void
    {
        $config = config('vector');

        // Test top-level keys
        $this->assertArrayHasKey('collection', $config);
        $this->assertArrayHasKey('field_path', $config);
        $this->assertArrayHasKey('index', $config);
        $this->assertArrayHasKey('embeddings', $config);
        $this->assertArrayHasKey('search', $config);

        // Test index configuration
        $this->assertArrayHasKey('name', $config['index']);
        $this->assertArrayHasKey('dimensions', $config['index']);
        $this->assertArrayHasKey('similarity', $config['index']);
        $this->assertArrayHasKey('delete_wait_time', $config['index']);
        $this->assertArrayHasKey('delete_wait_interval', $config['index']);

        // Test embeddings configuration
        $this->assertArrayHasKey('batch_size', $config['embeddings']);
        $this->assertArrayHasKey('safety_limit', $config['embeddings']);
        $this->assertArrayHasKey('batch_delay_ms', $config['embeddings']);

        // Test search configuration
        $this->assertArrayHasKey('limit', $config['search']);
        $this->assertArrayHasKey('num_candidates', $config['search']);
    }

    /**
     * Test vector configuration has correct default values
     */
    public function test_vector_config_has_correct_defaults(): void
    {
        $config = config('vector');

        $this->assertEquals('movies', $config['collection']);
        $this->assertEquals('embeddings', $config['field_path']);
        $this->assertEquals('movies_vector_index', $config['index']['name']);
        $this->assertEquals(512, $config['index']['dimensions']);
        $this->assertEquals('cosine', $config['index']['similarity']);
        $this->assertEquals(30, $config['index']['delete_wait_time']);
        $this->assertEquals(2, $config['index']['delete_wait_interval']);
        $this->assertEquals(10, $config['embeddings']['batch_size']);
        $this->assertEquals(100, $config['embeddings']['safety_limit']);
        $this->assertEquals(100, $config['embeddings']['batch_delay_ms']);
        $this->assertEquals(10, $config['search']['limit']);
        $this->assertEquals(100, $config['search']['num_candidates']);
    }

    /**
     * Test vector configuration values are correct types
     */
    public function test_vector_config_value_types(): void
    {
        $config = config('vector');

        $this->assertIsString($config['collection']);
        $this->assertIsString($config['field_path']);
        $this->assertIsString($config['index']['name']);
        $this->assertIsInt($config['index']['dimensions']);
        $this->assertIsString($config['index']['similarity']);
        $this->assertIsInt($config['index']['delete_wait_time']);
        $this->assertIsInt($config['index']['delete_wait_interval']);
        $this->assertIsInt($config['embeddings']['batch_size']);
        $this->assertIsInt($config['embeddings']['safety_limit']);
        $this->assertIsInt($config['embeddings']['batch_delay_ms']);
        $this->assertIsInt($config['search']['limit']);
        $this->assertIsInt($config['search']['num_candidates']);
    }
}
