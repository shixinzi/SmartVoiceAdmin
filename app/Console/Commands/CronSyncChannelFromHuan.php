<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;

class CronSyncChannelFromHuan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:SyncChannelFromHuan';

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
        $client = new \GuzzleHttp\Client([
            'base_uri' => config('app.huan.api_url'),
            'timeout' => 2.0,
        ]);
        $response = $client->request('POST', '/json2', [
            'body' => '{"action":"GetServicesBySP","developer":{"apikey":"NLS2AW29","secretkey":"ab72e987020d824940a1294f49a0c763"},"user":{"userid":"123"},"device":{"dnum":"123"},"param":{"sp_code":"public","showlive":"false"}}'
        ]);

        $htmlContent = $response->getBody()->getContents();
        $jsonContent = \GuzzleHttp\json_decode($htmlContent, true);
        if ($jsonContent && isset($jsonContent['services']) && $jsonContent['services']) {
            foreach ($jsonContent['services'] as $channel) {
                $this->info($channel['name'] . ' === ' . $channel['code']);
                $channelObj = Channel::where('name', '=', $channel['name'])->first();
                if (!$channelObj) {
                    $channelObj = new Channel();
                    $channelObj->name = $channel['name'];
                    $channelObj->code = $channel['code'];
                    $channelObj->logo = $channel['logo'];
                    //$channelObj->code_sd = $channel['code_sd'];
                    $channelObj->tags = is_array($channel['tags']) ? $channel['tags'] : null;
                    //$channelObj->memos = explode(',', $channel['memo']);
                    $channelObj->save();
                } else {

                }
            };
        }
    }

    public function insertChannel($channel)
    {
        $channelObj = Channel::where('name', '=', $channel['name'])->first();
        if (!$channelObj) {
            $this->info('No find ' . $channel['name']);
            $channelObj = new Channel();
            $channelObj->name = $channel['name'];
            $channelObj->code = $channel['code'];
            $channelObj->logo = $channel['logo'];
            $channelObj->code_sd = $channel['code_sd'];
            $channelObj->tags = is_array($channel['type']) ? $channel['type'] : [$channel['type']];
            $channelObj->memos = explode(',', $channel['memo']);
            $channelObj->save();
        } else {

        }
    }

}
