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
        Commands\TransformerMakeCommand::class,
        Commands\RepositoryMakeCommand::class,
        Commands\TriggerPoint::class,
        Commands\Trails::class,
        Commands\Project::class


    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('Trails')->everyMinute();
        $schedule->command('Project')->everyMinute();
        $schedule->command("client:protected")->everyMinute();//直客保护
        $schedule->command("schedule:remind")->everyMinute();//日程提醒

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
