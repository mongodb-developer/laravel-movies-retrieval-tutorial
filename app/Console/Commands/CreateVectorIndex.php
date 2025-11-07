<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateVectorIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vector:create-index {--force : Delete existing index before creating new one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create MongoDB Atlas Vector Search index for movies collection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $indexName = 'movies_vector_index';
        $collectionName = 'movies';

        // Get vector configuration from environment
        $vectorDimensions = (int) env('VECTOR_DIMENSIONS', 512);
        $vectorSimilarity = env('VECTOR_SIMILARITY', 'cosine');

        $this->info('Creating vector search index for movies collection...');
        $this->newLine();

        try {
            // Get the MongoDB collection instance
            $connection = DB::connection('mongodb');
            $collection = $connection->getCollection($collectionName);

            // Check if vector index already exists
            $existingIndex = $this->findExistingIndex($collection, $indexName);

            if ($existingIndex) {
                if ($this->option('force')) {
                    $this->warn("Found existing index '{$indexName}'. Deleting due to --force flag...");
                    $this->deleteIndex($collection, $indexName);
                    $this->info('Existing index deleted successfully.');
                    $this->info('Waiting for deletion to complete...');

                    // Wait for deletion to propagate (MongoDB Atlas can take time)
                    $maxWaitTime = 30; // seconds
                    $waitInterval = 2; // seconds
                    $elapsed = 0;

                    while ($elapsed < $maxWaitTime) {
                        sleep($waitInterval);
                        $elapsed += $waitInterval;

                        // Check if index still exists
                        $stillExists = $this->findExistingIndex($collection, $indexName);
                        if (!$stillExists) {
                            $this->info('Index deletion confirmed.');
                            break;
                        }

                        $this->line("  Still waiting... ({$elapsed}s)");
                    }

                    $this->newLine();
                } else {
                    $this->warn("Vector search index '{$indexName}' already exists.");
                    $this->info('Use --force flag to delete and recreate the index.');
                    $this->newLine();
                    $this->displayIndexInfo($existingIndex);
                    return 0;
                }
            }

            // Create vector search index
            $this->info('Creating new vector search index...');
            $result = $collection->createSearchIndex(
                [
                    'fields' => [
                        [
                            'type' => 'vector',
                            'path' => 'embeddings',
                            'numDimensions' => $vectorDimensions,
                            'similarity' => $vectorSimilarity
                        ]
                    ]
                ],
                [
                    'name' => $indexName,
                    'type' => 'vectorSearch'
                ]
            );

            $this->newLine();
            $this->info('✓ Vector search index created successfully!');
            $this->newLine();

            // Display configuration
            $this->table(
                ['Configuration', 'Value'],
                [
                    ['Index Name', $indexName],
                    ['Collection', $collectionName],
                    ['Vector Field', 'embeddings'],
                    ['Dimensions', $vectorDimensions],
                    ['Similarity Function', $vectorSimilarity],
                ]
            );

            $this->newLine();
            $this->info('Note: It may take a few moments for the index to become active in MongoDB Atlas.');

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to create vector search index.');
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
