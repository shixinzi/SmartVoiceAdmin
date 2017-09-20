<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class HdpChannel extends Eloquent
{
    //http://www.hdplive.net/channellist.xml
    protected $connection = 'mongodb';
    protected $collection = 'hdpChannel';

    protected $fillable = ['name' , 'num', 'type' ,'channel_code'];

}
