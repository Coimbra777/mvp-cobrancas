<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    /**
     * Única linha de cron necessária no servidor:
     * * * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
     */
    protected function schedule(Schedule $schedule): void
    {
        // Duas vezes ao dia: se a primeira falhar, a segunda (e os dias seguintes) recuperam.
        $schedule->command('cobrancas:gerar')
            ->twiceDaily(7, 13)
            ->timezone('America/Fortaleza')
            ->withoutOverlapping();

        $schedule->command('webhook:reprocessar')->hourly()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
