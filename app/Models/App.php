<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class App extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'app';

    protected $fillable = ['name' , 'package_name', 'version_name', 'abbr'];
}
