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

    public function test_politica_exibe_identificacao_juridica_quando_for_configurada(): void
    {
        config()->set('oficina.razao_social', 'Empresa de Teste LTDA');
        config()->set('oficina.cnpj', '00.000.000/0000-00');
        config()->set('oficina.privacidade_email', 'privacidade@example.test');

        $this->get(route('privacidade.politica'))
            ->assertOk()
            ->assertSee('Empresa de Teste LTDA')
            ->assertSee('00.000.000/0000-00')
            ->assertSee('mailto:privacidade@example.test', false)
            ->assertSee('Canal para solicitações sobre dados pessoais');
    }

    public function test_direitos_prioriza_canal_de_privacidade(): void
    {
        config()->set('oficina.privacidade_email', 'privacidade@example.test');

        $this->get(route('privacidade.direitos'))
            ->assertOk()
            ->assertSee('mailto:privacidade@example.test', false)
            ->assertSee('WhatsApp oficial');
    }

    public function test_politica_nao_inventa_cnpj_e_mantem_canal_existente(): void
    {
        config()->set('oficina.razao_social', '');
        config()->set('oficina.cnpj', '');
        config()->set('oficina.privacidade_email', '');
        config()->set('oficina.email', '');

        $this->get(route('privacidade.politica'))
            ->assertOk()
            ->assertDontSee('<strong>CNPJ:</strong>', false)
            ->assertSee('WhatsApp da oficina');
    }

    public function test_termos_nao_tratam_uso_do_site_como_consentimento_geral(): void
    {
        $this->get(route('termos'))
            ->assertOk()
            ->assertSee('não significa consentimento geral')
            ->assertSee('proteção e defesa do consumidor');
    }
}
