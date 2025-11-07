<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Scan setiap jam
        // $schedule->command('websites:scan-all-scheduled')
        //     ->hourly()
        //     ->withoutOverlapping();
        
        // Scan setiap hari jam 2 pagi
        $schedule->command('websites:scan-all-scheduled')
            ->dailyAt('02:00')
            ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }
}
