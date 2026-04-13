<?php

use Illuminate\Support\Facades\Route;
use App\Models\Book;
use App\Models\Movie;
use App\Services\VoyageAIService;
use App\Http\Controllers\MongoDbTestController;
use App\Http\Controllers\EmbeddingModelInfoController;
use App\Http\Controllers\EmbeddingModelVectorizeController;
use App\Http\Controllers\GetMovieByTitleController;
use App\Http\Controllers\MovieSearchVectorController;
use App\Http\Controllers\MovieSearchTextController;

Route::get('/hello', function () {
    return response()->json([
        'response' => 'hello world'
    ]);
});

Route::get('/mongodb-test', MongoDbTestController::class);

Route::get('/embedding-model-info', EmbeddingModelInfoController::class);

Route::get('/embedding-model-vectorize/{input}', EmbeddingModelVectorizeController::class);

Route::get('/get-movie-by-title/{title}', GetMovieByTitleController::class);

Route::post('/movie-search-vector', MovieSearchVectorController::class);

Route::post('/search-text-naive', [MovieSearchTextController::class, 'naive']);

Route::post('/search-text', [MovieSearchTextController::class, 'weighted']);
