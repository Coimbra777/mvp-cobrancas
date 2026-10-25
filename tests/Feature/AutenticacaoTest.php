<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_nao_existe_api_publica(): void
    {
        $this->getJson('/api/user')->assertNotFound();
    }
}
