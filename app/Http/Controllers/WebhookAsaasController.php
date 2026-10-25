<?php

namespace App\Http\Controllers;

use App\Models\EventoWebhook;
use App\Services\WebhookAsaasService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookAsaasController extends Controller
{
    public function __invoke(Request $request, WebhookAsaasService $service)
    {
        $token = (string) config('services.asaas.webhook_token');

        if ($token === '' || ! hash_equals($token, (string) $request->header('asaas-access-token'))) {
            Log::warning('Webhook Asaas com token inválido', ['ip' => $request->ip()]);

            return response()->json(['erro' => 'nao autorizado'], 401);
        }

        $dados = $request->json()->all();

        if (empty($dados['event'])) {
            return response()->json(['erro' => 'payload invalido'], 400);
        }

        // Grava antes de processar. O id do evento (evt_...) é a chave de idempotência.
        try {
            $evento = EventoWebhook::firstOrCreate(
                ['chave' => $dados['id'] ?? 'sha1:'.sha1($request->getContent())],
                ['evento' => $dados['event'], 'payload' => $dados],
            );
        } catch (UniqueConstraintViolationException) {
            return response()->json(['duplicado' => true]);
        }

        if ($evento->processado_em) {
            return response()->json(['duplicado' => true]);
        }

        try {
            $service->processar($evento);
        } catch (Throwable $e) {
            // O evento fica salvo como não processado; o Asaas reenvia e o reenvio o processa.
            Log::error('Erro ao processar webhook Asaas', ['evento_id' => $evento->id, 'erro' => $e->getMessage()]);

            return response()->json(['erro' => 'falha ao processar'], 500);
        }

        return response()->json(['recebido' => true]);
    }
}
