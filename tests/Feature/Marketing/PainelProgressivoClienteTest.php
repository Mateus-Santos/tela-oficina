<?php

namespace Tests\Feature\Marketing;

use App\Models\Cliente;
use App\Models\MarketingClienteAcesso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PainelProgressivoClienteTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): User
    {
        $usuario = User::factory()->create([
            'perfil' => User::PERFIL_CLIENTE,
            'status' => true,
        ]);

        $cliente = Cliente::create([
            'pessoa_id' => $usuario->pessoa_id,
            'pontos' => 0,
        ]);

        // Nos testes antigos, a concessao e explicita, nunca automatica em producao.
        MarketingClienteAcesso::create([
            'cliente_id' => $cliente->id,
            'portal_inicio' => true,
            'solicitacoes' => true,
        ]);

        return $usuario;
    }

    public function test_painel_desabilitado_nao_altera_portal_antigo(): void
    {
        config()->set('marketing.portal_inicio_habilitado', false);
        config()->set('marketing.portal_solicitacoes_habilitado', false);

        $this->actingAs($this->cliente())
            ->get(route('cliente.area.index'))->assertNotFound();

        $this->get(route('cliente.notas.index'))
            ->assertOk()
            ->assertDontSee('Minha área', false)
            ->assertDontSee('Minhas solicitações de orçamento');

        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('marketing_leads', 0);
    }

    public function test_cliente_acessa_painel_liberado_com_links_existentes(): void
    {
        config()->set('marketing.portal_inicio_habilitado', true);
        config()->set('marketing.portal_solicitacoes_habilitado', false);

        $this->actingAs($this->cliente())
            ->get(route('cliente.area.index'))
            ->assertOk()
            ->assertSee('Minha área')
            ->assertSee('Produtos disponíveis')
            ->assertSee('Minhas notas')
            ->assertSee('Minhas ordens de serviço')
            ->assertSee('Meus veículos')
            ->assertSee('Meus endereços')
            ->assertDontSee('Ver solicitações')
            ->assertSee(route('cliente.notas.index'));

        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('marketing_leads', 0);
    }

    public function test_solicitacoes_sao_exibidas_somente_com_seu_proprio_recurso_liberado(): void
    {
        config()->set('marketing.portal_inicio_habilitado', true);
        config()->set('marketing.portal_solicitacoes_habilitado', true);

        $this->actingAs($this->cliente())
            ->get(route('cliente.area.index'))
            ->assertOk()
            ->assertSee('Ver solicitações')
            ->assertSee(route('cliente.solicitacoes.index'));

        $this->get(route('cliente.solicitacoes.index'))->assertOk();
    }

    public function test_visitantes_e_outros_perfis_nao_acessam_painel(): void
    {
        config()->set('marketing.portal_inicio_habilitado', true);

        $this->get(route('cliente.area.index'))
            ->assertRedirect(route('erro-autenticacao'));

        foreach ([User::PERFIL_ADMIN, User::PERFIL_COLABORADOR] as $perfil) {
            $usuario = User::factory()->create([
                'perfil' => $perfil,
                'status' => true,
            ]);
            $this->actingAs($usuario)
                ->get(route('cliente.area.index'))->assertForbidden();
        }
    }

    public function test_liberacao_de_solicitacoes_nao_depende_do_novo_painel(): void
    {
        config()->set('marketing.portal_inicio_habilitado', false);
        config()->set('marketing.portal_solicitacoes_habilitado', true);

        $this->actingAs($this->cliente())
            ->get(route('cliente.area.index'))->assertNotFound();

        $this->get(route('cliente.notas.index'))
            ->assertOk()
            ->assertSee('Minhas solicitações de orçamento')
            ->assertDontSee('Minha área', false);

        $this->get(route('cliente.solicitacoes.index'))->assertOk();
    }
}
