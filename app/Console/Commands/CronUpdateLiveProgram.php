<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Wiki;
use App\Models\Channel;
use App\Models\Program;
use App\Models\LiveProgram;

class CronUpdateLiveProgram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:UpdateLiveProgram';

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
        $now = time() + 60;
        $channelObjs  = Channel::where("code", 'exists', true)->get();
        if($channelObjs) {
            foreach ($channelObjs as $channelObj) {
                $liveProgramObj = LiveProgram::where('channel_code', $channelObj->code)->first();
                if(!$liveProgramObj) {
                    $liveProgramObj = new LiveProgram();
                    $curProgramObj = Program::where('channel_code', $channelObj->code)
                        ->where('start_time', '<', $now)
                        ->where('end_time' ,'>', $now)->first();
                    if($curProgramObj) {
                        $liveProgramObj->channel_code = $channelObj->code;
                        $liveProgramObj->program_name = $curProgramObj->name;
                        $liveProgramObj->start_time = $curProgramObj->start_time;
                        $liveProgramObj->end_time = $curProgramObj->end_time;
                        if($curProgramObj->wiki_id) {
                            $wikiObj = Wiki::getOneById($curProgramObj->wiki_id);
                        } else {
                            $wikiObj = null;
                        }
                        if($wikiObj) {
                            $liveProgramObj->wiki_id = $curProgramObj->wiki_id;
                            $liveProgramObj->wiki_title = $wikiObj->title;
                            $liveProgramObj->wiki_cover = $wikiObj->cover;
                        } else {
                            $liveProgramObj->wiki_id = null;
                            $liveProgramObj->wiki_title = null;
                            $liveProgramObj->wiki_cover = null;
                        }
                        $liveProgramObj->tags = $curProgramObj->tags;
                        $liveProgramObj->hot = $channelObj->hot;
                        $liveProgramObj->save();
                        $this->info($channelObj->code."\t11111");
                    } else {
                        $liveProgramObj->program_name = '未知节目';
                        $liveProgramObj->start_time = $now;
                        $liveProgramObj->end_time = $now;
                        $liveProgramObj->wiki_id = null;
                        $liveProgramObj->wiki_title = null;
                        $liveProgramObj->wiki_cover = null;
                        $liveProgramObj->tags = null;
                        $liveProgramObj->save();
                        $this->info($channelObj->code."\t2222");
                    }
                } else if ($liveProgramObj->end_time < $now){
                    $curProgramObj = Program::where('channel_code', $channelObj->code)
                        ->where('start_time', '<', $now)
                        ->where('end_time' ,'>', $now)->first();
                    if($curProgramObj) {
                        $liveProgramObj->channel_code = $channelObj->code;
                        $liveProgramObj->program_name = $curProgramObj->name;
                        $liveProgramObj->start_time = $curProgramObj->start_time;
                        $liveProgramObj->end_time = $curProgramObj->end_time;
                        if($curProgramObj->wiki_id) {
                            $wikiObj = Wiki::getOneById($curProgramObj->wiki_id);
                        } else {
                            $wikiObj = null;
                        }
                        if($wikiObj) {
                            $liveProgramObj->wiki_id = $curProgramObj->wiki_id;
                            $liveProgramObj->wiki_title = $wikiObj->title;
                            $liveProgramObj->wiki_cover = $wikiObj->cover;
                        } else {
                            $liveProgramObj->wiki_id = null;
                            $liveProgramObj->wiki_title = null;
                            $liveProgramObj->wiki_cover = null;
                        }

                        $liveProgramObj->tags = $curProgramObj->tags;
                        $liveProgramObj->hot = $channelObj->hot;
                        $liveProgramObj->save();
                        $this->info($channelObj->code."\t33333");
                    } else {
                        $liveProgramObj->program_name = '未知节目';
                        $liveProgramObj->start_time = $now;
                        $liveProgramObj->end_time = $now;
                        $liveProgramObj->wiki_id = null;
                        $liveProgramObj->wiki_title = null;
                        $liveProgramObj->wiki_cover = null;
                        $liveProgramObj->tags = null;
                        $liveProgramObj->save();
                        $this->info($channelObj->code."\t4444");
                    }
                }
            }
        }
    }
}
