@php
    $reais = fn (int $centavos) => 'R$ '.number_format($centavos / 100, 2, ',', '.');
    $paga = $parcela->status === \App\Enums\ParcelaStatus::Paga;
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $parcela->descricao() }}</title>
    <style>
        :root { --bg: #f4f5f7; --card: #fff; --texto: #1d2330; --suave: #5b6475; --borda: #e2e5ea; --ok: #157347; --alerta: #b45309; --botao: #1d4ed8; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #11141a; --card: #1b2029; --texto: #e8eaee; --suave: #a1a9b8; --borda: #2c3340; --ok: #4ade80; --alerta: #fbbf24; --botao: #3b82f6; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--texto); font: 16px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        main { max-width: 420px; margin: 0 auto; padding: 24px 16px; }
        .card { background: var(--card); border: 1px solid var(--borda); border-radius: 12px; padding: 20px; }
        h1 { font-size: 1.1rem; margin: 0 0 4px; }
        .suave { color: var(--suave); font-size: .9rem; }
        .valor { font-size: 2rem; font-weight: 700; margin: 12px 0 4px; }
        dl { display: grid; grid-template-columns: auto 1fr; gap: 6px 12px; margin: 16px 0; }
        dt { color: var(--suave); } dd { margin: 0; text-align: right; }
        .situacao { display: inline-block; padding: 2px 10px; border-radius: 999px; font-weight: 600; font-size: .9rem; border: 1px solid currentColor; }
        .ok { color: var(--ok); } .alerta { color: var(--alerta); }
        .qrcode { display: flex; justify-content: center; margin: 16px 0; }
        .qrcode svg { width: 100%; max-width: 260px; height: auto; background: #fff; padding: 8px; border-radius: 8px; }
        textarea { width: 100%; font: 12px/1.4 ui-monospace, monospace; padding: 8px; border: 1px solid var(--borda); border-radius: 8px; background: var(--bg); color: var(--texto); resize: none; }
        button { width: 100%; margin-top: 10px; padding: 14px; font-size: 1rem; font-weight: 600; color: #fff; background: var(--botao); border: 0; border-radius: 8px; cursor: pointer; }
        .aviso { margin-top: 16px; padding: 12px; border-radius: 8px; background: var(--bg); }
    </style>
</head>
<body>
<main>
    <div class="card">
        <h1>Olá, {{ $primeiroNome }}</h1>
        <div class="suave">{{ $parcela->descricao() }}</div>

        <div class="valor">{{ $reais($valorCentavos) }}</div>
        @if ($paga)
            <span class="situacao ok">Paga</span>
        @elseif ($diasAtraso > 0)
            <span class="situacao alerta">Em atraso há {{ $diasAtraso }} {{ $diasAtraso === 1 ? 'dia' : 'dias' }}</span>
        @else
            <span class="situacao">Em aberto</span>
        @endif

        <dl>
            <dt>Vencimento</dt><dd>{{ $parcela->vencimento->format('d/m/Y') }}</dd>
            @if ($paga && $parcela->pago_em)
                <dt>Pago em</dt><dd>{{ $parcela->pago_em->format('d/m/Y') }}</dd>
            @endif
        </dl>

        @if ($cobranca && $qrCodeSvg)
            <div class="qrcode" aria-label="QR Code Pix">{!! $qrCodeSvg !!}</div>
            <label class="suave" for="pix">Pix copia e cola</label>
            <textarea id="pix" rows="4" readonly>{{ $cobranca->payload }}</textarea>
            <button type="button" id="copiar">Copiar código Pix</button>
            <p class="suave">
                Válido até {{ $cobranca->expira_em->format('d/m/Y') }} às {{ $cobranca->expira_em->format('H:i') }}.
                @if ($diasAtraso > 0) Depois disso, abra este link de novo para gerar um novo código. @endif
            </p>
        @elseif (! $paga)
            <div class="aviso">
                @if ($falhou)
                    Não conseguimos gerar o Pix agora. Tente de novo em alguns minutos.
                @else
                    O Pix desta parcela fica disponível a partir de {{ $disponivelEm->format('d/m/Y') }}.
                @endif
            </div>
        @endif
    </div>
</main>
@if ($cobranca && $qrCodeSvg)
<script>
    document.getElementById('copiar').addEventListener('click', function () {
        var campo = document.getElementById('pix'), botao = this;
        var pronto = function () { botao.textContent = 'Código copiado!'; setTimeout(function () { botao.textContent = 'Copiar código Pix'; }, 2500); };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(campo.value).then(pronto);
        } else {
            campo.select(); document.execCommand('copy'); pronto();
        }
    });
</script>
@endif
</body>
</html>
