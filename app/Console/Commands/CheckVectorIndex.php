<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckVectorIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vector:check-index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of the MongoDB Atlas Vector Search index';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $indexName = config('vector.index.name');
        $collectionName = config('vector.collection');

        $this->info('Checking vector search index status...');
        $this->newLine();

        try {
            // Get the MongoDB collection instance
            $connection = DB::connection('mongodb');
            $collection = $connection->getCollection($collectionName);

            // List all search indexes
            $indexes = iterator_to_array($collection->listSearchIndexes());

            if (empty($indexes)) {
                $this->warn('No search indexes found on the movies collection.');
                $this->newLine();
                $this->info('Run the following command to create a vector search index:');
                $this->line('  php artisan vector:create-index');
                return 1;
            }

            // Look for the specific vector index
            $vectorIndex = null;
            foreach ($indexes as $index) {
                if ($index['name'] === $indexName) {
                    $vectorIndex = $index;
                    break;
                }
            }

            if (!$vectorIndex) {
                $this->warn("Vector search index '{$indexName}' not found.");
                $this->newLine();
                $this->info('Available search indexes:');
                foreach ($indexes as $index) {
                    $this->line("  - {$index['name']} (type: {$index['type']}, status: {$index['status']})");
                }
                $this->newLine();
                $this->info('Run the following command to create the vector search index:');
                $this->line('  php artisan vector:create-index');
                return 1;
            }

            // Display vector index information
            $this->info("✓ Vector search index '{$indexName}' found!");
            $this->newLine();

            // Basic information table
            $this->table(
                ['Property', 'Value'],
                [
                    ['Name', $vectorIndex['name']],
                    ['Type', $vectorIndex['type']],
                    ['Status', $vectorIndex['status']],
                    ['Queryable', $vectorIndex['queryable'] ? 'Yes' : 'No'],
                ]
            );

            // Status explanation
            $status = $vectorIndex['status'];
            if ($status === 'READY') {
                $this->info('✓ Index is READY and can be used for vector search queries.');
            } elseif ($status === 'BUILDING' || $status === 'PENDING') {
                $this->warn("⚠ Index is currently {$status}. Please wait a few minutes for it to become READY.");
            } elseif ($status === 'FAILED') {
                $this->error('✗ Index build FAILED. You may need to delete and recreate it.');
                $this->line('  php artisan vector:create-index --force');
            } else {
                $this->line("Index status: {$status}");
            }

            // Configuration details
            if (isset($vectorIndex['latestDefinition'])) {
                $this->newLine();
                $this->info('Index Configuration:');

                $definition = $vectorIndex['latestDefinition'];
                if (isset($definition['fields']) && is_array($definition['fields'])) {
                    foreach ($definition['fields'] as $field) {
                        if ($field['type'] === 'vector') {
                            $this->table(
                                ['Configuration', 'Value'],
                                [
                                    ['Vector Field Path', $field['path']],
                                    ['Dimensions', $field['numDimensions']],
                                    ['Similarity Function', $field['similarity']],
                                ]
                            );
                        }
                    }
                }
            }

            // List all other search indexes if any
            $otherIndexes = array_filter($indexes, fn($idx) => $idx['name'] !== $indexName);
            if (!empty($otherIndexes)) {
                $this->newLine();
                $this->info('Other search indexes on this collection:');
                foreach ($otherIndexes as $index) {
                    $this->line("  - {$index['name']} (type: {$index['type']}, status: {$index['status']})");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to check vector search index.');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            $this->info('Please verify:');
            $this->line('  1. MongoDB connection is configured in .env');
            $this->line('  2. You have network access to MongoDB Atlas');
            $this->line('  3. The database and collection exist');
            return 1;
        }
    }
}
