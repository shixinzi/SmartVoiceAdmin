<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\PostMessage2Dingding;

class TestEcho extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:echo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $wikiIds = [];
    protected $index = 0;

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
//        $content = [
//            "msgtype" => "markdown",
//            "markdown" => [
//                "title" => "数据异常提醒",
//                "text" => "> 测试:2017年10月13日数据异常,请相关人员解决! [管理后台](http://www.gz-data.com/)"
//            ],
//            "at" => [
//                "atMobiles" => [
//                    "18511901056","18723130798"
//                ],
//                "isAtAll" => false
//            ]
//        ];
        $content = [
            'msgtype' => 'feedCard',
            'feedCard' => [
                'links' => [
                    [
                        'title' => '开心麻花新作:羞羞的铁拳',
                        'messageURL' => 'https://m.douban.com/movie/subject/27038183',
                        'picURL' => 'https://img3.doubanio.com/view/photo/l/public/p2499680835.webp'
                    ],
                    [
                        'title' => '泰国高分电影:天才枪手',
                        'messageURL' => 'https://m.douban.com/movie/subject/27024903',
                        'picURL' => 'https://img3.doubanio.com/view/movie_poster_cover/lpst/public/p2501863104.webp'
                    ],
                    [
                        'title' => '王晶最新导演:追龙',
                        'messageURL' => 'https://m.douban.com/movie/subject/26425068',
                        'picURL' => 'https://img3.doubanio.com/view/movie_poster_cover/lpst/public/p2499052494.webp'
                    ]
                ]
            ]
        ];
        dispatch(new PostMessage2Dingding($content));
        exit;


        \Excel::create('channels', function ($excel) {
            $excel->sheet('Sheetname', function ($sheet) {
                $channelObjs = \App\Models\Channel::orderBy('sort_id', 'asc')->get();
                $channels = [];
                foreach ($channelObjs as $key => $channelObj) {
                    $cols = ['tv_station_id', 'sort_id', 'name', 'code', 'memo', 'type', 'logo', 'lookcheck'];
                    $channel = [];
                    foreach ($cols as $col) {
                        $channel[$col] = $channelObj->$col ? $channelObj->$col : "";
                    }
                    $channels[$key] = $channel;
                }
                $sheet->fromArray($channels);

            });

        })->store('xls', storage_path('exports'));
    }

    public function initVoiceLocalCommands()
    {
        $commands = [
            ['word' => '关机', 'target' => []],
            ['word' => '返回桌面', 'target' => []],
            ['word' => '商店', 'target' => []],
            ['word' => '我的应用', 'target' => []]
        ];
        foreach ($commands as $command) {
            \App\Models\VoiceLocalCommand::updateOrCreate($command);
        }
    }
}
