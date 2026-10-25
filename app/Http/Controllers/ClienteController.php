<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClienteController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('clientes/Index', [
            'clientes' => Cliente::orderBy('nome')->get(['id', 'nome', 'telefone']),
        ]);
    }

    public function store(ClienteRequest $request): RedirectResponse
    {
        Cliente::create($request->validated());

        return back()->with('sucesso', 'Cliente cadastrado.');
    }
}
