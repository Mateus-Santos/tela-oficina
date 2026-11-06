<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePublicaTest extends TestCase
{
    public function test_home_mostra_servicos_contato_e_mapa(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Serviços para cuidar do seu veículo')
            ->assertSee('Mapa de localização da Oficina SOS Mecânica')
            ->assertSee('https://wa.me/'.config('oficina.whatsapp'), false);
    }
}
