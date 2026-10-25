<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AsaasClient
{
    private function chavePix(): string
    {
        $chave = (string) config('services.asaas.pix_key');

        if ($chave === '') {
            throw new RuntimeException('ASAAS_PIX_KEY não configurada (chave Pix da conta que recebe).');
        }

        return $chave;
    }

    /**
     * Cria o QR Code estático com o valor final da parcela. Juros e multa ficam
     * só no sistema; o Asaas recebe apenas o valor já calculado.
     *
     * @return array{id: string, payload: string, encodedImage?: string}
     */
    public function criarQrCodeEstatico(int $valorCentavos, string $descricao, CarbonInterface $expiraEm, string $externalReference): array
    {
        return $this->post('/pix/qrCodes/static', [
            'addressKey' => $this->chavePix(),
            'value' => round($valorCentavos / 100, 2),
            'description' => $descricao,
            'expirationDate' => $expiraEm->format('Y-m-d H:i:s'),
            'allowsMultiplePayments' => false,
            'externalReference' => $externalReference,
        ])->json();
    }

    public function removerQrCodeEstatico(string $qrCodeId): bool
    {
        return (bool) $this->send('delete', "/pix/qrCodes/static/{$qrCodeId}")->json('deleted');
    }

    public function consultarCobranca(string $paymentId): array
    {
        return $this->get("/payments/{$paymentId}")->json();
    }

    private function get(string $path, array $query = []): Response
    {
        return $this->send('get', $path, $query);
    }

    private function post(string $path, array $dados): Response
    {
        return $this->send('post', $path, $dados);
    }

    private function send(string $metodo, string $path, array $dados = []): Response
    {
        $inicio = microtime(true);

        $response = $this->http()->{$metodo}($path, $dados);

        // Nunca logar headers: o access_token vai neles.
        $contexto = [
            'metodo' => strtoupper($metodo),
            'path' => $path,
            'status' => $response->status(),
            'ms' => (int) ((microtime(true) - $inicio) * 1000),
        ];

        if ($response->failed()) {
            $erros = collect($response->json('errors', []))->pluck('description')->implode('; ');
            Log::error('Asaas: requisição falhou', $contexto + ['erros' => $erros ?: $response->body()]);

            throw new RuntimeException("Asaas {$contexto['metodo']} {$path} falhou ({$response->status()}): {$erros}");
        }

        Log::info('Asaas: requisição ok', $contexto);

        return $response;
    }

    private function http(): PendingRequest
    {
        $url = rtrim((string) config('services.asaas.url'), '/');
        $chave = config('services.asaas.key');

        if (! app()->isProduction() && ! str_contains((string) parse_url($url, PHP_URL_HOST), 'sandbox')) {
            throw new RuntimeException('URL do Asaas sem "sandbox" com APP_ENV diferente de production. Use https://api-sandbox.asaas.com/v3.');
        }

        if (! $chave) {
            throw new RuntimeException('ASAAS_API_KEY não configurada.');
        }

        return Http::baseUrl($url)
            ->withHeaders(['access_token' => $chave, 'User-Agent' => config('app.name')])
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.asaas.timeout'))
            ->connectTimeout(5)
            // Só repete falha de conexão; erro de negócio (4xx) volta na hora.
            ->retry(3, 500, fn ($e) => $e instanceof ConnectionException, throw: false);
    }
}
