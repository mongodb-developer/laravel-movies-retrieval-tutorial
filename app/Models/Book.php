<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Book extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'books';

    // ISBN is used as the primary key (_id)
    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        '_id',
        'title',
        'authors',
        'genres',
        'pages',
        'year',
        'synopsis',
        'cover',
        'attributes',
        'totalInventory',
        'available',
        'binding',
        'language',
        'publisher',
        'longTitle',
        'reviews',
    ];

    protected $casts = [
        'pages' => 'integer',
        'year' => 'integer',
        'totalInventory' => 'integer',
        'available' => 'integer',
    ];
}
