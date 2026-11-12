<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrivacidadePaginasTest extends TestCase
{
    public function test_paginas_de_transparencia_sao_publicas(): void
    {
        $this->get(route('privacidade.politica'))
            ->assertOk()
            ->assertSee('Política de Privacidade')
            ->assertSee('medidas pré-contratuais');

        $this->get(route('privacidade.cookies'))
            ->assertOk()
            ->assertSee('Google Maps')
            ->assertSee('localStorage');

        $this->get(route('privacidade.direitos'))
            ->assertOk()
            ->assertSee('Seus direitos sobre dados pessoais')
            ->assertSee('Solicitação LGPD');
    }

    public function test_home_oferece_rejeicao_e_nao_injeta_mapa_externo_antes_da_escolha(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Rejeitar opcionais')
            ->assertSee('data-sos-map-src', false)
            ->assertDontSee('<iframe', false)
            ->assertSee('Política de Cookies');
    }

    public function test_termos_nao_tratam_uso_do_site_como_consentimento_geral(): void
    {
        $this->get(route('termos'))
            ->assertOk()
            ->assertSee('não significa consentimento geral')
            ->assertSee('proteção e defesa do consumidor');
    }
}
