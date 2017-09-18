<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Log;
use Cache;
use App\Models\ChannelHotLog;
use App\Models\Channel;

class CronUpdateChannelHotFromHuan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:UpdateChannelHotFromHuan';

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
        $url = "http://bigdata.huan.tv/attention_rank";
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);

        $htmlContent = $response->getBody()->getContents();
        $jsonContent = \GuzzleHttp\json_decode($htmlContent, true);
        $hotLog = [];
        if ($jsonContent && isset($jsonContent['channels']) && isset($jsonContent['timestamp'])) {
            $timestamp = strtotime($jsonContent['timestamp']);
            if((time() - $timestamp) > 300) {
                Log::error('attention_rank timestamp isnot update!');
                exit;
            } else {
                $hotLog['timestamp'] = $timestamp;
                foreach ($jsonContent['channels'] as $hot) {
                    $this->info($hot['channel_code'] . "\t" . $hot['attention']);
                    $hotLog['attentions'][$hot['channel_code']] = $hot['attention'];
                }
            }
        } else {
            Log::error('attention_rank content isnot json!');
        }
        ChannelHotLog::insert($hotLog);
        Channel::chunk(20, function($channels) use ($hotLog) {
            foreach($channels as $channel) {
                if($channel->code && array_key_exists($channel->code, $hotLog['attentions'])) {
                    $channel->hot = intval($hotLog['attentions'][$channel->code]*10000);
                    $channel->save();
                } else {
                    $channel->hot = 0;
                    $channel->save();
                }
            }
        });
        Log::info('task '.$this->signature.' is finished!');
    }
}
