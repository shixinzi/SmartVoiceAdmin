<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class QQAlbumHotLog extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'qqAlbumHotLog';

    protected $fillable = ['album_name','album_score', 'album_id', 'date', 'hot_num'];
}
