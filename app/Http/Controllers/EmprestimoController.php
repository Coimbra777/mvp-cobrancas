<?php

namespace App\Http\Controllers;

use App\Enums\MetodoCalculo;
use App\Http\Requests\EmprestimoRequest;
use App\Models\Cliente;
use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Services\CalculoParcelas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EmprestimoController extends Controller
{
    public function index(): Response
    {
        $emprestimos = Emprestimo::with('cliente:id,nome')
            ->withCount(['parcelas', 'parcelas as parcelas_pagas_count' => fn ($q) => $q->where('status', 'paga')])
            ->latest('id')
            ->get()
            ->map(fn (Emprestimo $e) => [
                'id' => $e->id,
                'cliente' => $e->cliente->nome,
                'valor_centavos' => $e->valor_centavos,
                'total_centavos' => $e->total_centavos,
                'parcelas' => $e->parcelas_count,
                'parcelas_pagas' => $e->parcelas_pagas_count,
            ]);

        return Inertia::render('emprestimos/Index', ['emprestimos' => $emprestimos]);
    }

    public function create(): Response
    {
        return Inertia::render('emprestimos/Novo', [
            'clientes' => Cliente::orderBy('nome')->get(['id', 'nome']),
            'hoje' => today()->toDateString(),
        ]);
    }

    public function store(EmprestimoRequest $request, CalculoParcelas $calculo): RedirectResponse
    {
        $dados = $request->validated();
        $metodo = MetodoCalculo::from($dados['metodo']);
        $resultado = $calculo->calcular(
            $request->valorCentavos(),
            (float) $dados['taxa_mensal'],
            $metodo,
            (int) $dados['quantidade_parcelas'],
            Carbon::parse($dados['primeiro_vencimento']),
        );

        $emprestimo = DB::transaction(function () use ($dados, $request, $metodo, $resultado) {
            $emprestimo = Emprestimo::create([
                'cliente_id' => $dados['cliente_id'],
                'valor_centavos' => $request->valorCentavos(),
                'taxa_mensal' => $dados['taxa_mensal'],
                'metodo' => $metodo,
                'quantidade_parcelas' => $dados['quantidade_parcelas'],
                'primeiro_vencimento' => $dados['primeiro_vencimento'],
                'total_centavos' => $resultado['total'],
            ]);
            $emprestimo->parcelas()->createMany($resultado['parcelas']);

            return $emprestimo;
        });

        return redirect()->route('emprestimos.show', $emprestimo)->with('sucesso', 'Empréstimo cadastrado.');
    }

    public function show(Emprestimo $emprestimo): Response
    {
        $emprestimo->load(['cliente:id,nome', 'parcelas']);

        return Inertia::render('emprestimos/Mostrar', [
            'emprestimo' => [
                'id' => $emprestimo->id,
                'cliente' => $emprestimo->cliente->nome,
                'valor_centavos' => $emprestimo->valor_centavos,
                'taxa_mensal' => (float) $emprestimo->taxa_mensal,
                'metodo' => $emprestimo->metodo->rotulo(),
                'quantidade_parcelas' => $emprestimo->quantidade_parcelas,
                'total_centavos' => $emprestimo->total_centavos,
            ],
            'parcelas' => $emprestimo->parcelas->map(fn (Parcela $p) => [
                'id' => $p->id,
                'numero' => $p->numero,
                'vencimento' => $p->vencimento->toDateString(),
                'valor_centavos' => $p->valor_centavos,
                'situacao' => $p->situacao(),
                'pago_em' => $p->pago_em?->toDateString(),
            ]),
        ]);
    }
}
