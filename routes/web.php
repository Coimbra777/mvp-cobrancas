<?php

use App\Http\Controllers\Auth\LoginController;
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

Route::redirect('/', '/painel');

// Acesso do único usuário (sem cadastro público nem recuperação de senha)
Route::middleware('guest')->group(function () {
    Route::get('/entrar', [LoginController::class, 'create'])->name('entrar');
    Route::post('/entrar', [LoginController::class, 'store']);
});

// Painel: tudo aqui dentro exige sessão. Rotas novas do painel entram neste grupo.
Route::middleware('auth')->group(function () {
    Route::post('/sair', [LoginController::class, 'destroy'])->name('sair');

    // Áreas ainda provisórias; cada change troca a sua pela página real.
    Route::inertia('/painel', 'EmConstrucao', ['titulo' => 'Painel'])->name('painel');
    Route::inertia('/clientes', 'EmConstrucao', ['titulo' => 'Clientes'])->name('clientes');
    Route::inertia('/emprestimos', 'EmConstrucao', ['titulo' => 'Empréstimos'])->name('emprestimos');
    Route::inertia('/parcelas', 'EmConstrucao', ['titulo' => 'Parcelas'])->name('parcelas');
    Route::inertia('/lembretes', 'EmConstrucao', ['titulo' => 'Lembretes'])->name('lembretes');
    Route::inertia('/configuracoes', 'EmConstrucao', ['titulo' => 'Configurações'])->name('configuracoes');
});

// Página pública de pagamento da parcela (token aleatório, nunca o id)
Route::get('/p/{token}', [PagamentoController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('pagamento.show');

// Webhook do Asaas (fora do CSRF, ver VerifyCsrfToken::$except)
Route::post('/webhooks/asaas', WebhookAsaasController::class)->name('webhooks.asaas');
