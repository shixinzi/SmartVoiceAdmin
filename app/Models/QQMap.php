<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class QQMap extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'qqMap';
}
