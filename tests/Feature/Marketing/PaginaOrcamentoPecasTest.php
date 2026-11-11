<?php

namespace Tests\Feature\Marketing;

use Tests\TestCase;

class PaginaOrcamentoPecasTest extends TestCase
{
    public function test_visitante_acessa_pagina_de_pecas_sem_login(): void
    {
        $this->get(route('marketing.pecas.orcamento'))
            ->assertOk()
            ->assertSee('Precisa de uma peça para seu carro?')
            ->assertSee('Como solicitar orçamento de peças')
            ->assertSee('A compatibilidade, o preço e a disponibilidade serão confirmados no atendimento.')
            ->assertSee('https://wa.me/'.preg_replace('/\D+/', '', config('oficina.whatsapp')), false);
    }

    public function test_home_original_e_link_para_pecas_sao_preservados(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Cuidado de verdade para o seu carro.')
            ->assertSee('Serviços para cuidar do seu veículo')
            ->assertSee(
                'href="'.route('marketing.pecas.orcamento').'"',
                false
            );
    }

    public function test_rota_de_pecas_nao_exige_login_e_exibe_formulario(): void
    {
        $this->get('/pecas/orcamento')
            ->assertOk()
            ->assertSee('name="peca"', false)
            ->assertSee('name="aceite_privacidade"', false)
            ->assertDontSee('Cuidado de verdade para o seu carro.');
    }
}
