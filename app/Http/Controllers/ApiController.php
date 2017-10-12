<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QQAlbum;
use App\Models\QQAlbumVideo;
use App\Models\Channel;
use App\Models\Wiki;
use App\Models\WikiFollow;
use App\Models\WikiFormer;
use App\Models\Program;
use App\Models\LiveProgram;
use App\Models\HdpChannel;
use App\Models\App;
use App\Models\VoiceSearchLog;
use App\Models\VoiceLocalCommand;
use Log;
use Cache;
use XS;
use App\Common\Tools;

class ApiController extends Controller
{
    protected $action;
    protected $developer;
    protected $user;
    protected $device;
    protected $param;

    public $backJson = [
        'status' => 0,
        'message' => ''
    ];

    protected function index(Request $request)
    {
        return "The Json Server accepts POST requests only.";
    }

    protected function v1Post(Request $request)
    {
        if (!$this->checkInput($request)) {
            return response()->json($this->backJson);
        }
        $method = $this->action;
        $this->$method();
        return response()->json($this->backJson);
    }

    protected function checkInput(Request $request)
    {
        $jsonContent = file_get_contents('php://input');
        if (!$jsonContent) {
            $this->setErrArray(1000, '未知数据');
            return false;
        }
        $jsonObj = json_decode($jsonContent, true);
        if (!$jsonObj || JSON_ERROR_NONE != json_last_error()) {
            $this->setErrArray(1001, '未知数据格式');
            return false;
        }
        if (!isset($jsonObj['action']) || !$jsonObj['action'] || !$jsonObj['action'] || !in_array($jsonObj['action'], $this->getAllowActions())) {
            $this->setErrArray(1002, '未定义或者错误的方法');
            return false;
        }
        $this->action = $jsonObj['action'];
        $this->user = isset($jsonObj['user']) ? $jsonObj['user'] : null;
        $this->param = isset($jsonObj['param']) ? $jsonObj['param'] : null;
        return true;
    }

    protected function setOkArray($message)
    {
        $this->backJson['status'] = 0;
        $this->backJson['message'] = $message;
    }

    protected function setErrArray($status, $message)
    {
        $this->backJson['status'] = $status;
        $this->backJson['message'] = $message;
    }

    protected function getAllowActions()
    {
        return [
            'GetChannelCategory', //获取频道分类
            'GetChannels', //获取频道列表
            'GetChannelsByRecommended', //获取推荐的频道列表
            'GetLivePrograms', //获取当前正在直播的节目列表
            'GetProgramsOfDateByChannel', //获取频道指定日期节目列表
            'GetProgramsByWiki', //获取wiki指定时间段节目列表
            'SearchPrograms', //根据关键字获取指定时间段节目列表
            'GetProgramsByRecommended', //获取指定时间内推荐的节目列表
            'SetLikeChannelByUser', //设置用户喜欢的频道(如果已经存在，则取消喜欢)
            'GetLikeChannelsByUser', //获取用户喜欢的频道列表
            'SetLikeWikiByUser', //上报用户喜欢的wiki(如果已经存在，则取消喜欢)
            'GetLikeWikisByUser', //获取用户喜欢的wiki列表
            'GetLikeProgramsByWiki', //获取用户喜欢的wiki指定时间段节目列表
            'GetSearchRecommends', //获取搜索推荐
            'SearchWikis', //根据关键字搜索wiki列表
            'GetWikiInfo', //获取wiki详情
            'OrderProgramByUser', //预约节目
            'UnOrderProgramByUser', //取消预约
            'GetProgramOrdersByUser', //获取预约列表
            'GetMessagesByUser',             //获取用户的节目提醒
            'GetSystemConfig', //获取系统配置
            'SearchByVoiceText', //通过语意来返回结果
            'GetHotWikiFollows', //获取热门回看wiki
            'GetHotWikiFormers', //获取热门预告wiki,
            'GetHotVods', //获取热门点播
            'GetQQAlbumInfo', //获取QQAlbum信息
        ];
    }

    protected function SearchByVoiceText()
    {
        if (!isset($this->param['text'])) {
            $this->setErrArray(1011, '不完善的参数text');
            return false;
        }
        $text = Tools::filterSearchText($this->param['text']);
        VoiceSearchLog::create(['voiceText' => $text, 'created_at' => time()]);

        $pregMatchs = [
            '/^我(要|想)看(\S+)/' => "searchTVAndVod",
            '/^我(要|想)打开(\S+)/' => "searchApp",
            '/^我(要|想)听(\S+)/' => "searchMusic",
        ];

        foreach ($pregMatchs as $pregMatch => $function) {
            if (preg_match($pregMatch, $text, $matches)) {
                $this->$function($text, $matches);
                return false;
            }
        }

        $this->setErrArray(1002, '没有找到你想要的结果!');
        return false;
    }

    protected function GetSystemConfig()
    {
        $this->backJson['data'] = [
            'Debug' => false,
            'DefaultLiveApp' => 'hdp',
            'DefaultVodApp' => 'opentv',
            'Vocie' => [
                'LocalCommands' => VoiceLocalCommand::all()->map(function ($item, $key) {
                    return ['word' => $item->word, 'taget' => $item->target];
                }),
            ]
        ];
        return true;
    }

    protected function searchTVAndVod($text)
    {
        if (preg_match('/^我(要|想)看(\S+)/', $text, $matches) && isset($matches[2])) {
            $key = trim($matches[2]);
            $channelObjs = Channel::where("name", $key)->limit(10)->get();
            if ($channelObjs && count($channelObjs) > 0) {
                $channels = [];
                foreach ($channelObjs as $key => $channelObj) {
                    $channels[$key] = [
                        'model' => 'channel',
                        'name' => $channelObj->name,
                        'code' => $channelObj->code,
                        'logo' => $this->getChannelLogo($channelObj->logo),
                        'tags' => $channelObj->tags,
                        'hot' => $channelObj->hot,
                        'targetActions' => $this->getTargetActionObjsByChannelCode($channelObj->code),
                        'liveProgram' => $this->getLiveProgramByChannelCode($channelObj->code)
                    ];
                }
                $this->backJson['datas'] = $channels;
                return true;
            }
            $liveProgramObjs = LiveProgram::where("program_name", $key)->orWhere('wiki_title', $key)->limit(10)->get();
            if ($liveProgramObjs && count($liveProgramObjs) > 0) {
                \Log::info('search liveProgram');
                $livePrograms = [];
                foreach ($liveProgramObjs as $key => $liveProgramObj) {
                    $livePrograms[$key] = [
                        'model' => 'live',
                        'programName' => $liveProgramObj->program_name,
                        'channelCode' => $liveProgramObj->channel_code,
                        'startTime' => date('Y-m-d H:i:s', $liveProgramObj->start_time),
                        'endTime' => date('Y-m-d H:i:s', $liveProgramObj->end_time),
                        'wikiID' => $liveProgramObj->wiki_id,
                        "wikiTitle" => $liveProgramObj->wiki_title,
                        "wikiCover" => $this->getWikiCover($liveProgramObj->wiki_cover),
                        'tags' => $liveProgramObj->tags,
                        'hot' => $liveProgramObj->hot,
                        'targetActions' => $this->getTargetActionObjsByChannelCode($liveProgramObj->channel_code),
                    ];
                }
                $this->backJson['datas'] = $livePrograms;
                return true;
            }

            if (preg_match('/^(\S+)第(\S+)(集|期)/', $key, $matches2)) {
                Log::info($matches2[1] . "\t" . $matches2[2]);
                $key = trim($matches2[1]);
                $num = Tools::cnNum2Num(trim($matches2[2]));
            } else {
                Log::info("-------------");
                $num = null;
            }
            $key = str_replace(['的'], '', $key);
            $xs = new \XS(config_path('./album.ini'));
            $docs = $xs->search->setSort('score')->setLimit(10)->search($key);
            $count = $xs->search->lastCount;
            if (!$count) {
                $this->setErrArray(1002, '没有找到你想要的结果!');
                return false;
            }
            if (!$num) {
                $datas = [];
                foreach ($docs as $key => $doc) {
                    $datas[$key] = $this->formatXsAlbum2AI($doc);
                }
                $this->backJson['datas'] = $datas;
                return true;
            } else {
                $doc = $docs[0];
                $qqAlbumVideo = QQAlbumVideo::where("album_id", $doc->album_id)
                    ->where('play_order', $num)->first();
                if (!$qqAlbumVideo) {
                    $this->setErrArray(1002, '没有找到你想要的结果!');
                    return false;
                } else {
                    $data = [];
                    $data[0] = $this->formatQQAlbumVideo2AI($qqAlbumVideo);
                    $this->backJson['datas'] = $data;
                    return true;
                }
            }

        } else {
            $this->setErrArray(1002, '没有找到你想要的结果!');
            return false;
        }
    }

    protected function searchApp($text, $matches)
    {
        if (isset($matches[2]) || preg_match('/^我(要|想)打开(\S+)/', $text, $matches)) {
            $key = $matches[2];
            $apps = App::where('name', $key)->get();
            if (!$apps) {
                $this->setErrArray(1002, '没有找到你想要的结果!');
                return false;
            }
            $data = [];
            foreach ($apps as $key => $app) {
                $data[$key][0] = $this->formatApp2AI($app);
            }
            $this->backJson['datas'] = $data;
            return true;
        } else {
            $this->setErrArray(1002, '没有找到你想要的结果!');
            return false;
        }
    }

    protected function searchMusic()
    {
        return [
            [
                "active_model" => "music",
                "target_type" => "androidApp",
                'package_name' => 'com.tencent.music'
            ]
        ];
    }

    protected function formatChannel2AI($channel)
    {
        return [
            'active_model' => 'channel',
            'active_type' => 'activity',
            'name' => $channel->name,   //mainActivity,activity,action,broadcast,service,url
            'package_name' => 'hdpfans.com',
            'class_name' => 'hdp.player.StartActivity',
            'extra' => [
                ['key' => "ChannelNum", 'value' => $channel->num]
            ]
        ];
    }

    protected function formatXsAlbum2AI($doc)
    {
        return [
            'model' => 'album',
            'name' => $doc->albumName,
            'verpic' => $doc->albumVerpic,
            'horpic' => $doc->albumHorpic,
            'targetActions' => [
                [
                    'active_type' => 'action',
                    'package_name' => 'com.ktcp.tvvideo',
                    'action_name' => 'om.tencent.qqlivetv.open',
                    'extra' => [
                        ['uri' => 'tenvideo2://?action=1&cover_id=' . $doc->album_id . '&pull_from=101503']
                    ]
                ]
            ]
        ];
    }

    protected function formatQQAlbum2AI($album)
    {

        return [
            'active_type' => 'action',
            'package_name' => 'com.ktcp.tvvideo',
            'action_name' => 'om.tencent.qqlivetv.open',
            'extra' => [
                ['uri' => 'tenvideo2://?action=1&cover_id=' . $album->album_id . '&pull_from=101503']
            ]
        ];
    }

    protected function formatQQAlbumVideo2AI($video)
    {
        return [
            'model' => 'albumVideo',
            'name' => $video->video_name,
            'album_id' => $video->album_id,
            'video_id' => $video->video_id,
            'verpic' => $video->video_verpic,
            'horpic' => $video->video_horpic,
            'targetActions' => [
                [
                    'active_type' => 'action',
                    'package_name' => 'com.ktcp.video',
                    'action_name' => 'com.tencent.qqlivetv.open',
                    'extra' => [
                        ['uri' => 'uri="tenvideo2://?action=7&video_id=' . $video->video_id . '&video_name=' . $video->video_name . '&cover_id=' . $video->album_id . '&cover_pulltype=1"']
                    ]
                ]
            ]
        ];
    }

    protected function formatApp2AI($app)
    {
        return [
            'active_model' => 'app',
            'active_type' => 'mainActivity',
            'name' => $app->name,
            'package_name' => $app->package_name,
            'version_name' => $app->version_name,
        ];
    }

    protected function GetChannelCategory()
    {
        $this->backJson['data'] = [
            'cctv' => '央视',
            'tv' => '卫视',
            'local' => '本地',
            'hd' => '高清',
            'pay' => '收费'
        ];
        return true;
    }

    protected function GetChannels()
    {
        $channelObjs = Channel::where([])->orderBy("hot", 'desc')->get();
        $channels = [];
        $showlive = isset($this->param['showlive']) ? Tools::strToBoolean($this->param['showlive']) : null;
        if ($channelObjs) {
            foreach ($channelObjs as $key => $channelObj) {
                array_push($channels, [
                    'model' => 'channel',
                    'name' => $channelObj->name,
                    'code' => $channelObj->code,
                    'logo' => $this->getChannelLogo($channelObj->logo),
                    'tags' => $channelObj->tags,
                    'hot' => $channelObj->hot,
                    'targetActions' => $this->getTargetActionObjsByChannelCode($channelObj->code),
                    'liveProgram' => $showlive ? $this->getLiveProgramByChannelCode($channelObj->code) : null
                ]);
            }
        }
        $this->backJson['total'] = count($channels);
        $this->backJson['datas'] = $channels;
        return true;
    }

    protected function GetChannelsByRecommended()
    {
        $channelObjs = Channel::where('istop', 1)->orderBy("hot", 'desc')->get();
        $channels = [];
        $showlive = isset($this->param['showlive']) ? Tools::strToBoolean($this->param['showlive']) : null;
        foreach ($channelObjs as $key => $channelObj) {
            array_push($channels, [
                'model' => 'channel',
                'name' => $channelObj->name,
                'code' => $channelObj->code,
                'logo' => $this->getChannelLogo($channelObj->logo),
                'tags' => $channelObj->tags,
                'hot' => $channelObj->hot,
                'targetActions' => $this->getTargetActionObjsByChannelCode($channelObj->code),
                'liveProgram' => $showlive ? $this->getLiveProgramByChannelCode($channelObj->code) : null
            ]);
        }
        $this->backJson['total'] = count($channels);
        $this->backJson['datas'] = $channels;
        return true;
    }

    protected function getChannelLogo($logo)
    {
        return $logo;
        //return 'http://image.epg.huan.tv/2012/12/12/' . $logo;
    }

    protected function GetLivePrograms()
    {
        $page = 1;
        $pagesize = 10;
        $skip = ($page - 1) * $pagesize;
        $livePrograms = [];
        $liveProgramCount = LiveProgram::count();
        $liveProgramObjs = LiveProgram::orderBy('hot', 'desc')->skip($skip)->take($pagesize)->get();
        foreach ($liveProgramObjs as $key => $liveProgramObj) {
            $livePrograms[$key] = [
                'model' => 'live',
                'programName' => $liveProgramObj->program_name,
                'channelCode' => $liveProgramObj->channel_code,
                'startTime' => date('Y-m-d H:i:s', $liveProgramObj->start_time),
                'endTime' => date('Y-m-d H:i:s', $liveProgramObj->end_time),
                'wikiID' => $liveProgramObj->wiki_id,
                "wikiTitle" => $liveProgramObj->wiki_title,
                "wikiCover" => $this->getWikiCover($liveProgramObj->wiki_cover),
                'tags' => $liveProgramObj->tags,
                'hot' => $liveProgramObj->hot,
                'targetActions' => $this->getTargetActionObjsByChannelCode($liveProgramObj->channel_code),
            ];
        }
        $this->backJson['page'] = $page;
        $this->backJson['pagesize'] = $pagesize;
        $this->backJson['total'] = $liveProgramCount;
        $this->backJson['pagetotal'] = intval(($liveProgramCount - 1) / $pagesize) + 1;
        $this->backJson['datas'] = $livePrograms;
        return true;
    }

    protected function getWikiCover($cover, $size = ['width' => 324, 'height' => 480])
    {
        return "http://image.epg.huan.tv/thumb/" . $size['width'] . "/" . $size['height'] . "/" . $cover;
    }

    protected function getScreenshots($screenshots, $size = ['width' => 420, 'height' => 236])
    {
        $return = [];
        if ($screenshots && is_array($screenshots)) {
            foreach ($screenshots as $key => $screenshot) {
                $return[$key] = "http://image.epg.huan.tv/thumb/" . $size['width'] . "/" . $size['height'] . "/" . $screenshot;
            }
        }
        return $return;
    }

    protected function GetProgramsOfDateByChannel()
    {
        $channel_code = isset($this->param['channel_code']) ? $this->param['channel_code'] : 'cctv1';
        $date = isset($this->param['date']) ? $this->param['date'] : date('Y-m-d');
        $programs = [];
        $programObjs = Program::where('channel_code', $channel_code)->where('date', $date)->get();
        foreach ($programObjs as $key => $programObj) {
            $program = [
                'programName' => $programObj->name,
                'channelCode' => $programObj->channel_code,
                'startTime' => date('Y-m-d H:i:s', $programObj->start_time),
                'endTime' => date('Y-m-d H:i:s', $programObj->end_time),
                'tags' => $programObj->tags,
            ];
            if ($programObj->wiki_id && ($wikiObj = Wiki::getOneById($programObj->wiki_id))) {
                $program["wikiID"] = $programObj->wiki_id;
                $program["wikiTitle"] = $wikiObj->title;
                $program["wikiCover"] = $this->getWikiCover($wikiObj->cover);
            }
            $programs[$key] = $program;
        }
        $this->backJson['page'] = 1;
        $this->backJson['pagesize'] = 50;
        $this->backJson['total'] = count($programs);
        $this->backJson['pagetotal'] = 1;
        $this->backJson['datas'] = $programs;
    }

    protected function GetHotWikiFollows()
    {
        $page = 1;
        $pagesize = 12;
        $skip = ($page - 1) * $pagesize;
        $wikiFollows = WikiFollow::orderBy('rating', 'desc')->skip($skip)->take($pagesize)->get();
        foreach ($wikiFollows as $key => $wikiFollow) {
            $wiki = [
                'wikiID' => $wikiFollow->wiki_id,
                'wikiTitle' => $wikiFollow->wiki_title,
                'wikiModel' => $wikiFollow->wiki_model,
                'wikiTags' => $wikiFollow->tags,
                'wikiCover' => $this->getWikiCover($wikiFollow->wiki_cover),
                'rating' => $wikiFollow->rating,
            ];
            $wikis[$key] = $wiki;
        }
        $this->backJson['page'] = $page;
        $this->backJson['pagesize'] = $pagesize;
        $this->backJson['total'] = count($wikis);
        $this->backJson['pagetotal'] = 1;
        $this->backJson['datas'] = $wikis;
    }

    protected function GetHotWikiFormers()
    {
        $page = 1;
        $pagesize = 12;
        $skip = ($page - 1) * $pagesize;
        $wikiFormers = WikiFormer::orderBy('rating', 'desc')->skip($skip)->take($pagesize)->get();
        foreach ($wikiFormers as $key => $wikiFormer) {
            $wiki = [
                'wikiID' => $wikiFormer->wiki_id,
                'wikiTitle' => $wikiFormer->wiki_title,
                'wikiModel' => $wikiFormer->wiki_model,
                'wikiTags' => $wikiFormer->tags,
                'wikiCover' => $this->getWikiCover($wikiFormer->wiki_cover),
                'rating' => $wikiFormer->rating,
            ];
            $wikis[$key] = $wiki;
        }
        $this->backJson['page'] = $page;
        $this->backJson['pagesize'] = $pagesize;
        $this->backJson['total'] = count($wikis);
        $this->backJson['pagetotal'] = 1;
        $this->backJson['datas'] = $wikis;
    }

    protected function GetHotVods()
    {
        $page = 1;
        $pagesize = 12;
        $skip = ($page - 1) * $pagesize;
        $type = isset($this->param['type']) ? $this->param['type'] : null;
        if ($type && in_array($type, ['movie', 'tv', 'doc', 'cartoon', 'variety'])) {
            $albums = QQAlbum::where('type', $type)->orderBy('hot_num', 'desc')->skip($skip)->take($pagesize)->get();
        } else {
            $albums = QQAlbum::orderBy('hot_num', 'desc')->skip($skip)->take($pagesize)->get();
        }
        foreach ($albums as $key => $album) {
            $wiki = [
                'model' => 'album',
                'album_id' => $album->album_id,
                'album_name' => $album->album_name,
                'type' => $album->type,
                'tags' => $album->sub_type,
                'album_verpic' => $album->album_verpic,
                'hot_num' => $album->hot_num,
                'targetActions' => [$this->formatQQAlbum2AI($album)],
            ];
            $wikis[$key] = $wiki;
        }
        $this->backJson['page'] = $page;
        $this->backJson['pagesize'] = $pagesize;
        $this->backJson['total'] = count($wikis);
        $this->backJson['pagetotal'] = 1;
        $this->backJson['datas'] = $wikis;
    }

    protected function getTargetActionObjsByChannelCode($channel_code)
    {
        $key = 'ChannelTargetActions_' . $channel_code;
        if (Cache::has($key)) {
            return Cache::get($key);
        } else {
            $targetActions = [];
            $targetActionObjs = HdpChannel::where("channel_code", $channel_code)->get();
            if ($targetActionObjs) {
                foreach ($targetActionObjs as $targetActionObj) {
                    array_push($targetActions, $this->formatChannel2AI($targetActionObj));
                }
            }
            Cache::put($key, $targetActions);
            return $targetActions;
        }
    }

    protected function getLiveProgramByChannelCode($channel_code)
    {
        $liveProgram = LiveProgram::where('channel_code', $channel_code)->first();
        if ($liveProgram) {
            return [
                "name" => $liveProgram->program_name,
                "start_time" => date("Y-m-d H:i:s", $liveProgram->start_time),
                "end_time" => date("Y-m-d H:i:s", $liveProgram->end_time),
                "wiki_id" => $liveProgram->wiki_id,
                "wiki_title" => $liveProgram->wiki_title,
                "wiki_cover" => $this->getWikiCover($liveProgram->wiki_cover),
                "next_name" => "",
                "next_wiki_id" => ""
            ];
        } else {
            return null;
        }
    }

    public function GetQQAlbumInfo()
    {
        if (!isset($this->param['album_id'])) {
            $this->setErrArray(1000, '错误的参数album_id');
            return false;
        }
        $album_id = $this->param['album_id'];
        $albumObj = QQAlbum::where('album_id', $album_id)->first();
        if (!$albumObj) {
            $this->setErrArray(1000, '没有找到相关QQAlbum');
            return false;
        }
        $data = $albumObj->toArray();
        $albumVideoObjs = QQAlbumVideo::where('album_id', $album_id)
            ->orderBy('play_order', 'asc')->get();
        $videos = [];
        if ($albumVideoObjs) {
            foreach ($albumVideoObjs as $albumVideoObj) {
                $video = $albumVideoObj->toArray();
                $video['targetActions'][0] = $this->formatQQAlbumVideo2AI($albumVideoObj);
                array_push($videos, $video);
            }
        }
        $data['videos'] = $videos;
        $this->backJson['data'] = $data;
        return false;
    }
}
