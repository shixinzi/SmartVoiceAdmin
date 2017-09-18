<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use App\Models\SearchDict;
use App\Models\QQAlbum;

class CronMakeSearchDict extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:MakeSearchDict';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $tags = [];

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
        QQAlbum::where('type', 'movie')->chunk(1000, function($albums){
            foreach($albums as $album) {
                $words = [];
                $words = $this->arrayUpdateOrPush($words, $album->album_name);
                $words = $this->arrayUpdateOrPush($words, $album->en_name);
                $words = $this->arrayUpdateOrPush($words, $album->alias_name);
                $words = $this->arrayUpdateOrPush($words, $album->cpr_companyname);
                $words = $this->arrayUpdateOrPush($words, $album->director);
                $words = $this->arrayUpdateOrPush($words, $album->actor);

                foreach($words as $word) {
                    $this->info($word);
                    $this->insertSearchDict($word);
                }

                $this->tags = $this->arrayUpdateOrPush($this->tags, $album->genre);
                $this->tags = $this->arrayUpdateOrPush($this->tags, $album->sub_genre);
                $this->tags = $this->arrayUpdateOrPush($this->tags, $album->sub_type);
                $this->tags = $this->arrayUpdateOrPush($this->tags, $album->area);
                $this->tags = $this->arrayUpdateOrPush($this->tags, $album->language);
                $this->tags = $this->arrayUpdateOrPush($this->tags, $album->tag);


            }
            if($this->tags) {
                foreach ($this->tags as $tag) {
                    $this->insertSearchDict($tag);
                }
            }
        });
    }

    public function insertSearchDict($word)
    {
        $word = trim($word);
        if(!$word) {
            return false;
        }
        $dictObj = SearchDict::where("word", $word)->first();
        if(!$dictObj) {
            Log::debug($word);
            $tfidf = $this->getTfidfFromXunsearch($word);
            SearchDict::create($tfidf);
        } else {
            Log::debug($word);
        }
    }

    public function getTfidfFromXunsearch($word)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'http://www.xunsearch.com/scws/demo/get_tfidf.php', ['form_params' => ['data' => $word]]);
            $htmlContent = $response->getBody()->getContents();
            if ($htmlContent) {
                $htmlContent = trim(str_replace(["\n","\r" , "{" , "}" ,":" , "/"], "", strip_tags($htmlContent)));
                preg_match('/TF=(?P<tf>[^\s]+) IDF=(?P<idf>[^\s]+)/', $htmlContent, $array);
                if ($array && isset($array['tf']) && isset($array['idf'])) {
                    return ['word' => $word, 'tf' => $array['tf'] , 'idf' => $array['idf']];
                }
            }
            return ['word' => $word];

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            \Log::error($e->getMessage());
            return ['word' => $word];
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return ['word' => $word];
        }
    }

    public function arrayUpdateOrPush($a, $b)
    {
        $bb = preg_split("/[\s,|;]+/", $b);
        foreach($bb as $c) {
            $c = trim($c);
            if($c && mb_strlen($c, 'UTF8') > 1 && !in_array($c, $a)) {
                array_push($a, $c);
            }
        }
        return $a;
    }
}
