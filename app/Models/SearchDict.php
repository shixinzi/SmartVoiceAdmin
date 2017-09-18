<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class SearchDict extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'searchDict';
    protected $fillable = [
        "word", 'tf', 'idf', 'attr'
    ];
}