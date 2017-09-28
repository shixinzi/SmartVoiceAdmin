<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\QQAlbum;

class CronMakeXunsearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:MakeXunsearch';

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
        //$this->testSearch();
        $this->makeAlbum();
    }

    public function testSearch()
    {
        $xs = new \XS(config_path('./album.ini'));
        $docs = $xs->search->setSort('score')->setLimit(10)->search('青云志');
        $count = $xs->search->lastCount;
        foreach($docs as $doc) {
            $this->info($count ."|". $doc->albumName ."(". trim($doc->tags) .")". $doc->percent());
        }
        exit;
    }

    public function makeAlbum()
    {
        $xs = new \XS(config_path('./album.ini'));
        $xs->index->clean();
        QQAlbum::chunk(2000, function($albums) use($xs) {
            foreach($albums as $i => $album) {
                $this->debug($i ."\t". $album->album_name);
                $doc = new \XSDocument;
                $data = [
                    'album_id' => $album->album_id,
                    'albumName' => $album->album_name,
                    'aliasName' => $this->mergeNames($album),
                    'albumVerpic' => $album->album_verpic,
                    'albumHorpic' => $album->album_horpic,
                    'tags' => $this->mergeTags($album),
                    'area' => $album->area,
                    'director' => $album->director,
                    'actor' => $album->actor,
                    'guests' => $album->guests,
                    'score' => $album->score,
                ];
                $doc->setFields($data);
                $xs->index->add($doc);
            }
        });
    }

    protected function mergeNames($album)
    {
        $names = array_merge(explode(";", $album->en_name), explode(";", $album->alias_name));
        $names = array_unique($names);
        return implode(";", $names);
    }

    protected function mergeTags($album)
    {
        $types = [
            'movie' => '电影',
            'tv' => '电视剧',
            'doc' => '记录片',
            'cartoon' => '卡通',
            'variety' => '综艺'
        ];
        $tags = [$types[$album->type]];
        $tags = array_merge($tags, explode(";", $album->genre));
        $tags = array_merge($tags, explode(";", $album->sub_genre));
        $tags = array_merge($tags, explode(";", $album->sub_type));
        $tags = array_merge($tags, explode(";", $album->tag));
        $tags = array_unique($tags);
        return implode(";", $tags);
    }
}
