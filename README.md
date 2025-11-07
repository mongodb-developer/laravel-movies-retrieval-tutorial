# Laravel Movies Retrieval API with Vector Search Tutorial

A practical tutorial project demonstrating how to **replace traditional database queries with semantic vector search** in a realistic movie database application scenario.

## Project Purpose

This repository accompanies an article about implementing vector search in Laravel applications. It showcases a real-world use case: building a movie search API that goes beyond simple keyword matching to understand the semantic meaning of search queries.

**Key Learning Objectives:**
- Integrate MongoDB Atlas Vector Search with Laravel
- Implement semantic search using embeddings (Voyage AI)
- Build a production-ready movie search API

## Tech Stack

- **Framework**: Laravel 12
- **Database**: MongoDB Atlas (cloud-hosted, sample_mflix database)
- **Vector Embeddings**: Voyage AI (voyage-3-lite model, 512 dimensions)
- **Search Technology**: MongoDB Atlas Vector Search (semantic search)

## Current Implementation Status

### Completed Features

- MongoDB Atlas integration with Laravel (sample_mflix database)
- Movie model with MongoDB Eloquent
- Voyage AI service integration (voyage-3-lite model)
- Vector embeddings generation via CLI
- Vector search index creation (512 dimensions, cosine similarity)
- Semantic search endpoint with query vectorization

## API Endpoints

### Core Endpoints

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| `/api/hello` | GET | ✅ | Test endpoint to verify API routing |

### Movie Query Endpoints

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| `/api/get-movie-by-title/{title}` | GET | ✅ | Retrieve movie by exact title match |

### Voyage AI Embedding Endpoints

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| `/api/embedding-model-info` | GET | ✅ | Test Voyage AI connection and get model info |
| `/api/embedding-model-vectorize/{input}` | GET | ✅ | Generate embedding for a single text input |

### Search Index Management

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| `/api/create-vector-index` | GET/POST | ✅ | Create vector search index (512 dimensions, cosine similarity) |

### Search Endpoints

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| `/api/movie-search-vector` | POST | ✅ | Semantic search using vector embeddings |

**Example `/api/movie-search-vector` Request:**
```bash
curl -X POST http://localhost:8000/api/movie-search-vector \
  -H "Content-Type: application/json" \
  -d '{"query": "outlaws on the run from law enforcement"}'
```

**Example Response:**
```json
{
  "query": "outlaws on the run from law enforcement",
  "results": [
    {
      "_id": {"$oid": "573a1390f29313caabcd42e8"},
      "title": "The Great Train Robbery",
      "plot": "A group of bandits stage a brazen train hold-up...",
      "score": 0.8234567
    }
  ],
  "count": 10,
  "embedding_model": "voyage-3-lite",
  "vector_dimensions": 512
}
```

## CLI Commands

### Generate Embeddings

Generate vector embeddings for movies using Voyage AI:

```bash
# Generate embeddings for movies without embeddings (up to 100)
php artisan embeddings:generate

# Force regenerate embeddings for all movies (up to 100)
php artisan embeddings:generate --force

# Generate embeddings for a specific number of movies
php artisan embeddings:generate --limit=20
```

**Command Details:**
- **Location**: [app/Console/Commands/GenerateEmbeddings.php](app/Console/Commands/GenerateEmbeddings.php)
- **Batch Processing**: Processes 10 movies at a time to avoid API timeouts
- **Safety Limit**: Hard limit of 100 movies per invocation to prevent high API costs
- **Text Preparation**: Combines movie title and plot for embedding generation
- **Progress Tracking**: Shows progress bar with current movie being processed

**Flags:**
- `--force`: Regenerate embeddings even if they already exist
- `--limit=N`: Process only N movies (subject to 100 max safety limit)

### Delete Embeddings

Delete all embeddings from the movies collection (useful for debugging):

```bash
# Delete embeddings with confirmation prompt
php artisan embeddings:delete

# Delete embeddings without confirmation
php artisan embeddings:delete --force
```

**Command Details:**
- **Location**: [app/Console/Commands/DeleteEmbeddings.php](app/Console/Commands/DeleteEmbeddings.php)
- **Operation**: Uses MongoDB `$unset` operation to remove embeddings field
- **Verification**: Checks remaining embeddings after deletion

## 3 Steps to Vector Search

Vector search enables semantic understanding of queries, finding relevant results based on meaning rather than exact keyword matches. Here's how it works in this project:

### Step 1: Create Data Embeddings

**What**: Convert your text data into numerical vectors that capture semantic meaning.

**How**: Use the Voyage AI service to generate 512-dimensional embeddings from movie titles and plots.

**Code Location**: [app/Console/Commands/GenerateEmbeddings.php](app/Console/Commands/GenerateEmbeddings.php)

**Key Implementation Details:**

1. **Text Preparation** ([GenerateEmbeddings.php:183-200](app/Console/Commands/GenerateEmbeddings.php#L183-L200)):
   ```php
   private function prepareMovieText(Movie $movie): string
   {
       $parts = [];

       if (!empty($movie->title)) {
           $parts[] = "Title: {$movie->title}";
       }

       if (!empty($movie->fullplot)) {
           $parts[] = "Plot: {$movie->fullplot}";
       } elseif (!empty($movie->plot)) {
           $parts[] = "Plot: {$movie->plot}";
       }

       return implode("\n", $parts);
   }
   ```

2. **Batch Processing** ([GenerateEmbeddings.php:102-154](app/Console/Commands/GenerateEmbeddings.php#L102-L154)):
   - Processes 10 movies at a time using Laravel's `chunk()` method
   - Calls Voyage AI API with batch of texts
   - Updates each movie document with its embedding array

3. **Voyage AI Service** ([app/Services/VoyageAIService.php](app/Services/VoyageAIService.php)):
   - Centralized API communication
   - Uses `voyage-3-lite` model (512 dimensions)
   - Method: `generateEmbeddings()` (supports single or batch processing)

**Run the command:**
```bash
php artisan embeddings:generate --limit=100
```

### Step 2: Create an Index

**What**: Create a MongoDB Atlas Vector Search index that enables efficient similarity searches.

**How**: Configure an index with the correct dimensions (512) and similarity function (cosine).

**Code Location**: [routes/api.php:104-171](routes/api.php#L104-L171)

**Key Implementation Details:**

1. **Index Configuration** ([routes/api.php:111-114](routes/api.php#L111-L114)):
   ```php
   // Get vector configuration from environment
   $vectorDimensions = (int) env('VECTOR_DIMENSIONS', 512);
   $vectorSimilarity = env('VECTOR_SIMILARITY', 'cosine');
   ```

2. **Index Creation** ([routes/api.php:133-151](routes/api.php#L133-L151)):
   ```php
   $result = $db->command([
       'createSearchIndexes' => $collection,
       'indexes' => [
           [
               'name' => $indexName,
               'type' => 'vectorSearch',
               'definition' => [
                   'fields' => [
                       [
                           'type' => 'vector',
                           'path' => 'embeddings',
                           'numDimensions' => $vectorDimensions,
                           'similarity' => $vectorSimilarity
                       ]
                   ]
               ]
           ]
       ]
   ]);
   ```

**Run the endpoint:**
```bash
curl http://localhost:8000/api/create-vector-index
```

**Environment Variables** (`.env`):
```env
VECTOR_DIMENSIONS=512
VECTOR_SIMILARITY=cosine
```

### Step 3: Search Using Vector Search

**What**: Convert search queries into vectors and find similar movies using semantic similarity.

**How**: Use Voyage AI to vectorize the query, then perform MongoDB `$vectorSearch` aggregation.

**Code Location**: [routes/api.php:110-192](routes/api.php#L110-L192)

**Key Implementation Details:**

1. **Query Vectorization** ([routes/api.php:120-138](routes/api.php#L120-L138)):
   ```php
   // Generate embedding for the query using VoyageAI
   $voyageAI = new VoyageAIService();
   $result = $voyageAI->generateEmbeddings([$query]);
   $queryVector = $result['embeddings'][0]['embedding'];
   ```

2. **Vector Search Pipeline** ([routes/api.php:150-174](routes/api.php#L150-L174)):
   ```php
   $pipeline = [
       [
           '$vectorSearch' => [
               'index' => $indexName,
               'path' => 'embeddings',
               'queryVector' => $queryVector,
               'numCandidates' => 100,
               'limit' => 10
           ]
       ],
       [
           '$project' => [
               '_id' => 1,
               'title' => 1,
               'plot' => 1,
               'fullplot' => 1,
               'genres' => 1,
               'year' => 1,
               'cast' => 1,
               'directors' => 1,
               'poster' => 1,
               'score' => ['$meta' => 'vectorSearchScore']
           ]
       ]
   ];

   $results = $db->$collection->aggregate($pipeline)->toArray();
   ```

**Run a search:**
```bash
curl -X POST http://localhost:8000/api/movie-search-vector \
  -H "Content-Type: application/json" \
  -d '{"query": "outlaws on the run from law enforcement"}'
```

**How It Works:**
1. User sends a natural language query (e.g., "outlaws on the run")
2. Query is vectorized using Voyage AI (same model as data embeddings)
3. MongoDB compares query vector to all movie embeddings using cosine similarity
4. Returns top 10 most similar movies with relevance scores
5. Movies are ranked by semantic similarity, not keyword matching

## What's Next

### Future Enhancements

- Add pagination for search results
- Add filtering by genre, year, cast
- Create CRUD endpoints for movie management
- Add rate limiting and authentication
- Performance optimization and caching
- Implement query result ranking and relevance tuning

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- MongoDB PHP extension (`pecl install mongodb`)
- MongoDB Atlas account with sample_mflix database loaded
- Voyage AI API key (get one at [voyageai.com](https://voyageai.com))

### Installation

1. Clone the repository
```bash
git clone <repository-url>
cd laravel-books-retrieval-api-tutorial
```

2. Install dependencies
```bash
composer install
```

3. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Add MongoDB Atlas and Voyage AI credentials to `.env`
```env
DB_CONNECTION=mongodb
DB_DSN=mongodb+srv://username:password@cluster.mongodb.net/sample_mflix?retryWrites=true&w=majority
DB_DATABASE=sample_mflix

VOYAGE_AI_API_KEY=your_voyage_ai_api_key_here
VECTOR_DIMENSIONS=512
VECTOR_SIMILARITY=cosine
```

5. Start development server
```bash
php artisan serve
```

6. Test the API
```bash
curl http://localhost:8000/api/hello
```

7. Generate embeddings for movies
```bash
php artisan embeddings:generate --limit=100
```

8. Create vector search index
```bash
curl http://localhost:8000/api/create-vector-index
```

9. Try a semantic search
```bash
curl -X POST http://localhost:8000/api/movie-search-vector \
  -H "Content-Type: application/json" \
  -d '{"query": "outlaws on the run from law enforcement"}'
```

## Project Structure

- [routes/api.php](routes/api.php) - API endpoint definitions
- [app/Models/Movie.php](app/Models/Movie.php) - MongoDB Movie model
- [app/Services/VoyageAIService.php](app/Services/VoyageAIService.php) - Voyage AI API integration
- [app/Console/Commands/GenerateEmbeddings.php](app/Console/Commands/GenerateEmbeddings.php) - CLI for generating embeddings
- [app/Console/Commands/DeleteEmbeddings.php](app/Console/Commands/DeleteEmbeddings.php) - CLI for deleting embeddings
- [config/database.php](config/database.php) - MongoDB configuration
- `CLAUDE.md` - Detailed development notes (not in repo)

## MongoDB Schema

Movies collection structure (sample_mflix database):
- `_id`: MongoDB ObjectId (primary key)
- `title`: Movie title
- `plot`: Short plot summary
- `fullplot`: Full plot description
- `genres`: Array of genre strings
- `cast`: Array of actor names
- `directors`: Array of director names
- `year`: Release year
- `runtime`: Movie runtime in minutes
- `rated`: MPAA rating
- `imdb`: Embedded document with rating, votes, id
- `tomatoes`: Embedded document with Rotten Tomatoes data
- `embeddings`: Vector embeddings (512 dimensions) - generated by this project

## About the Tutorial Article

This project demonstrates how to implement semantic vector search in a practical movie database application context. By following along, you'll learn:

- How to implement production-ready vector search with Laravel
- Best practices for MongoDB Atlas Vector Search integration
- Real-world embedding generation workflows with Voyage AI
- Building semantic search that understands meaning, not just keywords
- Efficient batch processing for embedding generation

# Semantic Search Query Suggestions

These search queries demonstrate the power of semantic/vector search by finding relevant movies **without using obvious keywords from titles or plots**. They showcase how the search understands meaning, concepts, and themes rather than just matching exact words.

## Themes & Concepts

### 1. "outlaws on the run from law enforcement"
- **Finds**: The Great Train Robbery (1903)
- **Why it works**: Understands "outlaws" = bandits, "law enforcement" = posse/sheriff
- **Keywords NOT used**: train, robbery, western

### 2. "wealth inequality and economic exploitation"
- **Finds**: A Corner in Wheat (1909)
- **Why it works**: Identifies economic injustice themes
- **Keywords NOT used**: wheat, tycoon, bread, poverty

### 3. "bringing drawings to life"
- **Finds**: Winsor McCay films, Gertie the Dinosaur (1914)
- **Why it works**: Conceptually understands animation = bringing drawings to life
- **Keywords NOT used**: animation, cartoon, dinosaur

### 4. "human trafficking and rescue mission"
- **Finds**: Traffic in Souls (1913)
- **Why it works**: Understands trafficking = kidnapping/prostitution ring
- **Keywords NOT used**: prostitution, kidnapping, crime

### 5. "prehistoric creature comes alive"
- **Finds**: Gertie the Dinosaur (1914)
- **Why it works**: "prehistoric creature" = dinosaur concept
- **Keywords NOT used**: dinosaur, animation, McCay

### 6. "rags to riches criminal empire"
- **Finds**: Little Caesar (1931), Scarface (1932)
- **Why it works**: Identifies gangster rise narrative arc
- **Keywords NOT used**: gangster, mob, bootlegger, mafia

### 7. "alcohol smuggling during prohibition"
- **Finds**: The Public Enemy (1931), Little Caesar (1931)
- **Why it works**: "alcohol smuggling" = bootlegging concept
- **Keywords NOT used**: bootlegging, prohibition specific terms

### 8. "forbidden love across social classes"
- **Finds**: A Woman of Paris (1923), The Italian (1915), Morocco (1930)
- **Why it works**: Identifies class-based romantic barriers
- **Keywords NOT used**: specific character names, locations

### 9. "performer with broken heart"
- **Finds**: Laugh, Clown, Laugh (1928), He Who Gets Slapped (1924)
- **Why it works**: Understands emotional state + profession
- **Keywords NOT used**: clown, circus, slapped

### 10. "newsroom chaos and deadline pressure"
- **Finds**: The Front Page (1931)
- **Why it works**: "newsroom" = newspaper office, "deadline pressure" = journalism stress
- **Keywords NOT used**: newspaper, reporter, journalist, press

## Emotional & Abstract Concepts

### 11. "sacrifice for a greater cause"
- **Finds**: Civilization (1916), Four Sons (1928), Wings (1927)
- **Why it works**: Identifies selfless/heroic narrative themes
- **Keywords NOT used**: war, soldier, battle

### 12. "identity hidden by disguise"
- **Finds**: Robin Hood (1922), Shanghai Express (1932)
- **Why it works**: Understands concealment and dual identity themes
- **Keywords NOT used**: mask, outlaw, secret

### 13. "redemption through love"
- **Finds**: Regeneration (1915), The Sin of Madelon Claudet (1931)
- **Why it works**: Identifies salvation/transformation narrative
- **Keywords NOT used**: redemption, salvation, reform

### 14. "small person against powerful system"
- **Finds**: Modern Times (1936), À Nous la Liberté (1931)
- **Why it works**: David vs. Goliath narrative structure
- **Keywords NOT used**: factory, worker, industrial

### 15. "childhood friendship tested by conflict"
- **Finds**: Wings (1927), Four Sons (1928)
- **Why it works**: Identifies relationship + external pressure themes
- **Keywords NOT used**: war, friends, aviator

## Settings & Atmospheres

### 16. "exotic Asian location with intrigue"
- **Finds**: Shanghai Express (1932), Red Dust (1932)
- **Why it works**: Understands geographic + mysterious atmosphere
- **Keywords NOT used**: Shanghai, China, Asia, train

### 17. "confinement and institutional life"
- **Finds**: The Big House (1930), Mädchen in Uniform (1931)
- **Why it works**: Identifies restricted/controlled environments
- **Keywords NOT used**: prison, jail, boarding school

### 18. "westward expansion adventure"
- **Finds**: The Big Trail (1930), The Iron Horse (1924)
- **Why it works**: American frontier movement concept
- **Keywords NOT used**: wagon, pioneer, frontier, west

### 19. "tropical paradise turns complicated"
- **Finds**: Tabu (1931), Red Dust (1932)
- **Why it works**: Idyllic setting disrupted by conflict
- **Keywords NOT used**: island, South Pacific, plantation

### 20. "battlefield brotherhood"
- **Finds**: Wings (1927), Four Sons (1928), All Quiet on the Western Front (1930)
- **Why it works**: Identifies wartime camaraderie theme
- **Keywords NOT used**: war, soldier, WWI, battle

## Why These Queries Work

These semantic search queries successfully find relevant movies because:

1. **Synonym Recognition**: The search understands "outlaws" = bandits, "law enforcement" = sheriff/posse
2. **Conceptual Understanding**: "Bringing drawings to life" is semantically understood as animation
3. **Thematic Matching**: Abstract concepts like "wealth inequality" match plot themes about economic injustice
4. **Emotional States**: "Broken heart" finds stories with emotional suffering and loss
5. **Professional Contexts**: "Newsroom chaos" correctly identifies journalism/media settings
6. **Narrative Structures**: "Rags to riches" identifies character progression arcs
7. **Atmospheric Understanding**: "Tropical paradise turns complicated" finds settings + conflict patterns



## Laravel Framework

Built on Laravel 12 - a web application framework with expressive, elegant syntax. Learn more at [laravel.com](https://laravel.com)

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
