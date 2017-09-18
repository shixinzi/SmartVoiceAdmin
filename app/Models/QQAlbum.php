<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Models\QQAlbumVideo;

class QQAlbum extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'qqAlbum';

    protected $fillable = [
        'type',
        'album_id',
        'album_name',
        'en_name',
        'alias_name',
        'update_time',
        'season',
        'cpr_companyname',
        'album_verpic',
        'album_horpic',
        'genre',
        'sub_genre',
        'sub_type',
        'area',
        'year',
        'language',
        'director',
        'actor',
        'publish_time',
        'is_clip',
        'fee',
        'is_show',
        'play_url',
        'guests',
        'episode_total',
        'episode_updated',
        'score',
        'album_desc',
        'focus',
        'pay_status',
        'vip_type',
        'category_map',
        'tag',
        'copyright_expiration_time',
        'real_pubtime',
        'online_time'
    ];

    public function videos()
    {
        return $this->hasMany(QQAlbumVideo::class, 'album_id', 'album_id');
    }
}