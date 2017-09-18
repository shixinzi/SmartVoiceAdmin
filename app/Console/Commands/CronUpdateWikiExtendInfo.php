<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Log;
use Cache;
use App\Common\Tools;
use App\Models\Program;
use App\Models\Wiki;
use App\Models\WikiFormer;
use App\Models\WikiFollow;
use App\Models\WikiHotLog;
use App\Jobs\SyncWikiFromHuan;


/**
 * Class CronUpdateWikiExtendInfo
 * 每日计算wiki的扩展数据,例如 维基热度,可回看wiki,可预告wiki
 *
 * @package App\Console\Commands
 */
class CronUpdateWikiExtendInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:UpdateWikiExtendInfo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->getHotListFromHuan();
        $this->getWikiAverageHotWeek();
        $this->getWikiFormers();
        $this->getWikiFollows();
    }

    public function getHotListFromHuan()
    {
        $app_key = 'a34b402649f04d249d7ed352c1717771';
        $securitty_key = '21cd854a03ad4637b159fa7d909cebc8';
        $api_url = 'http://bigdata.huan.tv/tv/v1';
        $timestamp = time()*1000;
        $dates = Tools::getDatesByNums('-7,-6,-5,-4,-3,-2,-1');
        $ignoreIds = Wiki::getIgnoreIds();
        foreach($dates as $date) {
            $hotLogObj = WikiHotLog::where('type', 'live')->where('date', $date)->first();
            if (!$hotLogObj) {
                $query_string = "{date:\"" . $date . "\",timestamp:\"" . $timestamp . "\",app_key:\"" . $app_key . "\"}";
                $message_digest = md5($query_string . $securitty_key);
                $wikiIdHots = [];
                try {
                    $client = new \GuzzleHttp\Client();
                    $response = $client->request('GET', $api_url . "/program/market_shares?query=" . $query_string . "&message_digest=" . $message_digest);
                    $htmlContent = $response->getBody()->getContents();
                    $jsonContent = \GuzzleHttp\json_decode($htmlContent, true);
                    if ($jsonContent && isset($jsonContent['programs'])) {
                        foreach ($jsonContent['programs'] as $program) {
                            $wiki_id = $program['wiki_id'];
                            $wiki_rating = intval($program['audience_rating']);
                            $this->info($wiki_id . "\t" . $wiki_rating);
                            if(in_array($wiki_id, $ignoreIds)) {
                                continue;
                            }
                            if (!isset($wikiIdHots[$wiki_id]) || (isset($wikiIdHots[$wiki_id]) && $wikiIdHots[$wiki_id] < $wiki_rating)) {
                                $wikiIdHots[$wiki_id] = $wiki_rating;
                            }
                        }
                    }
                    if ($wikiIdHots) {
                        $hotLogObj = new WikiHotLog();
                        $hotLogObj->type = 'live';
                        $hotLogObj->date = $date;
                        $hotLogObj->logs = $wikiIdHots;
                        $hotLogObj->save();
                    }
                } catch (\GuzzleHttp\Exception\ConnectException $e) {
                    $this->error($api_url . ' ConnectException!');
                    Log::error($api_url . ' ConnectException!');
                } catch (\Exception $e) {
                    $this->error($api_url . ' unknow Exception!');
                    Log::error($api_url . ' unknow Exception!');
                }
            }
        }
    }

    public function getWikiAverageHotWeek()
    {
        $wikiDaysArr = [];
        $days = Tools::getDatesByNums('-7,-6,-5,-4,-3,-2,-1');
        foreach($days as $day) {
            $log = WikiHotLog::where('type', 'live')->where('date', $day)->first();
            if($log && $log->logs) {
                foreach($log->logs as $wiki_id => $rating) {
                    $wikiDaysArr[$wiki_id][$day] = $rating;
                }
            }
        }
        $wikiAvgsArr = [];
        foreach($wikiDaysArr as $wiki_id => $wikiDayRow) {
            if($wikiDayRow && count($wikiDayRow) > 0) {
                $Avg = intval(array_sum($wikiDayRow) / count($wikiDayRow));
                $AvgPlus = intval($Avg * (10 + count($wikiDayRow)) / 10);
                $wikiAvgsArr[$wiki_id] = $AvgPlus;
            }
        }
        $avgLogObj = WikiHotLog::where('type', 'liveWeekHotAvg')->first();
        if(!$avgLogObj) {
            $avgLogObj = new WikiHotLog();
        }
        $avgLogObj->type = 'liveWeekHotAvg';
        $avgLogObj->logs = $wikiAvgsArr;
        $avgLogObj->save();
        Cache::put('wikiLiveWeekHotAvg', $wikiAvgsArr, 12*60);
    }

    public function getWikiFormers()
    {
        $dayFormers = [];
        $ignoreIds = Wiki::getIgnoreIds();
        for($i = -6; $i< 0; $i++ ) {
            array_push($dayFormers, date("Y-m-d", strtotime($i." day")));
        }
        WikiFormer::where([])->delete();
        $wikiWeekHog_ids = Cache::get('wikiLiveWeekHotAvg');
        $wiki_ids = array_pluck(Program::whereIn('date', $dayFormers)->groupBy('wiki_id')->get(['wiki_id']), 'wiki_id');
        foreach($wiki_ids as $wiki_id) {
            if(!$wiki_id || in_array($wiki_id, $ignoreIds)) continue;
            $wikiObj = Wiki::find($wiki_id);
            if ($wikiObj) {
                $former = new WikiFormer();
                $former->wiki_id = $wiki_id;
                $former->wiki_title = $wikiObj->title;
                $former->wiki_model = $wikiObj->model;
                $former->wiki_tags = $wikiObj->tags;
                $former->wiki_cover = $wikiObj->cover;
                if (array_key_exists($wiki_id, $wikiWeekHog_ids)) {
                    $former->rating = $wikiWeekHog_ids[$wiki_id];
                }
                $former->save();
            } else {
                dispatch(new SyncWikiFromHuan([
                    'wiki_id' => $wiki_id,
                ]));
                $this->info($wiki_id."++++++");
            }
        }
    }

    public function getWikiFollows()
    {
        $dayFormers = [];
        $ignoreIds = Wiki::getIgnoreIds();
        for($i = 0; $i< 6; $i++ ) {
            array_push($dayFormers, date("Y-m-d", strtotime($i." day")));
        }
        WikiFollow::where([])->delete();
        $wikiWeekHog_ids = Cache::get('wikiLiveWeekHotAvg');
        $wiki_ids = array_pluck(Program::whereIn('date', $dayFormers)->groupBy('wiki_id')->get(['wiki_id']), 'wiki_id');
        foreach($wiki_ids as $wiki_id) {
            if(!$wiki_id || in_array($wiki_id, $ignoreIds)) continue;
            $wikiObj = Wiki::find($wiki_id);
            if ($wikiObj) {
                $former = new WikiFollow();
                $former->wiki_id = $wiki_id;
                $former->wiki_title = $wikiObj->title;
                $former->wiki_model = $wikiObj->model;
                $former->wiki_tags = $wikiObj->tags;
                $former->wiki_cover = $wikiObj->cover;
                if (array_key_exists($wiki_id, $wikiWeekHog_ids)) {
                    $former->rating = $wikiWeekHog_ids[$wiki_id];
                }
                $former->save();
            } else {
                dispatch(new SyncWikiFromHuan([
                    'wiki_id' => $wiki_id,
                ]));
                $this->info($wiki_id."++++++");
            }
        }
    }
}
