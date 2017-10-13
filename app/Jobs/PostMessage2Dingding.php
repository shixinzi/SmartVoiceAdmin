<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PostMessage2Dingding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $message;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($message)
    {
        $this->message= $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $url = 'https://oapi.dingtalk.com/robot/send?access_token=239099b80a09bcef50d5ca08e77316273ccf33dcd7d4513206a9155a005fce42';
            $url = 'https://oapi.dingtalk.com/robot/send?access_token=7d2b8d033cc7a96f7559029e805221ccb42c88a9e50397f7d26e4a76ff3505b3';
            $url = 'https://oapi.dingtalk.com/robot/send?access_token=72c872ba2ad01ebd862c96a321dd8e7dedff9e7f3b3e14381dec49bc730c75c9';
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', $url, [
                'body' => json_encode($this->message),
                'headers' => [
                    'Content-Type' => 'application/json;charset=utf-8',
                ]
            ]);
            $htmlContent = $response->getBody()->getContents();
            $jsonContent = json_decode($htmlContent, true);
            if(!$jsonContent || $jsonContent['errcode'] != 0) {
                \Log::error($htmlContent);
            }

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            \Log::error($e->getMessage());
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
    }
}
