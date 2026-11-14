<?php

namespace Tests\Feature;

use Tests\TestCase;

class CadastroClientePublicoLinksTest extends TestCase
{
    public function test_home_login_e_formulario_oferecem_cadastro_seguro_quando_liberado(): void
    {
        config()->set('marketing.cadastro_cliente_habilitado', true);
        $url = route('marketing.cadastro.create');

        $this->get('/')->assertOk()->assertSee('href="'.$url.'"', false);
        $this->get('/login')->assertOk()->assertSee('href="'.$url.'"', false);
        $this->get($url)->assertOk()->assertSee('Criar minha conta de cliente');
    }

    public function test_cadastro_desligado_nao_divulga_link_indisponivel(): void
    {
        config()->set('marketing.cadastro_cliente_habilitado', false);
        $url = route('marketing.cadastro.create');

        $this->get('/')->assertOk()->assertDontSee('href="'.$url.'"', false);
        $this->get('/login')->assertOk()->assertDontSee('href="'.$url.'"', false);
        $this->get($url)->assertNotFound();
    }
}
