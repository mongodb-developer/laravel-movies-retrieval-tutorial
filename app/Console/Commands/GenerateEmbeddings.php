<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;
use App\Services\VoyageAIService;
use Illuminate\Support\Facades\Log;

class GenerateEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embeddings:generate {--force : Force regeneration of existing embeddings} {--limit= : Limit the number of movies to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate vector embeddings for movies using Voyage AI';

    /**
     * Batch processing configuration
     */
    private const BATCH_SIZE = 10;

    /**
     * Voyage AI Service
     */
    private VoyageAIService $voyageAI;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Initialize Voyage AI service
        $this->voyageAI = new VoyageAIService();

        // Check if API key is configured
        if (!$this->voyageAI->isConfigured()) {
            $this->error('VOYAGE_AI_API_KEY is not set in .env file');
            return 1;
        }

        $this->info('Starting embedding generation for movies collection...');
        $this->newLine();

        // Build query based on --force flag
        $query = Movie::query();
        if (!$this->option('force')) {
            // Only process movies without embeddings
            $query->where(function ($q) {
                $q->whereNull('embeddings')
                  ->orWhere('embeddings', []);
            });
        }

        // Apply limit if specified
        $limit = $this->option('limit');
        if ($limit && is_numeric($limit)) {
            $query->limit((int) $limit);
            $this->info("Limiting to {$limit} movies");
        }

        // SAFETY: Hard limit to prevent accidentally processing too many movies
        // This protects against high API costs from the embedding service.
        // Comment out the lines below if you intentionally want to process more than 100 movies.
        $HARD_LIMIT = 100;
        $currentLimit = $query->toBase()->limit ?? PHP_INT_MAX;
        if ($currentLimit > $HARD_LIMIT) {
            $query->limit($HARD_LIMIT);
            $this->warn("Safety limit applied: Processing maximum of {$HARD_LIMIT} movies.");
            $this->warn("To process more, comment out the HARD_LIMIT in " . __FILE__);
        }

        $totalMovies = $query->count();

        if ($totalMovies === 0) {
            $this->info('No movies to process. Use --force to regenerate existing embeddings.');
            return 0;
        }

        $this->info("Found {$totalMovies} movies to process");
        $this->newLine();

        // Create progress bar
        $progressBar = $this->output->createProgressBar($totalMovies);
        $progressBar->setFormat('Processing: %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->setMessage('Starting...');
        $progressBar->start();

        $processedCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        // Process movies in chunks
        $query->chunk(self::BATCH_SIZE, function ($movies) use (&$processedCount, &$errorCount, &$skippedCount, $progressBar) {
            try {
                // Prepare texts for embedding
                $texts = [];

                foreach ($movies as $movie) {
                    $texts[] = $this->prepareMovieText($movie);
                }

                // Generate embeddings using VoyageAI service
                $result = $this->voyageAI->generateEmbeddings($texts);

                if ($result['success']) {
                    $embeddings = $result['embeddings'];

                    // Update each movie with its embedding
                    foreach ($movies as $index => $movie) {
                        if (isset($embeddings[$index]['embedding'])) {
                            $movie->embeddings = $embeddings[$index]['embedding'];
                            $movie->save();
                            $processedCount++;
                            $progressBar->setMessage("Processed: {$movie->title}");
                        } else {
                            $skippedCount++;
                            Log::warning("No embedding returned for movie: {$movie->title}");
                        }
                        $progressBar->advance();
                    }
                } else {
                    $errorCount += count($movies);
                    Log::error("Voyage AI API error: {$result['error']}");
                    $progressBar->setMessage("API Error - check logs");

                    // Still advance the progress bar for skipped movies
                    foreach ($movies as $movie) {
                        $progressBar->advance();
                    }
                }

                // Small delay to respect rate limits
                usleep(100000); // 0.1 second delay between batches

            } catch (\Exception $e) {
                $errorCount += count($movies);
                Log::error("Exception during embedding generation: " . $e->getMessage());
                $progressBar->setMessage("Error: " . $e->getMessage());

                // Still advance the progress bar for skipped movies
                foreach ($movies as $movie) {
                    $progressBar->advance();
                }
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('Embedding generation completed!');
        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['Processed Successfully', $processedCount],
                ['Errors', $errorCount],
                ['Skipped', $skippedCount],
                ['Total', $totalMovies],
            ]
        );

        if ($errorCount > 0) {
            $this->warn('Some movies failed to process. Check logs for details.');
        }

        return 0;
    }

    /**
     * Prepare movie text for embedding generation
     * Combines title and plot only for focused semantic search
     */
    private function prepareMovieText(Movie $movie): string
    {
        $parts = [];

        // Add title
        if (!empty($movie->title)) {
            $parts[] = "Title: {$movie->title}";
        }

        // Add plot (prefer fullplot if available)
        if (!empty($movie->fullplot)) {
            $parts[] = "Plot: {$movie->fullplot}";
        } elseif (!empty($movie->plot)) {
            $parts[] = "Plot: {$movie->plot}";
        }

        return implode("\n", $parts);
    }
}
