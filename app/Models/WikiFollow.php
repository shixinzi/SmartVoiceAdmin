<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Models\Wiki;

/**
 * 预告Wiki
 */
class WikiFollow extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'wikiFollow';

    public function wiki()
    {
        return $this->belongsTo(Wiki::class);
    }
}
