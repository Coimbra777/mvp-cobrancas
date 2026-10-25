<?php

namespace App\Console\Commands;

use App\Models\EventoWebhook;
use App\Services\WebhookAsaasService;
use Illuminate\Console\Command;
use Throwable;

class ReprocessarWebhooks extends Command
{
    protected $signature = 'webhook:reprocessar';

    protected $description = 'Processa de novo os eventos do Asaas que foram gravados mas falharam no processamento';

    public function handle(WebhookAsaasService $service): int
    {
        $pendentes = EventoWebhook::whereNull('processado_em')->orderBy('id')->get();

        foreach ($pendentes as $evento) {
            try {
                $service->processar($evento);
                $this->info("Evento {$evento->id}: {$evento->resultado}");
            } catch (Throwable $e) {
                $evento->update(['erro' => $e->getMessage()]);
                $this->error("Evento {$evento->id}: {$e->getMessage()}");
            }
        }

        $this->info("Pendentes: {$pendentes->count()}");

        return self::SUCCESS;
    }
}
