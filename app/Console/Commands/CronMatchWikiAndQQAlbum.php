<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Wiki;
use App\Models\QQAlbum;

class CronMatchWikiAndQQAlbum extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:MatchWikiAndQQAlbum';

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
        QQAlbum::whereIn('type', ['movie', 'tv', 'variety'])->whereNull('wiki_id')->chunk(100, function($albums) {
            foreach($albums as $album) {
                $models = ['movie' => 'film' , 'tv' => 'teleplay', 'cartoon' => 'teleplay', 'variety' => 'television', 'doc' => 'television'];
                if(isset($models[$album->type])) {
                    $wikis = Wiki::where('model' , $models[$album->type])
                        ->where('title', $album->album_name)->get();
                    if($wikis && count($wikis) == 1) {
                        $album->wiki_id = $wikis[0]->_id;
                        $album->save();

                        $this->info($album->album_name . "\t" . $album->type);
                    } else  {

                        $this->error($album->album_name . "\t" . $album->type);
                    }
                }
            }
        });
    }
}
