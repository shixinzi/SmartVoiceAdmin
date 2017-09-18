<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class ChannelHotLog extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'channelHotLog';
    protected $fillable = ['timestamp' ,'attentions'];
    public $timestamps = false;
}
