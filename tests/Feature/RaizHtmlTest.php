<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Tests\TestCase;

class RaizHtmlTest extends TestCase
{
    public function test_script_de_tema_vem_no_head_antes_dos_assets(): void
    {
        config(['inertia.testing.ensure_pages_exist' => false]);
        Route::middleware('web')->get('/_teste-raiz', fn () => Inertia::render('Teste'));

        $html = $this->get('/_teste-raiz')->assertOk()->getContent();

        $posScript = strpos($html, "localStorage.getItem('caderneta.tema')");
        $this->assertNotFalse($posScript);
        $this->assertLessThan(strpos($html, '</head>'), $posScript);
        $this->assertStringContainsString('<html lang="pt-BR">', $html);

        // No template, o script precisa vir antes do @vite (CSS/JS do painel).
        $template = file_get_contents(resource_path('views/app.blade.php'));
        $this->assertLessThan(strpos($template, '@vite'), strpos($template, "localStorage.getItem('caderneta.tema')"));
    }
}
