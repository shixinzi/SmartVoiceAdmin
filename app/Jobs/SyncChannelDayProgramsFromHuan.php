<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Log;
use App\Models\Program;
use App\Models\Wiki;
use App\Jobs\SyncWikiFromHuan;

class SyncChannelDayProgramsFromHuan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $channel = $this->data['channel'];
        $day = $this->data['day'];
        Log::info($channel->code . "\t" . $day);
        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => config('app.huan.api_url')
            ]);
            $body = [
                'action' => 'GetDayProgramsByChannel',
                'developer' => [
                    'apikey' => config('app.huan.api_appkey'),
                    'secretkey' => config('app.huan.api_secretkey'),
                ],
                'user' => [
                    "userid" => "huan123456",
                ],
                'device' => [
                    'dnum' => 'huan123456',
                ],
                'param' => [
                    'channel_code' => $channel->code_sd ? $channel->code_sd : $channel->code,
                    'day' => $day,
                ]
            ];

            $response = $client->request('POST', '/json2', ['body' => json_encode($body)]);
            $htmlContent = $response->getBody()->getContents();
            $jsonContent = \GuzzleHttp\json_decode($htmlContent, true);
            if ($jsonContent && isset($jsonContent['programs'])
                && is_array($jsonContent['programs']) && count($jsonContent['programs']) > 4
            ) {
                $this->deleteChannelDayProgram($day, $channel->code);
                foreach ($jsonContent['programs'] as $program) {
                    Log::info($program['name'] . "\t" . $program['start_time']);
                    $programObj = new Program();
                    $programObj->channel_code = $channel->code;
                    $programObj->date = $day;
                    $programObj->name = $program['name'];
                    $programObj->start_time = strtotime($program['start_time']);
                    $programObj->end_time = strtotime($program['end_time']);
                    if (isset($program['wiki_id'])) {
                        $programObj->wiki_id = $program['wiki_id'];
                        $wiki = Wiki::find($program['wiki_id']);
                        if (!$wiki) {
                            dispatch(new SyncWikiFromHuan([
                                'wiki_id' => $program['wiki_id'],
                            ]));
                        }
                    }
                    if (isset($program['tags'])) {
                        $programObj->tags = $program['tags'];
                    }
                    if (isset($program['episode'])) {
                        $programObj->episode = $program['episode'];
                    }
                    $programObj->save();
                }
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            \Log::error($e->getMessage());
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    protected function deleteChannelDayProgram($date, $channel_code)
    {
        return Program::where('date', $date)->where('channel_code', $channel_code)->delete();
    }
}
