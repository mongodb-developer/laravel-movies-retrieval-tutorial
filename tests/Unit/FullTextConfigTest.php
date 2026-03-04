<?php

namespace Tests\Unit;

use Tests\TestCase;

class FullTextConfigTest extends TestCase
{
    /**
     * Test that fulltext configuration file exists and loads correctly
     */
    public function test_fulltext_config_exists_and_loads(): void
    {
        $configPath = base_path('config/fulltext.php');
        $this->assertFileExists($configPath, 'FullText config file should exist');

        $config = require $configPath;
        $this->assertIsArray($config, 'FullText config should return an array');
    }

    /**
     * Test fulltext configuration has required keys
     */
    public function test_fulltext_config_has_required_keys(): void
    {
        $config = config('fulltext');

        // Test top-level keys
        $this->assertArrayHasKey('index', $config);
        $this->assertArrayHasKey('search', $config);

        // Test index configuration
        $this->assertArrayHasKey('name', $config['index']);
        $this->assertArrayHasKey('fields', $config['index']);
        $this->assertArrayHasKey('delete_wait_time', $config['index']);
        $this->assertArrayHasKey('delete_wait_interval', $config['index']);

        // Test search configuration
        $this->assertArrayHasKey('limit', $config['search']);
        $this->assertArrayHasKey('fuzzy', $config['search']);
    }

    /**
     * Test fulltext configuration has correct default values
     */
    public function test_fulltext_config_has_correct_defaults(): void
    {
        $config = config('fulltext');

        $this->assertEquals('movies_fulltext_index', $config['index']['name']);
        $this->assertIsArray($config['index']['fields']);
        $this->assertContains('title', $config['index']['fields']);
        $this->assertContains('plot', $config['index']['fields']);
        $this->assertContains('fullplot', $config['index']['fields']);
        $this->assertEquals(30, $config['index']['delete_wait_time']);
        $this->assertEquals(2, $config['index']['delete_wait_interval']);
        $this->assertEquals(10, $config['search']['limit']);
        $this->assertTrue($config['search']['fuzzy']);
    }

    /**
     * Test fulltext configuration values are correct types
     */
    public function test_fulltext_config_value_types(): void
    {
        $config = config('fulltext');

        $this->assertIsString($config['index']['name']);
        $this->assertIsArray($config['index']['fields']);
        $this->assertIsInt($config['index']['delete_wait_time']);
        $this->assertIsInt($config['index']['delete_wait_interval']);
        $this->assertIsInt($config['search']['limit']);
        $this->assertIsBool($config['search']['fuzzy']);
    }

    /**
     * Test fulltext index fields are all strings
     */
    public function test_fulltext_index_fields_are_strings(): void
    {
        $config = config('fulltext');
        $fields = $config['index']['fields'];

        foreach ($fields as $field) {
            $this->assertIsString($field);
        }
    }
}
