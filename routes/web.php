<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CobrancaController;
use App\Http\Controllers\EmprestimoController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\WebhookAsaasController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/cobrancas');

// Acesso do único usuário (sem cadastro público nem recuperação de senha)
Route::middleware('guest')->group(function () {
    Route::get('/entrar', [LoginController::class, 'create'])->name('entrar');
    Route::post('/entrar', [LoginController::class, 'store']);
});

// Painel: tudo aqui dentro exige sessão. Rotas novas do painel entram neste grupo.
Route::middleware('auth')->group(function () {
    Route::post('/sair', [LoginController::class, 'destroy'])->name('sair');

    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');

    Route::get('/emprestimos', [EmprestimoController::class, 'index'])->name('emprestimos.index');
    Route::get('/emprestimos/novo', [EmprestimoController::class, 'create'])->name('emprestimos.create');
    Route::post('/emprestimos', [EmprestimoController::class, 'store'])->name('emprestimos.store');
    Route::get('/emprestimos/{emprestimo}', [EmprestimoController::class, 'show'])->whereNumber('emprestimo')->name('emprestimos.show');

    Route::get('/cobrancas', [CobrancaController::class, 'index'])->name('cobrancas.index');
    Route::post('/parcelas/{parcela}/baixa', [CobrancaController::class, 'baixa'])->name('parcelas.baixa');
});

// Página pública de pagamento da parcela (token aleatório, nunca o id)
Route::get('/p/{token}', [PagamentoController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('pagamento.show');

// Webhook do Asaas (fora do CSRF, ver VerifyCsrfToken::$except)
Route::post('/webhooks/asaas', WebhookAsaasController::class)->name('webhooks.asaas');
