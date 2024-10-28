<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Đăng ký các command của ứng dụng.
     *
     * @var array
     */
    protected $commands = [
        // Đăng ký command của bạn ở đây
        \App\Console\Commands\CheckFlashSale::class,
    ];

    /**
     * Xác định lịch trình các command.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('flashsale:check')->everyMinute();
    }

    /**
     * Đăng ký các command cho ứng dụng.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
