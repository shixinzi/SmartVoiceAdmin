<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class ChannelMatchDefine extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'channelMatchDefine';

    protected $fillable = ['channel_name' , 'channel_code', 'sp'];
}
