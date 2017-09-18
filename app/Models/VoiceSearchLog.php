<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class VoiceSearchLog extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'voiceSearchLog';
    protected $fillable = ['voiceText' , 'created_at'];
    public $timestamps = false;
}
