<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Cache;
use App\Models\Wiki;

class WikiHotLog extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'wikiHotLog';

    public static function getLiveWeekHotAvg($limit = 20)
    {
        $key = 'mWikiHotLogGetLiveWeekHotAvg_'.$limit;
        if(!Cache::has($key)) {
            $avgLogObj = WikiHotLog::where('type', 'liveWeekHotAvg')->first();
            if (!$avgLogObj || !$avgLogObj->logs) {
                return [];
            }
            $wikiArrs = [];
            $wiki_ids = array_slice($avgLogObj->logs, 0, $limit);
            foreach ($wiki_ids as $wiki_id => $rating) {
                \Log::info($wiki_id);
                $wikiObj = Wiki::find($wiki_id);
                \Log::info($wikiObj->title);
                if ($wikiObj) {
                    array_push($wikiArrs, [
                        'wiki_id' => $wikiObj->_id,
                        'title' => $wikiObj->title,
                        'model' => $wikiObj['model'],
                        'tags' => $wikiObj->tags,
                        'cover' => Wiki::getUrlForCover($wikiObj->cover),
                        'rating' => $rating,
                    ]);
                }
            }
            Cache::put($key, $wikiArrs, 60*2);
            return $wikiArrs;
        } else {
            return Cache::get($key);
        }
    }
}