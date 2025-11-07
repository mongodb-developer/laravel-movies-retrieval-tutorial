<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;

class DeleteEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embeddings:delete {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all embeddings from movies collection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Count movies with embeddings
        $count = Movie::whereNotNull('embeddings')->count();

        if ($count === 0) {
            $this->info('No embeddings found to delete.');
            return 0;
        }

        $this->info("Found {$count} movies with embeddings.");
        $this->newLine();

        // Confirmation prompt unless --force is used
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to delete all embeddings?', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Deleting embeddings...');

        try {
            // Use MongoDB's unset operation to remove the embeddings field
            $result = Movie::whereNotNull('embeddings')->update([
                '$unset' => ['embeddings' => '']
            ]);

            $this->newLine();
            $this->info("Successfully deleted embeddings from {$count} movies.");

            // Verify deletion
            $remaining = Movie::whereNotNull('embeddings')->count();
            if ($remaining > 0) {
                $this->warn("Warning: {$remaining} movies still have embeddings.");
            } else {
                $this->info('All embeddings have been removed.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to delete embeddings: ' . $e->getMessage());
            return 1;
        }
    }
}
