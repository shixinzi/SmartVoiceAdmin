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
        $this->info('Hello word!');

        $contents = file_get_contents('/Users/superwen/Desktop/channelMatchDefine.csv');
        if($contents && $lines = explode("\n", $contents)) {
            foreach($lines as $line) {
                $this->info($line);
                $cols = explode(",", $line);
                if($cols && isset($cols[2])) {
                    \App\Models\ChannelMatchDefine::firstOrCreate(
                        ['channel_name' => $cols[1]],
                        ['channel_code' => $cols[2]]
                    );
                }
            }
        }
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
