<?php

namespace App\Http\Controllers;

use App\Enums\ParcelaStatus;
use App\Models\Parcela;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CobrancaController extends Controller
{
    private const FILTROS = ['atrasadas', 'a-vencer'];

    public function index(Request $request): Response
    {
        $filtro = in_array($request->query('filtro'), self::FILTROS, true) ? $request->query('filtro') : 'atrasadas';
        $hoje = today();

        $abertas = Parcela::where('status', ParcelaStatus::Aberta);

        $parcelas = (clone $abertas)
            ->when($filtro === 'atrasadas', fn ($q) => $q->where('vencimento', '<', $hoje))
            ->when($filtro === 'a-vencer', fn ($q) => $q->where('vencimento', '>=', $hoje))
            ->with('emprestimo.cliente')
            ->orderBy('vencimento')
            ->orderBy('id')
            ->get()
            ->map(fn (Parcela $p) => [
                'id' => $p->id,
                'cliente' => $p->emprestimo->cliente->nome,
                'descricao' => $p->descricao(),
                'emprestimo_id' => $p->emprestimo_id,
                'vencimento' => $p->vencimento->toDateString(),
                'valor_centavos' => $p->valor_centavos,
                'situacao' => $p->situacao(),
                'link_whatsapp' => $p->linkWhatsApp(),
            ]);

        return Inertia::render('cobrancas/Index', [
            'filtro' => $filtro,
            'parcelas' => $parcelas,
            'contagem' => [
                'atrasadas' => (clone $abertas)->where('vencimento', '<', $hoje)->count(),
                'a-vencer' => (clone $abertas)->where('vencimento', '>=', $hoje)->count(),
            ],
        ]);
    }

    public function baixa(Parcela $parcela): RedirectResponse
    {
        if (! $parcela->darBaixa()) {
            return back()->with('erro', 'Esta parcela já está paga.');
        }

        return back()->with('sucesso', 'Baixa registrada.');
    }
}
