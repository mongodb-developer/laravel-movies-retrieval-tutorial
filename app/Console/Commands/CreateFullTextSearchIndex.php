<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateFullTextSearchIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fulltext:create-index {--force : Delete existing index before creating new one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create MongoDB Atlas Full-Text Search index for movies collection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $indexName = config('fulltext.index.name');
        $collectionName = config('vector.collection');

        // Get full-text search configuration
        // Current value: ['title', 'plot', 'fullplot', 'cast', 'directors']
        $searchFields = config('fulltext.index.fields');

        $this->info('Creating full-text search index for movies collection...');
        $this->newLine();

        try {
            // Get the MongoDB collection instance
            $collection = DB::connection('mongodb')->getCollection($collectionName);

            // Check if full-text index already exists
            $existingIndex = $this->findExistingIndex($collection, $indexName);

            if ($existingIndex) {
                if ($this->option('force')) {
                    $this->warn("Found existing index '{$indexName}'. Deleting due to --force flag...");
                    $this->deleteIndex($collection, $indexName);
                    $this->info('Existing index deleted successfully.');
                    $this->info('Waiting for deletion to complete...');

                    // Wait for deletion to propagate (MongoDB Atlas can take time)
                    $maxWaitTime = config('fulltext.index.delete_wait_time', 30);
                    $waitInterval = config('fulltext.index.delete_wait_interval', 2);
                    $elapsed = 0;

                    while ($elapsed < $maxWaitTime) {
                        sleep($waitInterval);
                        $elapsed += $waitInterval;

                        // Check if index still exists (refresh collection connection)
                        // $refreshedCollection = DB::connection('mongodb')->getCollection($collectionName);
                        $stillExists = $this->findExistingIndex($collection , $indexName);
                        if (!$stillExists) {
                            $this->info('Index deletion confirmed.');
                            break;
                        }

                        $this->line("  Still waiting... ({$elapsed}s)");
                    }

                    $this->newLine();
                } else {
                    $this->warn("Full-text search index '{$indexName}' already exists.");
                    $this->info('Use --force flag to delete and recreate the index.');
                    $this->newLine();
                    $this->displayIndexInfo($existingIndex);
                    return 0;
                }
            }

            // Build field mappings for full-text search
            $fieldMappings = [];
            foreach ($searchFields as $field) {
                $fieldMappings[$field] = [
                    'type' => 'string'
                ];
            }

            // Create full-text search index
            $this->info('Creating new full-text search index...');
            $result = $collection->createSearchIndex(
                [
                    'mappings' => [
                        'dynamic' => false,
                        'fields' => $fieldMappings
                    ]
                ],
                [
                    'name' => $indexName
                ]
            );

            $this->newLine();
            $this->info('✓ Full-text search index created successfully!');
            $this->newLine();

            // Display configuration
            $this->table(
                ['Configuration', 'Value'],
                [
                    ['Index Name', $indexName],
                    ['Collection', $collectionName],
                    ['Search Fields', implode(', ', $searchFields)],
                    ['Index Type', 'search (Lucene)'],
                ]
            );

            $this->newLine();
            $this->info('Note: It may take a few moments for the index to become active in MongoDB Atlas.');

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to create full-text search index.');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Find existing index by name
     */
    private function findExistingIndex($collection, string $indexName)
    {
        $indexes = $collection->listSearchIndexes();
        foreach ($indexes as $index) {
            if (isset($index['name']) && $index['name'] === $indexName) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Delete an existing search index
     */
    private function deleteIndex($collection, string $indexName): void
    {
        $collection->dropSearchIndex($indexName);
    }

    /**
     * Display information about existing index
     */
    private function displayIndexInfo($index): void
    {
        $this->info('Existing Index Details:');
        $this->table(
            ['Property', 'Value'],
            [
                ['Name', $index['name'] ?? 'N/A'],
                ['Type', $index['type'] ?? 'N/A'],
                ['Status', $index['status'] ?? 'N/A'],
            ]
        );
    }
}
