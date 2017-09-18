<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class VoiceLocalCommand extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'voiceLocalCommand';

    protected $fillable = [
        'word',
        'target',
    ];
}
