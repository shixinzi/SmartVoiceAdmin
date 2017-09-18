<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Log;
use App\Models\Wiki;

class SyncWikiFromHuan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $wikiObj = Wiki::find($this->data['wiki_id']);
            if($wikiObj) {
                return true;
            }
            $client = new \GuzzleHttp\Client([
                'base_uri' => config('app.huan.api_url')
            ]);
            $body = [
                'action' => 'GetWikiInfo',
                'developer' => [
                    'apikey' => config('app.huan.api_appkey'),
                    'secretkey' => config('app.huan.api_secretkey'),
                ],
                'user' => [
                    "userid" => "huan123456",
                ],
                'device' => [
                    'dnum' => 'huan123456',
                ],
                'param' => [
                    'wiki_id' => $this->data['wiki_id'],
                ]
            ];
            $response = $client->request('POST', '/json2', ['body' => json_encode($body)]);
            $htmlContent = $response->getBody()->getContents();
            $jsonContent = \GuzzleHttp\json_decode($htmlContent, true);
            if ($jsonContent && isset($jsonContent['media'])) {
                $wikiArr = $jsonContent['media'];
                Log::info($wikiArr['title']);
                $wikiObj = new Wiki();
                $wikiObj->_id = $wikiArr['id'];
                $wikiObj->title = $wikiArr['title'];
                $wikiObj['model'] = $wikiArr['model'];
                if ($wikiObj['model'] == 'actor') {
                    if(isset($wikiArr['info'])) {
                        $wikiObj->english_name = $this->checkWikiAttribute($wikiArr['info']['english_name']);
                        $wikiObj->nickname = $this->checkWikiAttribute($wikiArr['info']['nickname']);
                        $wikiObj->sex = $this->checkWikiAttribute($wikiArr['info']['sex']);
                        $wikiObj->birthday = $this->checkWikiAttribute($wikiArr['info']['birthday']);
                        $wikiObj->birthplace = $this->checkWikiAttribute($wikiArr['info']['birthplace']);
                        $wikiObj->occupation = $this->checkWikiAttribute($wikiArr['info']['occupation']);
                        $wikiObj->nationality = $this->checkWikiAttribute($wikiArr['info']['nationality']);
                        $wikiObj->zodiac = $this->checkWikiAttribute($wikiArr['info']['zodiac']);
                        $wikiObj->bloodType = $this->checkWikiAttribute($wikiArr['info']['bloodType']);
                        $wikiObj->debut = $this->checkWikiAttribute($wikiArr['info']['debut']);
                        $wikiObj->height = $this->checkWikiAttribute($wikiArr['info']['height']);
                        $wikiObj->weight = $this->checkWikiAttribute($wikiArr['info']['weight']);
                        $wikiObj->region = $this->checkWikiAttribute($wikiArr['info']['region']);
                    }
                } elseif ($wikiObj['model'] == 'film' || $wikiObj['model'] == 'teleplay') {
                    if(isset($wikiArr['info'])) {
                        $wikiObj->tags = $this->checkWikiAttribute($wikiArr['info']['tags']);
                        $wikiObj->alias = $this->checkWikiAttribute($wikiArr['info']['alias']);
                        $wikiObj->director = $this->checkWikiAttribute($wikiArr['info']['director']);
                        $wikiObj->starring = $this->checkWikiAttribute($wikiArr['info']['starring']);
                        $wikiObj->released = $this->checkWikiAttribute($wikiArr['info']['released']);
                        $wikiObj->language = $this->checkWikiAttribute($wikiArr['info']['language']);
                        $wikiObj->country = $this->checkWikiAttribute($wikiArr['info']['country']);
                        $wikiObj->writer = $this->checkWikiAttribute($wikiArr['info']['writer']);
                        $wikiObj->distributor = $this->checkWikiAttribute($wikiArr['info']['distributor']);
                        $wikiObj->runtime = $this->checkWikiAttribute($wikiArr['info']['runtime']);
                        $wikiObj->produced = $this->checkWikiAttribute($wikiArr['info']['produced']);
                        $wikiObj->average = $this->checkWikiAttribute($wikiArr['info']['average']);
                        $wikiObj->aspect = $this->checkWikiAttribute($wikiArr['info']['aspect']);
                        $wikiObj->episodes = $this->checkWikiAttribute($wikiArr['info']['episodes']);
                    }
                } elseif ($wikiObj['model'] == 'television') {
                    if(isset($wikiArr['info'])) {
                        $wikiObj->tags = $this->checkWikiAttribute($wikiArr['info']['tags']);
                        $wikiObj->alias = $this->checkWikiAttribute($wikiArr['info']['alias']);
                        $wikiObj->channel = $this->checkWikiAttribute($wikiArr['info']['channel']);
                        $wikiObj->host = $this->checkWikiAttribute($wikiArr['info']['host']);
                        $wikiObj->guest = $this->checkWikiAttribute($wikiArr['info']['guest']);
                        $wikiObj->play_time = $this->checkWikiAttribute($wikiArr['info']['play_time']);
                        $wikiObj->runtime = $this->checkWikiAttribute($wikiArr['info']['runtime']);
                        $wikiObj->language = $this->checkWikiAttribute($wikiArr['info']['language']);
                        $wikiObj->country = $this->checkWikiAttribute($wikiArr['info']['country']);
                        $wikiObj->aspect = $this->checkWikiAttribute($wikiArr['info']['aspect']);
                    }
                }
                $wikiObj->content = $this->checkWikiAttribute($wikiArr['description']);
                $wikiObj->cover = $this->getWikiCoverFromApi($wikiArr['posters']);
                $wikiObj->screenshots = $this->getWikiScreenshotsFromApi($wikiArr['screens']);
                $wikiObj->save();
            }

            Log::info($this->data['wiki_id']);

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            \Log::error($e->getMessage());
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    public function checkWikiAttribute($attribute, $default = null)
    {
        if (isset($attribute) && $attribute) {
            return $attribute;
        } else {
            return $default;
        }
    }

    public function getWikiCoverFromApi($cover)
    {
        if(isset($cover) && isset($cover[0]) && isset($cover[0]['url'])) {
            $url = explode("/", $cover[0]['url']);
            return $url[count($url)-1];
        } else {
            return null;
        }
    }

    public function getWikiScreenshotsFromApi($screens)
    {
        $as = [];
        if($screens && is_array($screens)) {
            foreach($screens as $screen) {
                if($screen && isset($screen['url'])) {
                    $url = explode("/", $screen['url']);
                    array_push($as, $url[count($url)-1]);
                }
            }
        }
        return $as;
    }
}
