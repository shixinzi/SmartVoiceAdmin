<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Program extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'program';
    protected $fillable = ['name' ,'day', 'start_time', 'end_time', 'wiki_id', 'tags', 'episode', 'created_at', 'updated_at'];


    public function scopeChannelDay($query, $p)
    {
        return $query->where('date',  $p['date'])->where('channel_code', $p['channel_code']);
    }
}
