<?php

namespace App\Http\Controllers;

use App\Models\Parcela;
use App\Services\CobrancaPixService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Log;
use Throwable;

class PagamentoController extends Controller
{
    public function show(string $token, CobrancaPixService $service)
    {
        $parcela = Parcela::with('emprestimo.cliente')->where('token_pagamento', $token)->firstOrFail();
        $agora = now();
        $cobranca = null;
        $falhou = false;

        try {
            $cobranca = $service->garantirQrCodeVigente($parcela, $agora);
        } catch (Throwable $e) {
            $falhou = true;
            Log::error('Falha ao gerar QR Code na página de pagamento', ['parcela_id' => $parcela->id, 'erro' => $e->getMessage()]);
        }

        // A conciliação pode ter dado baixa durante a chamada acima.
        $parcela->refresh();

        return response()
            ->view('pagamento.show', [
                'parcela' => $parcela,
                'primeiroNome' => $parcela->emprestimo->cliente->primeiroNome(),
                'valorCentavos' => $parcela->valor_centavos,
                'diasAtraso' => $parcela->diasAtraso($agora),
                'disponivelEm' => CobrancaPixService::disponivelEm($parcela),
                'cobranca' => $parcela->estaAberta() ? $cobranca : null,
                'qrCodeSvg' => $cobranca?->payload ? $this->qrCodeSvg($cobranca->payload) : null,
                'falhou' => $falhou,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, private');
    }

    private function qrCodeSvg(string $payload): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(260, 1), new SvgImageBackEnd));

        return $writer->writeString($payload);
    }
}
