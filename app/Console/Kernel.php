<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\TestEcho::class,
        Commands\CronUpdateHdpChannels::class,
        Commands\CronUpdateAllQQAlbum::class,
        Commands\CronMakeSearchDict::class,
        Commands\CronUpdateApps::class,
        Commands\CronSyncChannelFromHuan::class,
        Commands\CronSyncProgramFromHuan::class,
        Commands\CronUpdateChannelHotFromHuan::class,
        Commands\CronUpdateLiveProgram::class,
        Commands\CronUpdateWikiExtendInfo::class,
        Commands\CronMatchWikiAndQQAlbum::class,
        Commands\CronUpdateQQAlbumHotFromQQ::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //更新wiki的昨天热度数据,每天5点执行
        $schedule->command(Commands\CronUpdateWikiExtendInfo::class)->dailyAt('5:00');
        //从欢网数据中心同步7天节目单,每周日早上3点执行
        $schedule->command(Commands\CronSyncProgramFromHuan::class,['--dayNums=0,+1,+2,+3,+4,+5,+6,+7'])->dailyAt('3:00')->sundays();
        //从欢网数据中心同步当天和昨天节目单任务,每天6,10点执行
        $schedule->command(Commands\CronSyncProgramFromHuan::class,['--dayNums=-1,0,+1'])->twiceDaily(6, 10);
        //从欢网数据中心同步当天节目单任务,每天13,15,18,23点执行
        $schedule->command(Commands\CronSyncProgramFromHuan::class,['--dayNums=0'])->twiceDaily(13, 15);
        $schedule->command(Commands\CronSyncProgramFromHuan::class,['--dayNums=0'])->twiceDaily(18, 23);
        //从欢网数据中心同步频道热度,每五分钟执行一次
        $schedule->command(Commands\CronUpdateChannelHotFromHuan::class)->everyFiveMinutes();
        //更新正在播出的节目,每分钟执行一次
        $schedule->command(Commands\CronUpdateLiveProgram::class)->everyMinute();
        //维基热度,可回看,可预约维基计算,每天8点执行一次
        $schedule->command(Commands\CronUpdateWikiExtendInfo::class)->dailyAt('8:00');
        //从腾讯视频获取vod的热度数据
        $schedule->command(Commands\CronUpdateQQAlbumHotFromQQ::class)->twiceDaily(7, 11);
        //从HDP更新频道列表,每天一次
        $schedule->command(Commands\CronUpdateHdpChannels::class)->dailyAt('8:20');
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
