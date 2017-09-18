<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Cache;

class Channel extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'channel';
    protected $fillable = ['name' ,'code', 'tags', 'logo', 'code_sd', 'memos' , 'sort'];
    protected $casts = [
        'sort' => 'integer',
    ];

    public static function getAllPluck($name = 'name', $key = 'code')
    {
        $key = 'mChannelGetAllPluck_'.$name.'_'.$key;
        if(!Cache::get($key)) {
            $plucks = Channel::all()->pluck($name, $key);
            Cache::put($key, $plucks, 120);
            return $plucks;
        } else {
            return Cache::get($key);
        }
    }

    public static function getOneByCode($code)
    {
        $key = 'mChannelGetOneByCode_'.$code;
        if(!Cache::get($key)) {
            $frist = Channel::where('code', $code)->first();
            Cache::put($key, $frist, 5);
            return $frist;
        } else {
            return Cache::get($key);
        }
    }

    public static function getAll()
    {
        $key = 'mChannelGetAll';
        if(!Cache::get($key)) {
            $alls = Channel::all();
            Cache::put($key, $alls, 5);
            return $alls;
        } else {
            return Cache::get($key);
        }
    }
}
