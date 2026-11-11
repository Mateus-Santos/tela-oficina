<?php

namespace Tests\Feature\Marketing;

use App\Models\Cliente;
use App\Models\MarketingLead;
use App\Models\MarketingClienteAcesso;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitacoesPortalClienteTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioCliente(): array
    {
        $user = User::factory()->create([
            'perfil' => User::PERFIL_CLIENTE,
            'status' => true,
        ]);
        $cliente = Cliente::create([
            'pessoa_id' => $user->pessoa_id,
            'pontos' => 0,
        ]);

        MarketingClienteAcesso::create([
            'cliente_id' => $cliente->id,
            'portal_inicio' => true,
            'solicitacoes' => true,
        ]);

        return [$user, $cliente];
    }

    private function solicitacao(?Cliente $cliente, string $protocolo): MarketingLead
    {
        $lead = MarketingLead::create([
            'protocolo' => $protocolo,
            'nome' => 'Pessoa interessada',
            'whatsapp' => '75999990000',
            'peca' => 'Filtro de oleo',
            'status' => 'em_atendimento',
            'consentido_em' => now(),
        ]);
        if ($cliente !== null) {
            $lead->forceFill(['cliente_id' => $cliente->id])->save();
        }
        $lead->itens()->create([
            'descricao' => 'Filtro consultado',
            'quantidade' => 2,
            'ordem' => 1,
        ]);
        return $lead;
    }

    private function nota(Cliente $cliente): Nota
    {
        return Nota::create([
            'cliente_id' => $cliente->id,
            'tipo' => 'Venda',
            'status' => 'Aberto',
            'subtotal' => '50.00',
            'desconto' => '0.00',
            'total' => '50.00',
        ]);
    }

    public function test_portal_desligado_nao_exibe_solicitacoes_nem_notas_de_marketing(): void
    {
        config()->set('marketing.portal_solicitacoes_habilitado', false);
        [$user, $cliente] = $this->usuarioCliente();
        $lead = $this->solicitacao($cliente, 'MKT-PORTAL0001');
        $notaMarketing = $this->nota($cliente);
        $notaNormal = $this->nota($cliente);
        $lead->forceFill(['nota_id' => $notaMarketing->id])->save();

        $this->actingAs($user)
            ->get(route('cliente.solicitacoes.index'))->assertNotFound();
        $this->get(route('cliente.solicitacoes.show', $lead->id))->assertNotFound();
        $this->get(route('cliente.notas.index'))
            ->assertOk()
            ->assertSee(sprintf('Nota #%06d', $notaNormal->id))
            ->assertDontSee(sprintf('Nota #%06d', $notaMarketing->id))
            ->assertDontSee('Minhas solicitações de orçamento');
        $this->get(route('cliente.notas.show', $notaMarketing->id))->assertNotFound();
    }

    public function test_visitante_e_perfis_nao_clientes_nao_acessam_solicitacoes(): void
    {
        config()->set('marketing.portal_solicitacoes_habilitado', true);
        $this->get(route('cliente.solicitacoes.index'))
            ->assertRedirect(route('erro-autenticacao'));
        foreach ([User::PERFIL_ADMIN, User::PERFIL_COLABORADOR] as $perfil) {
            $user = User::factory()->create(['perfil' => $perfil, 'status' => true]);
            $this->actingAs($user)
                ->get(route('cliente.solicitacoes.index'))->assertForbidden();
            $this->get(route('cliente.solicitacoes.show', 1))->assertForbidden();
        }
    }

    public function test_cliente_visualiza_somente_solicitacoes_vinculadas_ao_seu_cadastro(): void
    {
        config()->set('marketing.portal_solicitacoes_habilitado', true);
        [$user, $cliente] = $this->usuarioCliente();
        [, $outro] = $this->usuarioCliente();
        $propria = $this->solicitacao($cliente, 'MKT-PORTAL0002');
        $alheia = $this->solicitacao($outro, 'MKT-PORTAL0003');
        $semVinculo = $this->solicitacao(null, 'MKT-PORTAL0004');

        $this->actingAs($user)
            ->get(route('cliente.solicitacoes.index'))
            ->assertOk()
            ->assertSee($propria->protocolo)
            ->assertDontSee($alheia->protocolo)
            ->assertDontSee($semVinculo->protocolo);

        $this->get(route('cliente.solicitacoes.show', $alheia->id))->assertNotFound();
        $this->get(route('cliente.solicitacoes.show', $semVinculo->id))->assertNotFound();
        $this->get(route('cliente.solicitacoes.show', $propria->id))
            ->assertOk()
            ->assertSee('Filtro consultado')
            ->assertSee('Quantidade: 2')
            ->assertDontSee('75999990000')
            ->assertDontSee('utm_campaign');
    }

    public function test_cliente_sem_cadastro_nao_recebe_solicitacoes(): void
    {
        config()->set('marketing.portal_solicitacoes_habilitado', true);
        $user = User::factory()->create(['perfil' => User::PERFIL_CLIENTE, 'status' => true]);
        $this->solicitacao(null, 'MKT-PORTAL0005');
        $this->actingAs($user)
            ->get(route('cliente.solicitacoes.index'))
            ->assertNotFound();
        $this->get(route('cliente.solicitacoes.show', 1))->assertNotFound();
    }

    public function test_nota_vinculada_so_aparece_se_pertencer_ao_mesmo_cliente(): void
    {
        config()->set('marketing.portal_solicitacoes_habilitado', true);
        [$user, $cliente] = $this->usuarioCliente();
        [, $outro] = $this->usuarioCliente();
        $lead = $this->solicitacao($cliente, 'MKT-PORTAL0006');
        $notaAlheia = $this->nota($outro);
        $lead->forceFill(['nota_id' => $notaAlheia->id])->save();

        $this->actingAs($user)
            ->get(route('cliente.solicitacoes.show', $lead->id))
            ->assertOk()
            ->assertDontSee('Consultar nota vinculada');
        $this->get(route('cliente.notas.show', $notaAlheia->id))->assertNotFound();

        $notaPropria = $this->nota($cliente);
        $lead->forceFill(['nota_id' => $notaPropria->id])->save();
        $this->get(route('cliente.solicitacoes.show', $lead->id))
            ->assertOk()->assertSee('Consultar nota vinculada');
        $this->get(route('cliente.notas.show', $notaPropria->id))->assertOk();
    }
}
