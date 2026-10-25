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
        // Uma vez por dia: a janela de 5 dias recupera, no dia seguinte, o que falhar hoje.
        $schedule->command('cobrancas:gerar')
            ->dailyAt('07:00')
            ->timezone('America/Fortaleza')
            ->withoutOverlapping();
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
