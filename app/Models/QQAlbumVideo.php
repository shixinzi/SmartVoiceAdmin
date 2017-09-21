<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class QQAlbumVideo extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'qqAlbumVideo';

    protected $fillable = [
        'album_id',
        'video_id',
        'video_name',
        'play_order',
        'video_verpic',
        'video_horpic',
        'definition',
        'time_length',
        'video_url',
        'update_time',
        'head_time',
        'tail_time',
        'sub_genre',
        'sub_type',
        'publish_time',
        'is_clip',
        'full',
        'category_map',
        'drm',
        'partner_vid',
        'episode'
    ];

    protected $hidden = ["_id"];
}

