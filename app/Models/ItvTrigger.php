<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class ItvTrigger extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'itvTrigger';

    protected $fillable = [
        'channel_no', 'date_time', 'blogic_code', 'banner_code',
        'banner_url', 'TVCF_duration', 'show_banner_dur', 'tab_code_range_first_time', 'tab_code_range_last_time'
    ];
}
