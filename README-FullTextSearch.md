# Full-Text Search Implementation Guide

**Companion guide to the article: "Laravel MongoDB Full-Text Search Tutorial: The Art of the Relevancy"**

This document helps you navigate the full-text search implementation in this repository. If you're reading the article, use this guide to find the corresponding code and understand the architecture.

---

## Quick Navigation

| Article Section | Code Location | What to Look At |
|----------------|---------------|-----------------|
| "Defining the Search Index" | [`app/Console/Commands/CreateFullTextSearchIndex.php`](app/Console/Commands/CreateFullTextSearchIndex.php) | Index creation with field mappings |
| "Search Query Implementation" | [`app/Http/Controllers/MovieSearchTextController.php`](app/Http/Controllers/MovieSearchTextController.php) | Naive vs Weighted search endpoints |
| "Mastering Relevancy: Weighting & Boosting" | Lines 110-144 in controller | Layered title boosting strategy |
| Configuration | [`config/fulltext.php`](config/fulltext.php) | Index name, limits, field settings |
| MongoDB Service | [`app/Services/MongoDBService.php`](app/Services/MongoDBService.php) | Client connection and collection access |

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                    MongoDB Atlas                         │
│  ┌────────────────────────────────────────────────┐    │
│  │  sample_mflix.movies Collection                 │    │
│  │  • 21,349 movie documents                       │    │
│  │  • Fields: title, plot, cast, directors, etc.  │    │
│  └────────────────────────────────────────────────┘    │
│                                                          │
│  ┌────────────────────────────────────────────────┐    │
│  │  Lucene Full-Text Search Index                  │    │
│  │  • Index Name: "movies_fulltext_index"          │    │
│  │  • Indexed Fields: title, plot, fullplot,       │    │
│  │    cast, directors                              │    │
│  │  • Type: search (Lucene-based)                  │    │
│  └────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────┘
                          ↕
        ┌─────────────────────────────────┐
        │   Laravel Application            │
        │                                  │
        │   ┌──────────────────────────┐  │
        │   │  CLI Command             │  │
        │   │  php artisan             │  │
        │   │  fulltext:create-index   │  │
        │   └──────────────────────────┘  │
        │             ↓                    │
        │   ┌──────────────────────────┐  │
        │   │  MongoDBService          │  │
        │   │  • getClient()           │  │
        │   │  • getCollection()       │  │
        │   └──────────────────────────┘  │
        │             ↓                    │
        │   ┌──────────────────────────┐  │
        │   │  API Endpoints           │  │
        │   │  POST /api/search-text   │  │
        │   │    (weighted)            │  │
        │   │  POST /api/search-text-  │  │
        │   │    naive (baseline)      │  │
        │   └──────────────────────────┘  │
        └─────────────────────────────────┘
```

---

## Setup Checklist (Follow Article Steps)

After completing the article's prerequisites, verify your setup:

### 1. Environment Configuration

Check your `.env` file has these values:

```bash
# MongoDB Atlas connection
DB_CONNECTION=mongodb
DB_DSN=mongodb+srv://USERNAME:PASSWORD@cluster.mongodb.net/sample_mflix?retryWrites=true&w=majority
DB_DATABASE=sample_mflix

# Application name
APP_NAME=my-laravel-app
```

### 2. Create the Search Index

```bash
php artisan fulltext:create-index
```

**What this does:**
- Connects to MongoDB Atlas
- Checks if index already exists
- Creates Lucene search index on: `title`, `plot`, `fullplot`, `cast`, `directors`
- Uses `dynamic: false` mapping (only specified fields are indexed)

**Code reference:** [`app/Console/Commands/CreateFullTextSearchIndex.php:100-111`](app/Console/Commands/CreateFullTextSearchIndex.php#L100-L111)

**Expected output:**
```
Creating full-text search index for movies collection...

✓ Full-text search index created successfully!

┌───────────────┬──────────────────────────────────────┐
│ Configuration │ Value                                 │
├───────────────┼──────────────────────────────────────┤
│ Index Name    │ movies_fulltext_index                 │
│ Collection    │ movies                                │
│ Search Fields │ title, plot, fullplot, cast, directors│
│ Index Type    │ search (Lucene)                       │
└───────────────┴──────────────────────────────────────┘
```

### 3. Start Laravel Server

```bash
php artisan serve
```

**Note:** Depending on your environment, the base URL will differ:

| Environment | Example URL |
|-------------|-------------|
| Local PHP | `http://localhost:8000` |
| Codespaces | `https://[unique-id]-8000.app.github.dev` |
| Docker | `http://127.0.0.1:8080` |

For the rest of this guide, we'll use `{{BASE_URL}}` as a placeholder. Replace it with your actual URL.

---

## Understanding the Search Implementations

### Naive Search (Baseline)

**Endpoint:** `POST /api/search-text-naive`

**What it does:** Simple text search across all indexed fields with **equal weighting**.

**Code:** [`app/Http/Controllers/MovieSearchTextController.php:18-74`](app/Http/Controllers/MovieSearchTextController.php#L18-L74)

**Key line:**
```php
Search::text(
    path: ['title', 'plot', 'fullplot', 'cast', 'directors'],
    query: $query
)
```

**Test it:**
```bash
curl -X POST {{BASE_URL}}/api/search-text-naive \
  -H "Content-Type: application/json" \
  -d '{"query": "The Godfather"}'
```

---

### Weighted Search (Optimized)

**Endpoint:** `POST /api/search-text`

**What it does:** Field-specific weighting with **layered title boosting strategy**.

**Code:** [`app/Http/Controllers/MovieSearchTextController.php:88-190`](app/Http/Controllers/MovieSearchTextController.php#L88-L190)

**The Boosting Strategy (lines 110-144):**

```php
Search::compound(
    should: [
        // LAYER 1: Exact phrase matching on title
        Search::phrase(path: 'title', query: $query, score: ['boost' => ['value' => 10]]),

        // LAYER 2: Fuzzy text matching on title
        Search::text(path: 'title', query: $query, score: ['boost' => ['value' => 7]]),

        // Actor-based searches
        Search::text(path: 'cast', query: $query, score: ['boost' => ['value' => 5]]),

        // Content discovery
        Search::text(path: 'plot', query: $query, score: ['boost' => ['value' => 3]]),
        Search::text(path: 'directors', query: $query, score: ['boost' => ['value' => 2]]),
        Search::text(path: 'fullplot', query: $query, score: ['boost' => ['value' => 1]]),
    ]
)
```

**Why this works:**

| Query Type | Phrase Match (10x) | Text Match (7x) |
|------------|-------------------|-----------------|
| "The Godfather" (exact title) | ✅ Matches | ✅ Matches |
| "Godfather sequel" (partial) | ❌ No match | ✅ Matches |
| "Tom Hanks" (actor) | ❌ No match | ❌ No match |

**Test it:**
```bash
curl -X POST {{BASE_URL}}/api/search-text \
  -H "Content-Type: application/json" \
  -d '{"query": "The Godfather"}'
```

**Compare results:** Run both naive and weighted with the same query to see the difference!

---

## Key Implementation Patterns

### 1. Separation of Concerns

**Infrastructure (one-time setup):**
```php
// Uses native MongoDB PHP driver
$collection->createSearchIndex($definition);
```
See: [`CreateFullTextSearchIndex.php:101`](app/Console/Commands/CreateFullTextSearchIndex.php#L101)

**Application queries (runtime):**
```php
// Uses Laravel Eloquent with Search builder
Movie::query()->aggregate()->search(...)->get();
```
See: [`MovieSearchTextController.php:107`](app/Http/Controllers/MovieSearchTextController.php#L107)

**Why this matters:** MongoDB Laravel package intentionally has no Eloquent method for index creation. Schema operations use the native driver; queries use Eloquent.

---

### 2. Fail-Fast Configuration

**No silent defaults** - Missing configuration throws exceptions immediately rather than falling back to defaults.

```php
// Example: No fallback values in config calls
$indexName = config('fulltext.index.name');  // Will be null if not configured
$limit = config('fulltext.search.limit');    // No '10' default parameter
```

**Philosophy:** Explicit failure > silent misconfiguration. Tutorial code should teach correct patterns.

---

## Testing & Comparison

### Compare Naive vs Weighted Search

**Query: "The Godfather"**

```bash
# Naive search
curl -X POST {{BASE_URL}}/api/search-text-naive \
  -H "Content-Type: application/json" \
  -d '{"query": "The Godfather"}' | jq '.results[0:3][] | {title, score}'

# Weighted search
curl -X POST {{BASE_URL}}/api/search-text \
  -H "Content-Type: application/json" \
  -d '{"query": "The Godfather"}' | jq '.results[0:3][] | {title, score}'
```

**Expected improvement:** Exact title "The Godfather" ranks #1 in weighted, may be #2-3 in naive.

---

## Running Tests

```bash
php artisan test
```

**Full-text search test coverage:**
- Configuration validation (`tests/Unit/FullTextConfigTest.php`)
- MongoDBService `appName` validation (`tests/Unit/MongoDBServiceTest.php`)
- Naive search endpoint (`tests/Feature/ApiEndpointsTest.php`)
- Weighted search endpoint with correct weight structure
- CLI index creation command

**All tests:** 41 tests, 170 assertions

---

## Configuration Reference

### `config/fulltext.php`

```php
return [
    'index' => [
        'name' => env('FULLTEXT_INDEX_NAME', 'movies_fulltext_index'),
    ],
    'search' => [
        'limit' => env('FULLTEXT_SEARCH_LIMIT', 10),
        'num_candidates' => env('FULLTEXT_NUM_CANDIDATES', 100),
    ],
];
```

**Customize in `.env`:**
```bash
FULLTEXT_INDEX_NAME=my_custom_index
FULLTEXT_SEARCH_LIMIT=20
FULLTEXT_NUM_CANDIDATES=200
```

---

## Production Considerations

**Current implementation is tutorial-optimized** (hard-coded weights for clarity).

For production applications, consider:

1. **Extract field weights to config:**
   ```php
   'fields' => [
       'phrase' => ['title' => 10],
       'text' => ['title' => 7, 'cast' => 5, 'plot' => 3, 'directors' => 2, 'fullplot' => 1]
   ]
   ```

2. **Build Search operators dynamically:**
   ```php
   foreach (config('fulltext.fields.phrase') as $field => $weight) {
       $shouldClauses[] = Search::phrase(...);
   }
   ```

3. **Benefits:**
   - Single source of truth for field weights
   - Easier A/B testing of different boost values
   - Centralized field management

See discussion in [`MovieSearchTextController.php:76-87`](app/Http/Controllers/MovieSearchTextController.php#L76-L87) for details.

---

### Laravel & PHP Resources

- **Laravel MongoDB Package:** [mongodb/laravel-mongodb on GitHub](https://github.com/mongodb/laravel-mongodb)
- **Official Test Suite:** [AtlasSearchTest.php Reference](https://github.com/mongodb/laravel-mongodb/blob/5.x/tests/AtlasSearchTest.php)

---

## Troubleshooting

**Index creation fails:**
- Check `APP_NAME` is set in `.env` ("devrel-article-search-series-php-laravel" by default)
- Verify MongoDB Atlas cluster is accessible
- Ensure IP is whitelisted in Atlas network access

**Search returns no results:**
- Confirm index status: `php artisan fulltext:create-index` (should show "already exists")
- Wait 1-2 minutes after index creation (Atlas initial index build)
- Check collection has data: `curl {{BASE_URL}}/api/mongodb-test`

**Wrong results ranking:**
- Use weighted search endpoint: `/api/search-text` (not naive)
- Compare naive vs weighted to see the difference
- Check response includes `"search_type": "weighted"`

---

**Ready to experiment?** Try modifying the boost values in [`MovieSearchTextController.php:113-143`](app/Http/Controllers/MovieSearchTextController.php#L113-L143) and see how it affects search results!
