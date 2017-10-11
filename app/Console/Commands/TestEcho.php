<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestEcho extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:echo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $wikiIds = [];
    protected $index = 0;

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
        \Excel::create('channels', function($excel) {
            $excel->sheet('Sheetname', function($sheet) {
                $channelObjs = \App\Models\Channel::orderBy('sort_id', 'asc')->get();
                $channels = [];
                foreach($channelObjs as $key => $channelObj) {
                    $cols = ['tv_station_id', 'sort_id', 'name', 'code', 'memo', 'type', 'logo', 'lookcheck'];
                    $channel = [];
                    foreach($cols as $col) {
                        $channel[$col] = $channelObj->$col ? $channelObj->$col : "";
                    }
                    $channels[$key] = $channel;
                }
                $sheet->fromArray($channels);

            });

        })->store('xls', storage_path('exports'));
    }

    public function initVoiceLocalCommands()
    {
        $commands = [
            ['word' => '关机',  'target' => []],
            ['word' => '返回桌面',  'target' => []],
            ['word' => '商店', 'target' => []],
            ['word' => '我的应用', 'target' => []]
        ];
        foreach($commands as $command) {
            \App\Models\VoiceLocalCommand::updateOrCreate($command);
        }
    }
}
