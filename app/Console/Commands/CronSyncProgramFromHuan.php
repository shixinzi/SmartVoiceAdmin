<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\Program;
use App\Jobs\SyncChannelDayProgramsFromHuan;
use App\Common\Tools;

class CronSyncProgramFromHuan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:SyncProgramFromHuan {--dayNums=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cron:SyncProgramFromHuan  --dayNums=-1,0,+1';

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
        $days = Tools::getDatesByNums($this->option('dayNums'));
        if(!$days) {
            $this->error('need a option dayNums!');
            exit;
        }
        $channelObjs = Channel::orderBy('sort')->get();
        foreach($channelObjs as $channelObj) {
            foreach($days as $day) {
                $this->info("dispatch: ".$channelObj->name."(".$day.")");
                dispatch(new SyncChannelDayProgramsFromHuan([
                    'channel' => $channelObj,
                    'day' => $day,
                ]));
            }
        }
        $this->info('task dispatch finished!');
        exit;
    }
}
