<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    /**
     * Test that embeddings:generate command exists
     */
    public function test_embeddings_generate_command_exists(): void
    {
        $this->artisan('embeddings:generate --help')
            ->assertExitCode(0);
    }

    /**
     * Test that embeddings:delete command exists
     */
    public function test_embeddings_delete_command_exists(): void
    {
        $this->artisan('embeddings:delete --help')
            ->assertExitCode(0);
    }

    /**
     * Test that vector:create-index command exists
     */
    public function test_vector_create_index_command_exists(): void
    {
        $this->artisan('vector:create-index --help')
            ->assertExitCode(0);
    }

    /**
     * Test embeddings:delete command with force flag
     */
    public function test_embeddings_delete_with_force_flag(): void
    {
        // This should run without prompting - exits successfully regardless of outcome
        $this->artisan('embeddings:delete --force')
            ->assertSuccessful();
    }

    /**
     * Test vector:create-index command runs successfully
     */
    public function test_vector_create_index_runs_successfully(): void
    {
        // Command should run successfully (either creates index or shows existing)
        // Exit code 0 = success, Exit code 1 = MongoDB connection error or other failure
        $this->artisan('vector:create-index')
            ->assertExitCode(0);
    }
}
