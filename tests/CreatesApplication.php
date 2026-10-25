<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Chave descartável por execução: nenhuma APP_KEY fica versionada.
        if (! $app['config']->get('app.key')) {
            $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        }

        return $app;
    }
}
