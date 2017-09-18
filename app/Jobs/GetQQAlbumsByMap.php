<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Models\QQMap;
use App\Models\QQAlbum;
use App\Models\QQAlbumVideo;
use Log;
use Storage;
use SimpleXMLElement;

class GetQQAlbumsByMap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $qqMap;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(QQMap $qqMap)
    {
        $this->qqMap = $qqMap;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->qqMap->parseStatus == 1 ) {
            Log::error('this job is working in other thread!');
        } else {
            $now = time();
            $gap = 1;
            $fileName = str_replace("http://opentv.video.qq.com/ch/", "", $this->qqMap->loc);
            if ($this->qqMap->parseFinished && ($now - $this->qqMap->parseFinished < $gap)) {
                Log::error('this job is worked in one hour!');
            } else {
                $this->qqMap->parseStatus = 1;
                $this->qqMap->save();
                if (!Storage::exists("opentv/" . $fileName)) {
                    $htmlContent = $this->getMapXML();
                    if ($htmlContent) {
                        Storage::put("opentv/" . $fileName, $htmlContent);
                        if ($this->getQQAlbum($htmlContent)) {
                            $this->qqMap->parseStatus = 0;
                            $this->qqMap->parseFinished = $now;
                            $this->qqMap->save();
                        } else {
                            $this->qqMap->parseStatus = -1;
                            $this->qqMap->parseFinished = $now - 7200;
                            $this->qqMap->save();
                        }
                    }
                } else {
                    $htmlContent = Storage::get("opentv/" . $fileName);
                    if ($this->getQQAlbum($htmlContent)) {
                        $this->qqMap->parseStatus = 0;
                        $this->qqMap->parseFinished = $now;
                        $this->qqMap->save();
                    } else {
                        $this->qqMap->parseStatus = -1;
                        $this->qqMap->parseFinished = $now - 7200;
                        $this->qqMap->save();
                    }
                }
            }
        }
    }

    public function getMapXML()
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $this->qqMap->loc);
            $htmlContent = $response->getBody()->getContents();
            return $htmlContent;

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error($e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return null;
        }

    }

    public function getQQAlbum($htmlContent)
    {
        try {
            $movies = new SimpleXMLElement($htmlContent);
            if ($movies->albums->album) {
                foreach ($movies->albums->album as $albumElement) {
                    $albumArray = $this->albumElement2Array($this->qqMap->type, $albumElement);
                    $albumObj = QQAlbum::where('album_id', $albumArray['album_id'])->first();
                    if (!$albumObj) {
                        Log::info('insert album:' . $albumArray['album_id'] . "-" . $albumArray['album_name']);
                        QQAlbum::create($albumArray);
                    } else {
                        Log::info('update album:' . $albumArray['album_id'] . "-" . $albumArray['album_name']);
                        QQAlbum::create($albumArray);
                    }
                    if ($albumElement->videos->video) {
                        foreach ($albumElement->videos->video as $videoElement) {
                            $videoArray = $this->videoElement2Array($albumArray['album_id'], $videoElement);
                            $videoObj = QQAlbumVideo::where('album_id', $albumArray['album_id'])
                                ->where('video_id', $videoArray['video_id'])
                                ->first();
                            if (!$videoObj) {
                                Log::info('insert video:' . $videoArray['video_id'] . "-" . $videoArray['video_name']);
                                QQAlbumVideo::create($videoArray);
                            } else {
                                Log::info('update video:' . $videoArray['video_id'] . "-" . $videoArray['video_name']);
                                QQAlbumVideo::create($videoArray);
                            }
                        }
                    }
                }
                return true;
            } else {
                return false;
            }
        } catch (\ErrorException $e) {
            \Log::error($e->getFile() . "|" . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            \Log::error($e->getFile() . "|" . $e->getMessage());
            return false;
        }
    }

    public function albumElement2Array($type, $albumElement)
    {
        $keys = [
            'album_id',
            'album_name',
            'en_name',
            'alias_name',
            'update_time',
            'season',
            'cpr_companyname',
            'album_verpic',
            'album_horpic',
            'genre',
            'sub_genre',
            'sub_type',
            'area',
            'year',
            'language',
            'director',
            'actor',
            'publish_time',
            'is_clip',
            'fee',
            'is_show',
            'play_url',
            'guests',
            'episode_total',
            'episode_updated',
            'score',
            'album_desc',
            'focus',
            'pay_status',
            'vip_type',
            'category_map',
            'tag',
            'copyright_expiration_time',
            'real_pubtime',
            'online_time'
        ];
        $albumArray = ['type' => $type];
        foreach ($keys as $key) {
            $albumArray[$key] = trim(strval($albumElement->$key));
        }
        return $albumArray;
    }

    public function videoElement2Array($album_id, $videoElement)
    {
        $keys = [
            'video_id',
            'video_name',
            'play_order',
            'video_verpic',
            'video_horpic',
            'definition',
            'time_length',
            'video_url',
            'update_time',
            'head_time',
            'tail_time',
            'sub_genre',
            'sub_type',
            'publish_time',
            'is_clip',
            'full',
            'category_map',
            'drm',
            'partner_vid',
            'episode'
        ];
        $videoArray = ['album_id' => $album_id];
        foreach ($keys as $key) {
            $videoArray[$key] = trim(strval($videoElement->$key));
        }
        return $videoArray;
    }
}
