<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Storage;
use App\Models\App;
use Overtrue\Pinyin\Pinyin;

class CronUpdateApps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:UpdateApps';

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
        $pinyin = new Pinyin();
        $csvs = file_get_contents(storage_path('./app/apps.csv'));
        $lines = explode("\r", $csvs);
        foreach ($lines as $line) {
            $rows = explode(",", trim($line));
            if (isset($rows[0]) && isset($rows[1]) && $rows[0] && $rows[1]) {
                $this->info($rows[0] . "-" . $rows[1]);
                App::updateOrCreate(
                    ['package_name' => trim($rows[1])],
                    [
                        'name' => trim($rows[0]),
                        'version_name' => trim($rows[2]),
                        'abbr' => $pinyin->abbr(trim($rows[0])),
                    ]
                );
            }
        }
    }
}
