<?php

namespace App\Console;

use App\Console\Commands\GforceImportStock;
use App\Console\Commands\HendeyNewCarStocks;
use App\Console\Commands\HendeyUsedCarStocks;
use App\Console\Commands\UpdateDerivativesFromCap;
use App\Console\Commands\UpdateGFVFromCAP;
use App\Console\Commands\UpdateImage;
use App\Console\Commands\UpdateThumbImage;
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
        UpdateGFVFromCAP::class,
        UpdateImage::class,
        UpdateThumbImage::class,
        GforceImportStock::class,
        HendeyNewCarStocks::class,
        HendeyUsedCarStocks::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
//        $schedule->command('update:image')
//                 ->weekly();

        //$schedule->command('GforceStockFeed:import {--supplier=} {--location=}')->daily();
        //$schedule->command('stock:hendy-new')->daily();
        //$schedule->command('stock:hendy-used')->daily();
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
