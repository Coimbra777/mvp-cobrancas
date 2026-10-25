<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PagamentoController;
use App\Http\Controllers\Site\SiteController;
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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/contato', [SiteController::class, 'contato'])->name('site.contato');

// Acesso do único usuário (sem cadastro público nem recuperação de senha)
Route::middleware('guest')->group(function () {
    Route::get('/entrar', [LoginController::class, 'create'])->name('entrar');
    Route::post('/entrar', [LoginController::class, 'store']);
});

Route::post('/sair', [LoginController::class, 'destroy'])->middleware('auth')->name('sair');

// Página pública de pagamento da parcela (token aleatório, nunca o id)
Route::get('/p/{token}', [PagamentoController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('pagamento.show');

// Webhook do Asaas (fora do CSRF, ver VerifyCsrfToken::$except)
Route::post('/webhooks/asaas', WebhookAsaasController::class)->name('webhooks.asaas');
