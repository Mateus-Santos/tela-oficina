<?php

namespace Tests\Feature\Marketing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtribuicaoCampanhaTest extends TestCase
{
    use RefreshDatabase;

    private function pedido(array $extra = []): array
    {
        return array_merge([
            'nome' => 'Visitante de campanha',
            'whatsapp' => '75999990000',
            'peca' => 'Filtro de óleo',
            'aceite_privacidade' => '1',
        ], $extra);
    }

    public function test_campanha_da_home_segue_para_formulario_e_nao_e_substituida_no_post(): void
    {
        $this->get('/?utm_source=google&utm_campaign=pecas_novembro&gclid=CLI123')
            ->assertOk();

        $this->get('/pecas/orcamento')
            ->assertOk()
            ->assertSee('name="utm_source" value="google"', false)
            ->assertSee('name="utm_campaign" value="pecas_novembro"', false)
            ->assertSee('name="gclid" value="CLI123"', false);

        $this->post('/pecas/orcamento', $this->pedido([
            'utm_source' => 'fraudado',
            'utm_campaign' => 'outro',
            'gclid' => 'alterado',
        ]))->assertRedirect(route('marketing.pecas.orcamento'));

        $this->assertDatabaseHas('marketing_leads', [
            'utm_source' => 'google',
            'utm_campaign' => 'pecas_novembro',
            'gclid' => 'CLI123',
        ]);
    }

    public function test_visita_direta_nao_apaga_atribuicao_guardada(): void
    {
        $this->get('/home?utm_source=instagram&utm_medium=social')->assertOk();
        $this->get('/')->assertOk();
        $this->get('/pecas/orcamento')->assertOk()
            ->assertSee('name="utm_medium" value="social"', false);

        $this->post('/pecas/orcamento', $this->pedido())
            ->assertRedirect(route('marketing.pecas.orcamento'));

        $this->assertDatabaseHas('marketing_leads', [
            'utm_source' => 'instagram',
            'utm_medium' => 'social',
        ]);
    }

    public function test_nova_campanha_substitui_inteiramente_a_anterior(): void
    {
        $this->get('/?utm_source=google&gclid=ANTIGO')->assertOk();
        $this->get('/home?utm_source=meta&utm_campaign=retorno')->assertOk();

        $this->post('/pecas/orcamento', $this->pedido(['gclid' => 'forjado']))
            ->assertRedirect(route('marketing.pecas.orcamento'));

        $this->assertDatabaseHas('marketing_leads', [
            'utm_source' => 'meta',
            'utm_campaign' => 'retorno',
            'gclid' => null,
        ]);
    }

    public function test_parametros_invalidos_nao_sobrescrevem_origem_valida(): void
    {
        $this->get('/?utm_source=google&gclid=VALIDO')->assertOk();
        $this->get('/pecas/orcamento?utm_source%5B%5D=invalido&gclid='.str_repeat('X', 201))
            ->assertOk()
            ->assertSee('name="utm_source" value="google"', false);

        $this->post('/pecas/orcamento', $this->pedido())
            ->assertRedirect(route('marketing.pecas.orcamento'));

        $this->assertDatabaseHas('marketing_leads', [
            'utm_source' => 'google',
            'gclid' => 'VALIDO',
        ]);
    }

    public function test_post_legado_sem_sessao_rastreada_continua_aceito(): void
    {
        $this->post('/pecas/orcamento', $this->pedido([
            'utm_source' => 'indicacao',
            'utm_campaign' => 'whatsapp',
        ]))->assertRedirect(route('marketing.pecas.orcamento'));

        $this->assertDatabaseHas('marketing_leads', [
            'utm_source' => 'indicacao',
            'utm_campaign' => 'whatsapp',
        ]);
    }

    public function test_erro_de_formulario_nao_permitem_trocar_origem(): void
    {
        $this->get('/?utm_source=google')->assertOk();

        $this->post('/pecas/orcamento', $this->pedido([
            'nome' => '',
            'utm_source' => 'forjado',
        ]))->assertSessionHasErrors('nome');

        $this->get('/pecas/orcamento')->assertOk()
            ->assertSee('name="utm_source" value="google"', false)
            ->assertDontSee('name="utm_source" value="forjado"', false);

        $this->assertDatabaseCount('marketing_leads', 0);
    }
}
