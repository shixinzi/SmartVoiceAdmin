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

        if (preg_match('/^我(要|想)看(\S+)第(\S+)(集|期)/', "我要看楚乔传第1集", $matches)) {
            dd($matches);
        }
        $this->initVoiceLocalCommands();
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
