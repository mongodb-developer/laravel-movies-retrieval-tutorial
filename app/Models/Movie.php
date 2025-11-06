<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Movie extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'movies';

    // MongoDB ObjectId is used as the primary key (_id)
    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        '_id',
        'title',
        'plot',
        'fullplot',
        'genres',
        'runtime',
        'cast',
        'directors',
        'writers',
        'countries',
        'languages',
        'released',
        'rated',
        'awards',
        'lastupdated',
        'year',
        'imdb',
        'type',
        'tomatoes',
        'poster',
        'num_mflix_comments',
        'embeddings',
    ];

    protected $casts = [
        'runtime' => 'integer',
        'num_mflix_comments' => 'integer',
        'year' => 'integer',
        'released' => 'datetime',
        'lastupdated' => 'string',
    ];

    // Note: Arrays like genres, cast, directors, languages, countries
    // are NOT cast as 'array' because MongoDB returns them natively as arrays
}
