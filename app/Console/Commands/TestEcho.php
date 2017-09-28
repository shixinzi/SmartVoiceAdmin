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
        $ids = [];
        $txts = file_get_contents('./wids3.txt');
        $lines = explode("\n", $txts);
        foreach($lines as $line) {
            $line = trim($line);
            $wiki = \App\Models\Wiki::find($line);
            if($wiki) {
                $this->info($wiki->title);
                file_put_contents("./wikis/" . $wiki->_id . ".json", $wiki->toJson());
            }
        }
        exit;

        $this->info('Hello word!');
        \App\Models\Program::distinct('wiki_id')->chunk(10000, function($wikis) {
            $ids = [];
            foreach($wikis as $wiki) {
                if($wiki->wiki_id) {
                    array_push($ids, $wiki->wiki_id);
                }
            }
            $this->info($this->index++);
            file_put_contents("./wids.txt", implode("\n", $ids), FILE_APPEND);
        });
        exit;
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
