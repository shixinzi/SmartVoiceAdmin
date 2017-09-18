<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class LiveProgram extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'liveProgram';
    protected $fillable = ['channel_code', 'program_name', 'end_time', 'wiki_id', 'tags', 'episode', 'wiki_title'];
}
