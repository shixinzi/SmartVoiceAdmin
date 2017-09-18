<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Cache;

/*
 * 维基
 */
class Wiki extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'wiki';

    /**
     *
     *
     * @param $query
     * @param $gender
     * @return mixed
     */
    public function scopeWikiId($query, $gender)
    {
        if (!in_array($gender, ['m', 'f'])) {
            return $query;
        }

        return $query->whereHas('profile', function ($query) use ($gender) {
            $query->where('gender',  $gender);
        });
    }

    public static function getOneById($id)
    {
        if(!Cache::has("mWikiGetOne_".$id)) {
            $wiki = Wiki::find($id);
            Cache::put("mWikiGetOne_".$id, $wiki);
            return $wiki;
        } else {
            return Cache::get("mWikiGetOne_".$id);
        }
    }

    public static function countCaches()
    {
        if(!Cache::has('mWikiCountCaches')) {
            $coutns = [
                'all' => Wiki::count(),
                'film' => Wiki::where('model', 'film')->count(),
                'teleplay' => Wiki::where('model', 'teleplay')->count(),
                'television' => Wiki::where('model', 'television')->count(),
            ];
            Cache::put('mWikiCountCaches', $coutns);
            return $coutns;
        } else {
            return Cache::get('mWikiCountCaches');
        }
    }

    public static function formatToSubArray($wikiObj)
    {
        return ['wiki_id' => $wikiObj->_id,
            'title' => $wikiObj->title,
            'model' => $wikiObj->model,
            'tags' => $wikiObj->tags,
            'cover' => Wiki::getUrlForCover($wikiObj->cover),
        ];
    }

    protected static function formatToFullArray($wikiObj)
    {
        $wikiInfo = [];
        $wikiInfo['wiki_id'] = $wikiObj->_id;
        $wikiInfo['model'] = $wikiObj->model;
        $wikiInfo['title'] = $wikiObj->title;
        if ($wikiInfo['model'] == 'film' || $wikiInfo['model'] == 'teleplay') {
            $wikiInfo['tags'] = Wiki::checkWikiAttribute($wikiObj->tags);
            $wikiInfo['alias'] = Wiki::checkWikiAttribute($wikiObj->alias);
            $wikiInfo['director'] = Wiki::checkWikiAttribute($wikiObj->director);
            $wikiInfo['starring'] = Wiki::checkWikiAttribute($wikiObj->starring);
            $wikiInfo['released'] = Wiki::checkWikiAttribute($wikiObj->released);
            $wikiInfo['language'] = Wiki::checkWikiAttribute($wikiObj->language);
            $wikiInfo['country'] = Wiki::checkWikiAttribute($wikiObj->country);
            $wikiInfo['writer'] = Wiki::checkWikiAttribute($wikiObj->writer);
            $wikiInfo['distributor'] = Wiki::checkWikiAttribute($wikiObj->distributor);
            $wikiInfo['runtime'] = Wiki::checkWikiAttribute($wikiObj->runtime);
            $wikiInfo['produced'] = Wiki::checkWikiAttribute($wikiObj->produced);
            $wikiInfo['average'] = Wiki::checkWikiAttribute($wikiObj->average);
            $wikiInfo['aspect'] = Wiki::checkWikiAttribute($wikiObj->aspect);
            $wikiInfo['episodes'] = Wiki::checkWikiAttribute($wikiObj->episodes);
        } elseif ($wikiInfo['model'] == "television") {
            $wikiInfo['tags'] = Wiki::checkWikiAttribute($wikiObj->tags);
            $wikiInfo['alias'] = Wiki::checkWikiAttribute($wikiObj->alias);
            $wikiInfo['channel'] = Wiki::checkWikiAttribute($wikiObj->channel);
            $wikiInfo['host'] = Wiki::checkWikiAttribute($wikiObj->host);
            $wikiInfo['guest'] = Wiki::checkWikiAttribute($wikiObj->guest);
            $wikiInfo['play_time'] = Wiki::checkWikiAttribute($wikiObj->play_time);
            $wikiInfo['runtime'] = Wiki::checkWikiAttribute($wikiObj->runtime);
            $wikiInfo['language'] = Wiki::checkWikiAttribute($wikiObj->language);
            $wikiInfo['country'] = Wiki::checkWikiAttribute($wikiObj->country);
            $wikiInfo['aspect'] = Wiki::checkWikiAttribute($wikiObj->aspect);
        } elseif ($wikiInfo['model'] == 'actor') {
            $wikiInfo['english_name'] = Wiki::checkWikiAttribute($wikiObj->english_name);
            $wikiInfo['nickname'] = Wiki::checkWikiAttribute($wikiObj->nickname);
            $wikiInfo['sex'] = Wiki::checkWikiAttribute($wikiObj->sex);
            $wikiInfo['birthday'] = Wiki::checkWikiAttribute($wikiObj->birthday);
            $wikiInfo['birthplace'] = Wiki::checkWikiAttribute($wikiObj->birthplace);
            $wikiInfo['occupation'] = Wiki::checkWikiAttribute($wikiObj->occupation);
            $wikiInfo['nationality'] = Wiki::checkWikiAttribute($wikiObj->nationality);
            $wikiInfo['zodiac'] = Wiki::checkWikiAttribute($wikiObj->zodiac);
            $wikiInfo['bloodType'] = Wiki::checkWikiAttribute($wikiObj->bloodType);
            $wikiInfo['debut'] = Wiki::checkWikiAttribute($wikiObj->debut);
            $wikiInfo['height'] = Wiki::checkWikiAttribute($wikiObj->height);
            $wikiInfo['weight'] = Wiki::checkWikiAttribute($wikiObj->weight);
            $wikiInfo['region'] = Wiki::checkWikiAttribute($wikiObj->region);
        }

        $wikiInfo['cover'] = Wiki::getUrlForCover($wikiObj->cover);
        $wikiInfo['screenshots'] = Wiki::getUrlScreenshots($wikiObj->screenshots);
        return $wikiInfo;
    }

    public static function getUrlForCover($cover, $size = ['width' => 324, 'height' => 480])
    {
        return "http://image.epg.huan.tv/thumb/".$size['width']."/".$size['height']."/".$cover;
    }

    public static function getUrlScreenshots($screenshots, $size = ['width' => 420, 'height' => 236])
    {
        $return = [];
        if($screenshots && is_array($screenshots)) {
            foreach ($screenshots as $key => $screenshot) {
                $return[$key] = "http://image.epg.huan.tv/thumb/".$size['width']."/".$size['height']."/".$screenshot;
            }
        }
        return $return;
    }

    public static function checkWikiAttribute($attribute, $default = null)
    {
        if (isset($attribute) && $attribute) {
            return $attribute;
        } else {
            return $default;
        }
    }

    public static function getIgnoreIds()
    {
        return ['5170e50eed454b5c72000000'];
    }
}