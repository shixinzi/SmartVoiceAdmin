<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Models\Wiki;

class WikiFormer extends  Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'wikiFormer';

    public function wiki()
    {
        return $this->belongsTo(Wiki::class);
    }
}
