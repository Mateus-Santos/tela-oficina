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
            ->assertSee('Venha visitar a SOS Mecânica')
            ->assertSee('Carregar mapa do Google')
            ->assertSee('data-sos-map-src', false)
            ->assertDontSee('<iframe', false)
            ->assertSee('https://wa.me/'.config('oficina.whatsapp'), false);
    }
}
