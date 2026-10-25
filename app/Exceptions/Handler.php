<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'senha',
        'senha_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->respondUsing(function (Response $response, Throwable $e, Request $request) {
            return $this->deveMostrarPaginaDeErro($response, $request)
                ? Inertia::render('Erro', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode())
                : $response;
        });
    }

    /**
     * Página de erro do painel (Inertia) só onde faz sentido: não no webhook (JSON),
     * não na página pública /p/{token} (independe dos assets do painel) e, para
     * 500/503, só fora do modo debug, para manter o diagnóstico em ambiente local.
     */
    private function deveMostrarPaginaDeErro(Response $response, Request $request): bool
    {
        $status = $response->getStatusCode();

        if (! in_array($status, [404, 419, 500, 503], true)) {
            return false;
        }

        if ($request->is('webhooks/*', 'p/*') || ($request->expectsJson() && ! $request->header('X-Inertia'))) {
            return false;
        }

        return in_array($status, [404, 419], true) || ! config('app.debug');
    }
}
