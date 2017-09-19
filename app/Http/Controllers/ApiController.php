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
        ];
    }

    protected function SearchByVoiceText()
    {
        if (!isset($this->param['text'])) {
            $this->setErrArray(1011, '不完善的参数text');
            return false;
        }
        $text = $this->param['text'];
        $pregMatchs = [
            '/^我(要|想)看(\S+)/' => "searchTVAndVod",
            '/^我(要|想)打开(\S+)/' => "searchApp",
            '/^我(要|想)听(\S+)/' => "searchMusic",
        ];

        VoiceSearchLog::create(['voiceText' => $text, 'created_at' => time()]);

        foreach ($pregMatchs as $pregMatch => $function) {
            if (preg_match($pregMatch, $text, $matches)) {
                $this->backJson['data'] = $this->$function($text, $matches);
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
            $channel = HdpChannel::where("name", $key)->first();
            if ($channel) {
                return $this->formatChannel2AI($channel);
            }
            if (preg_match('/^(\S+)第(\S+)(集|期)/', $key, $matches2)) {
                Log::info($matches2[1]."\t".$matches2[2]);
                $key = trim($matches2[1]);
                $num = Tools::cnNum2Num(trim($matches2[2]));
            } else {
                Log::info("-------------");
                $num = '1';
            }
            $qqAlbum = QQAlbum::where("album_name", $key)->first();
            if (!$qqAlbum) {
                $this->setErrArray(1002, '没有找到你想要的结果!');
                return false;
            }
            $qqAlbumVideo = QQAlbumVideo::where("album_id", $qqAlbum->album_id)
                ->where('play_order', $num)->first();
            if (!$qqAlbumVideo) {
                $this->setErrArray(1002, '没有找到你想要的结果!');
                return false;
            } else {
                return $this->formatQQAlbumVideo2AI($qqAlbumVideo);
            }
        } else {
            return false;
        }
    }

    protected function searchApp($text, $matches)
    {
        if (isset($matches[2]) || preg_match('/^我(要|想)打开(\S+)/', $text, $matches)) {
            Log:
            debug('searchApp' . $matches[2]);
            $key = $matches[2];
            $apps = App::where('name', $key)->get();
            return $this->formatApp2AI($apps);
        } else {
            Log::debug('no matches');
            return null;
        }
    }

    protected function searchMusic()
    {
        return [
            [
                "type" => "music",
                "target_type" => "androidApp",
                'package_name' => 'com.tencent.music'
            ]
        ];
    }

    protected function formatChannel2AI($channel)
    {
        return [
            [
                'type' => 'androidApp',
                'name' => $channel->name,
                'start_type' => 'mainActivity',   //activity,action,broadcast,service
                'package_name' => 'hdpfans.com',
                'class_name' => 'hdp.player.StartActivity',
                'extra' => [
                    ['key' => "ChannelNum", 'value' => $channel->num]
                ]
            ]
        ];
    }

    protected function formatQQAlbum2AI($album)
    {
        return [
            [
                'type' => 'androidApp',
                'name' => $album->name,
                'start_type' => 'action',   //activity,action,broadcast,service
                'package_name' => 'com.ktcp.video',
                'action_name' => 'com.tencent.qqlivetv.open',
                'extra' => [
                    ['uri' => 'uri="tenvideo2://?action=7&video_id=' . $album->album_id . '&video_name=xxx&cover_id=xxx&cover_pulltype=1"']
                ]
            ]
        ];
    }

    protected function formatQQAlbumVideo2AI($video)
    {

        return [
            [
                'type' => 'androidApp',
                'name' => $video->video_name,
                'start_type' => 'action',   //activity,action,broadcast,service
                'package_name' => 'com.ktcp.video',
                'action_name' => 'com.tencent.qqlivetv.open',
                'extra' => [
                    ['uri' => 'uri="tenvideo2://?action=7&video_id=' . $video->video_id . '&video_name=' . $video->video_name . '&cover_id=xxx&cover_pulltype=1"']
                ]
            ]
        ];
    }

    protected function formatApp2AI($apps)
    {
        $ai = [];
        if ($apps) {
            foreach ($apps as $app) {
                array_push($ai, [
                    'name' => $app->name,
                    'package_name' => $app->package_name,
                    'version_name' => $app->version_name,
                ]);
            }
        }
        return $ai;
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
        $channelObjs = Channel::where([])->get();
        $channels = [];
        foreach ($channelObjs as $key => $channelObj) {
            $channels[$key] = [
                'name' => $channelObj->name,
                'code' => $channelObj->code,
                'logo' => $this->getChannelLogo($channelObj->logo),
                'tags' => $channelObj->tags,
                'hot' => $channelObj->hot,
            ];
            if (isset($this->param['showlive']) && boolval($this->param['showlive'])) {
                $liveProgram = LiveProgram::where('channel_code', $channelObj->code)->first();
                if ($liveProgram) {
                    $channels[$key]['program'] = [
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
                    $channels[$key]['program'] = [];
                }
            }
        }
        $this->backJson['total'] = count($channels);
        $this->backJson['data'] = $channels;
        return true;
    }

    protected function GetChannelsByRecommended()
    {
        $channelCodes = ['dragontv', 'c39a7a374d888bce3912df71bcb0d580', '590e187a8799b1890175d25ec85ea352', '5dfcaefe6e7203df9fbe61ffd64ed1c4', 'antv'];
        $channelObjs = Channel::whereIn('code', $channelCodes)->get();
        $channels = [];
        foreach ($channelObjs as $key => $channelObj) {
            $channels[$key] = [
                'name' => $channelObj->name,
                'logo' => $this->getChannelLogo($channelObj->logo),
                'tags' => $channelObj->tags,
                'hot' => $channelObj->hot,
            ];
            if (isset($this->param['showlive']) && boolval($this->param['showlive'])) {
                $liveProgram = LiveProgram::where('channel_code', $channelObj->code)->first();
                if ($liveProgram) {
                    $channels[$key]['program'] = [
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
                    $channels[$key]['program'] = [];
                }
            }
        }
        $this->backJson['total'] = count($channels);
        $this->backJson['data'] = $channels;
        return true;
    }

    protected function getChannelLogo($logo)
    {
        return $logo;
        return 'http://image.epg.huan.tv/2012/12/12/' . $logo;
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
                'program_name' => $liveProgramObj->program_name,
                'channel_code' => $liveProgramObj->channel_code,
                'wiki_id' => $liveProgramObj->wiki_id,
                "wiki_title" => $liveProgramObj->wiki_title,
                "wiki_cover" => $this->getWikiCover($liveProgramObj->wiki_cover),
                'tags' => $liveProgramObj->tags,
                'hot' => $liveProgramObj->hot,
            ];
        }
        $this->backJson['page'] = $page;
        $this->backJson['pagesize'] = $pagesize;
        $this->backJson['total'] = $liveProgramCount;
        $this->backJson['pagetotal'] = intval(($liveProgramCount - 1) / $pagesize) + 1;
        $this->backJson['data'] = $livePrograms;
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
        foreach ($programObjs as $key => $programObjs) {
            $program = [
                'program_name' => $programObjs->name,
                'channel_code' => $programObjs->channel_code,
                'start_time' => date('Y-m-d H:i:s', $programObjs->start_time),
                'end_time' => date('Y-m-d H:i:s', $programObjs->end_time),
                'tags' => $programObjs->tags,
            ];
            if ($programObjs->wiki_id && ($wikiObj = Wiki::getOneById($programObjs->wiki_id))) {
                array_add($program, "wiki_id", $programObjs->wiki_id);
                array_add($program, "wiki_title", $wikiObj->title);
                array_add($program, "wiki_cover", $this->getWikiCover($wikiObj->cover));
            }
            $programs[$key] = $program;
        }
        $this->backJson['page'] = 1;
        $this->backJson['pagesize'] = 50;
        $this->backJson['total'] = count($programs);
        $this->backJson['pagetotal'] = 1;
        $this->backJson['data'] = $programs;
    }

    protected function GetHotWikiFollows()
    {
        $page = 1;
        $pagesize = 12;
        $skip = ($page - 1) * $pagesize;
        $wikiFollows = WikiFollow::orderBy('rating', 'desc')->skip($skip)->take($pagesize)->get();
        foreach ($wikiFollows as $key => $wikiFollow) {
            $wiki = [
                'wiki_id' => $wikiFollow->wiki_id,
                'wiki_title' => $wikiFollow->wiki_title,
                'wiki_model' => $wikiFollow->wiki_model,
                'wiki_tags' => $wikiFollow->tags,
                'wiki_cover' => $this->getWikiCover($wikiFollow->wiki_cover),
                'rating' => $wikiFollow->rating,
            ];
            $wikis[$key] = $wiki;
        }
        $this->backJson['page'] = $page;
        $this->backJson['pagesize'] = $pagesize;
        $this->backJson['total'] = count($wikis);
        $this->backJson['pagetotal'] = 1;
        $this->backJson['data'] = $wikis;
    }

    protected function GetHotWikiFormers()
    {
        $page = 1;
        $pagesize = 12;
        $skip = ($page - 1) * $pagesize;
        $wikiFormers = WikiFormer::orderBy('rating', 'desc')->skip($skip)->take($pagesize)->get();
        foreach ($wikiFormers as $key => $wikiFormer) {
            $wiki = [
                'wiki_id' => $wikiFormer->wiki_id,
                'wiki_title' => $wikiFormer->wiki_title,
                'wiki_model' => $wikiFormer->wiki_model,
                'wiki_tags' => $wikiFormer->tags,
                'wiki_cover' => $this->getWikiCover($wikiFormer->wiki_cover),
                'rating' => $wikiFormer->rating,
            ];
            $wikis[$key] = $wiki;
        }
        $this->backJson['page'] = $page;
        $this->backJson['pagesize'] = $pagesize;
        $this->backJson['total'] = count($wikis);
        $this->backJson['pagetotal'] = 1;
        $this->backJson['data'] = $wikis;
    }

    protected function GetHotVods()
    {
        $page = 1;
        $pagesize = 12;
        $skip = ($page - 1) * $pagesize;
        $type = isset($this->param['type']) ? $this->param['type'] : null;
        if ($type && in_array($type, ['movie', 'tv', 'doc', 'cartoon', 'variety', 'doc'])) {
            $albums = QQAlbum::where('type', $type)->orderBy('hot_num', 'desc')->skip($skip)->take($pagesize)->get();
        } else {
            $albums = QQAlbum::orderBy('hot_num', 'desc')->skip($skip)->take($pagesize)->get();
        }
        foreach ($albums as $key => $album) {
            $wiki = [
                'model' => 'qqalbum',
                'wiki_id' => $album->album_id,
                'wiki_title' => $album->album_name,
                'wiki_model' => $album->type,
                'wiki_tags' => $album->sub_type,
                'wiki_cover' => $album->album_verpic,
                'hot_num' => $album->hot_num,
            ];
            $wikis[$key] = $wiki;
        }
        $this->backJson['page'] = $page;
        $this->backJson['pagesize'] = $pagesize;
        $this->backJson['total'] = count($wikis);
        $this->backJson['pagetotal'] = 1;
        $this->backJson['data'] = $wikis;
    }
}
