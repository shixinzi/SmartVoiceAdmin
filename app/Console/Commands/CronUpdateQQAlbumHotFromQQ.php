<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use voku\helper\HtmlDomParser;
use App\Models\QQAlbumHotLog;
use App\Models\QQAlbum;

class CronUpdateQQAlbumHotFromQQ extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:UpdateQQAlbumHotFromQQ';

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
        $urls = [
            'http://v.qq.com/x/list/movie',
            'http://v.qq.com/x/list/movie?&offset=30',
            'http://v.qq.com/x/list/movie?&offset=60',
            'http://v.qq.com/x/list/tv',
            'http://v.qq.com/x/list/tv?&offset=30',
            'http://v.qq.com/x/list/tv?&offset=60',
            'http://v.qq.com/x/list/variety',
            'http://v.qq.com/x/list/variety?&offset=30',
            'http://v.qq.com/x/list/variety?&offset=60',
        ];

        $date = date("Y-m-d");

        foreach($urls as $url) {
            $htmlContent = $this->getQQHtmlContent($url);
            $htmlDom = HtmlDomParser::str_get_html($htmlContent);
            $divDoms = $htmlDom->find('.figures_list .list_item');
            foreach($divDoms as $divDom) {
                $album_name = trim($divDom->find('strong a', 0)->plaintext);
                $album_cover = trim($divDom->find('strong a', 0)->href);
                $hot_num = $this->strToNum($divDom->find('.figure_count span', 0)->plaintext);
                $album_id = str_replace(['https://v.qq.com/x/cover/', '.html'], '', $album_cover);

                QQAlbumHotLog::firstOrCreate(
                    ['album_id' => $album_id , 'date' => $date],
                    ['album_name' => $album_name, 'hot_num' => $hot_num]
                );

                QQAlbum::where("album_id", $album_id)->update(['hot_num' => $hot_num]);

            }

        }
    }

    protected function getQQHtmlContent($url)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);

        $htmlContent = $response->getBody()->getContents();
        return $htmlContent;
    }

    protected function strToNum($str)
    {
        $num = 0;
        $str = trim($str);
        preg_match('/(?P<yi>[^\D]+)亿/', $str, $array);
        if(isset($array['yi'])) {
            $num = $num + intval($array['yi'])*100000000;
        }
        preg_match('/(?P<wan>[^\D]+)万/', $str, $array);
        if(isset($array['wan'])) {
            $num = $num + intval($array['wan'])*10000;
        }
        preg_match('/(?P<ge>[\d]+)$/', $str, $array);
        if(isset($array['ge'])) {
            $num = $num + intval($array['ge']);
        }
        return $num;
    }
}
