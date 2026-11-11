<?php

namespace Tests\Feature\Marketing;

use App\Models\Cliente;
use App\Models\MarketingClienteAcesso;
use App\Models\MarketingLead;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcessoIndividualPortalTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioCliente(): array
    {
        $usuario = User::factory()->create([
            'perfil' => User::PERFIL_CLIENTE,
            'status' => true,
        ]);
        $cliente = Cliente::create([
            'pessoa_id' => $usuario->pessoa_id,
            'pontos' => 0,
        ]);

        return [$usuario, $cliente];
    }

    private function habilitarGlobais(): void
    {
        config()->set('marketing.portal_inicio_habilitado', true);
        config()->set('marketing.portal_solicitacoes_habilitado', true);
    }

    public function test_sem_concessao_global_ligado_nao_abre_modulos_novos(): void
    {
        $this->habilitarGlobais();
        [$usuario, $cliente] = $this->usuarioCliente();

        $this->actingAs($usuario)
            ->get(route('cliente.area.index'))->assertNotFound();
        $this->get(route('cliente.solicitacoes.index'))->assertNotFound();
        $this->get(route('cliente.solicitacoes.show', 1))->assertNotFound();
        $this->get(route('cliente.notas.index'))->assertOk()
            ->assertDontSee('Minhas solicitações de orçamento')
            ->assertDontSee('Minha área', false);

        $this->assertDatabaseMissing('marketing_cliente_acessos', [
            'cliente_id' => $cliente->id,
        ]);
    }

    public function test_concessao_individual_do_painel_nao_libera_solicitacoes(): void
    {
        $this->habilitarGlobais();
        [$usuario, $cliente] = $this->usuarioCliente();
        MarketingClienteAcesso::create([
            'cliente_id' => $cliente->id,
            'portal_inicio' => true,
            'solicitacoes' => false,
        ]);

        $this->actingAs($usuario)
            ->get(route('cliente.area.index'))->assertOk()
            ->assertSee('Minha área')
            ->assertDontSee('Ver solicitações');
        $this->get(route('cliente.solicitacoes.index'))->assertNotFound();
        $this->get(route('cliente.notas.index'))->assertOk()
            ->assertDontSee('Minhas solicitações de orçamento');
    }

    public function test_concessao_de_solicitacoes_nao_libera_painel_e_isola_dados(): void
    {
        $this->habilitarGlobais();
        [$usuario, $cliente] = $this->usuarioCliente();
        [, $outroCliente] = $this->usuarioCliente();
        MarketingClienteAcesso::create([
            'cliente_id' => $cliente->id,
            'portal_inicio' => false,
            'solicitacoes' => true,
        ]);

        $propria = MarketingLead::create([
            'protocolo' => 'MKT-ACESSO-1', 'nome' => 'Cliente teste',
            'whatsapp' => '75999990000', 'peca' => 'Filtro',
            'status' => 'novo', 'consentido_em' => now(),
        ]);
        $propria->forceFill(['cliente_id' => $cliente->id])->save();
        $alheia = MarketingLead::create([
            'protocolo' => 'MKT-ACESSO-2', 'nome' => 'Outro teste',
            'whatsapp' => '75999990001', 'peca' => 'Correia',
            'status' => 'novo', 'consentido_em' => now(),
        ]);
        $alheia->forceFill(['cliente_id' => $outroCliente->id])->save();

        $this->actingAs($usuario)
            ->get(route('cliente.area.index'))->assertNotFound();
        $this->get(route('cliente.solicitacoes.index'))->assertOk()
            ->assertSee($propria->protocolo)
            ->assertDontSee($alheia->protocolo);
        $this->get(route('cliente.solicitacoes.show', $alheia->id))
            ->assertNotFound();
        $this->get(route('cliente.notas.index'))->assertOk()
            ->assertSee('Minhas solicitações de orçamento');
    }

    public function test_revogacao_oculta_notas_marketing_mas_preserva_notas_comuns(): void
    {
        $this->habilitarGlobais();
        [$usuario, $cliente] = $this->usuarioCliente();
        $acesso = MarketingClienteAcesso::create([
            'cliente_id' => $cliente->id,
            'portal_inicio' => true,
            'solicitacoes' => true,
        ]);
        $comum = Nota::create([
            'cliente_id' => $cliente->id, 'tipo' => 'Venda',
            'status' => 'Aberto', 'subtotal' => '25.00',
            'desconto' => '0.00', 'total' => '25.00',
        ]);
        $marketing = Nota::create([
            'cliente_id' => $cliente->id, 'tipo' => 'Venda',
            'status' => 'Aberto', 'subtotal' => '15.00',
            'desconto' => '0.00', 'total' => '15.00',
        ]);
        $lead = MarketingLead::create([
            'protocolo' => 'MKT-ACESSO-3', 'nome' => 'Cliente teste',
            'whatsapp' => '75999990000', 'peca' => 'Pastilha',
            'status' => 'novo', 'consentido_em' => now(),
        ]);
        $lead->forceFill([
            'cliente_id' => $cliente->id,
            'nota_id' => $marketing->id,
        ])->save();

        $this->actingAs($usuario)
            ->get(route('cliente.notas.show', $marketing->id))->assertOk();

        $acesso->update(['portal_inicio' => false, 'solicitacoes' => false]);

        $this->get(route('cliente.area.index'))->assertNotFound();
        $this->get(route('cliente.solicitacoes.index'))->assertNotFound();
        $this->get(route('cliente.notas.show', $marketing->id))->assertNotFound();
        $this->get(route('cliente.notas.index'))->assertOk()
            ->assertSee(sprintf('Nota #%06d', $comum->id))
            ->assertDontSee(sprintf('Nota #%06d', $marketing->id));
        $this->get(route('cliente.notas.show', $comum->id))->assertOk();
    }

    public function test_administrador_controla_grants_sem_alterar_outros_campos(): void
    {
        [$usuario, $cliente] = $this->usuarioCliente();
        $admin = User::factory()->create([
            'perfil' => User::PERFIL_ADMIN, 'status' => true,
        ]);
        $this->actingAs($admin)
            ->get(route('marketing.clientes-acessos.index'))
            ->assertOk()
            ->assertSee((string) $cliente->id);

        $this->patch(route('marketing.clientes-acessos.update', $cliente), [
            'portal_inicio' => '1',
            'solicitacoes' => '0',
            'pontos' => 9999,
            'atualizado_por' => $usuario->id,
        ])->assertRedirect(route('marketing.clientes-acessos.index'));

        $this->assertDatabaseHas('marketing_cliente_acessos', [
            'cliente_id' => $cliente->id,
            'portal_inicio' => 1,
            'solicitacoes' => 0,
            'atualizado_por' => $admin->id,
        ]);
        $this->assertSame(0, (int) $cliente->fresh()->pontos);

        $this->patch(route('marketing.clientes-acessos.update', $cliente), [
            'portal_inicio' => '2', 'solicitacoes' => '1',
        ])->assertSessionHasErrors('portal_inicio');
        $this->assertDatabaseHas('marketing_cliente_acessos', [
            'cliente_id' => $cliente->id, 'solicitacoes' => 0,
        ]);
    }

    public function test_visitantes_clientes_e_colaboradores_nao_editam_grants(): void
    {
        [$clienteUsuario, $cliente] = $this->usuarioCliente();
        $this->get(route('marketing.clientes-acessos.index'))
            ->assertRedirect(route('erro-autenticacao'));
        $this->patch(route('marketing.clientes-acessos.update', $cliente), [
            'portal_inicio' => '1', 'solicitacoes' => '1',
        ])->assertRedirect(route('erro-autenticacao'));

        foreach ([$clienteUsuario, User::factory()->create([
            'perfil' => User::PERFIL_COLABORADOR, 'status' => true,
        ])] as $usuario) {
            $this->actingAs($usuario)
                ->get(route('marketing.clientes-acessos.index'))->assertForbidden();
            $this->patch(route('marketing.clientes-acessos.update', $cliente), [
                'portal_inicio' => '1', 'solicitacoes' => '1',
            ])->assertForbidden();
        }
        $this->assertDatabaseCount('marketing_cliente_acessos', 0);
    }

    public function test_bloqueio_global_sempre_prevalece_sobre_concessao(): void
    {
        [$usuario, $cliente] = $this->usuarioCliente();
        MarketingClienteAcesso::create([
            'cliente_id' => $cliente->id,
            'portal_inicio' => true,
            'solicitacoes' => true,
        ]);
        config()->set('marketing.portal_inicio_habilitado', false);
        config()->set('marketing.portal_solicitacoes_habilitado', false);

        $this->actingAs($usuario)
            ->get(route('cliente.area.index'))->assertNotFound();
        $this->get(route('cliente.solicitacoes.index'))->assertNotFound();
    }
}
