<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsentimentoMapaTest extends TestCase
{
    public function test_home_nao_carrega_iframe_do_google_sem_consentimento(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-sos-map-src=', false)
            ->assertSee('data-sos-cookie-banner', false)
            ->assertSee('Rejeitar opcionais')
            ->assertDontSee('<iframe', false);
    }
}
