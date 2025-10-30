# Laravel Books Retrieval API with Vector Search Tutorial

A practical tutorial project demonstrating how to **replace traditional database queries with semantic vector search** in a realistic public library application scenario.

## Project Purpose

This repository accompanies an article about implementing vector search in Laravel applications. It showcases a real-world use case: building a book search API for a public library that goes beyond simple keyword matching to understand the semantic meaning of search queries.

**Key Learning Objectives:**
- Integrate MongoDB Atlas Vector Search with Laravel
- Implement semantic search using embeddings (Voyage AI)
- Compare traditional full-text search vs. vector search
- Build a production-ready book search API

## Tech Stack

- **Framework**: Laravel 12
- **Database**: MongoDB Atlas (cloud-hosted)
- **Vector Embeddings**: Voyage AI (planned)
- **Search Technologies**:
  - MongoDB Atlas Vector Search (semantic search)
  - MongoDB Atlas Search (full-text search)

## Current Implementation Status

### Completed Features

- MongoDB Atlas integration with Laravel
- Book model with MongoDB Eloquent
- API endpoint infrastructure
- Full-text search implementation using Lucene
- Vector search index creation
- Basic vector search endpoint (awaiting embeddings)

### API Endpoints

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| `/api/hello` | GET | ✅ | Test endpoint |
| `/api/getbook_isbn/{isbn}` | GET | ✅ | Retrieve book by ISBN |
| `/api/create-vector-index` | GET/POST | ✅ | Create vector search index (1408 dimensions) |
| `/api/create-fulltext-search-index` | GET/POST | ✅ | Create Lucene full-text search index |
| `/api/get-books-fulltext/{search}` | GET | ✅ | Search books by title/synopsis (keyword) |
| `/api/book-search-vector` | POST | ⚠️ | Semantic search (needs embedding generation) |

## What's Next

### High Priority

1. **Voyage AI Integration**
   - Configure Voyage AI API credentials
   - Implement query-to-embedding conversion service
   - Batch re-generate embeddings for all existing books in MongoDB
   - Update vector index if dimensions differ from current 1408

2. **Complete Vector Search**
   - Integrate embedding generation into `/api/book-search-vector`
   - Test semantic search with real queries
   - Compare results with full-text search

### Future Enhancements

- Add pagination for search results
- Implement hybrid search (combining full-text + vector search)
- Add filtering by genre, year, publisher
- Create CRUD endpoints for book management
- Add rate limiting and authentication
- Performance optimization and caching

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- MongoDB PHP extension (`pecl install mongodb`)
- MongoDB Atlas account

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
```

4. Add MongoDB Atlas credentials to `.env`
```env
DB_CONNECTION=mongodb
DB_DSN=mongodb+srv://username:password@cluster.mongodb.net/database?retryWrites=true&w=majority
DB_DATABASE=laravel_books
```

5. Start development server
```bash
php artisan serve
```

6. Test the API
```bash
curl http://localhost:8000/api/hello
```

## Project Structure

- [routes/api.php](routes/api.php) - API endpoint definitions
- [app/Models/Book.php](app/Models/Book.php) - MongoDB Book model
- [config/database.php](config/database.php) - MongoDB configuration
- `CLAUDE.md` - Detailed development notes (not in repo)

## MongoDB Schema

Books collection structure:
- `_id`: ISBN (primary key)
- `title`: Book title
- `authors`: Array of author objects
- `genres`: Array of genre strings
- `synopsis`: Book description
- `embeddings`: Vector embeddings (1408 dimensions)
- Additional fields: pages, year, cover, publisher, reviews, etc.

## About the Tutorial Article

This project demonstrates the transition from traditional database queries to semantic search in a practical library application context. By following along, you'll learn:

- When vector search makes sense vs. traditional search
- How to implement production-ready vector search with Laravel
- Best practices for MongoDB Atlas integration
- Real-world embedding generation workflows

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
