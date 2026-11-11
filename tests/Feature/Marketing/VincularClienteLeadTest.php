<?php

namespace Tests\Feature\Marketing;

use App\Models\Cliente;
use App\Models\MarketingClienteAcesso;
use App\Models\MarketingLead;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VincularClienteLeadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $perfil = User::PERFIL_ADMIN): User
    {
        return User::factory()->create(['perfil' => $perfil, 'status' => true]);
    }

    private function cliente(): array
    {
        $usuario = $this->admin(User::PERFIL_CLIENTE);
        $cliente = Cliente::create(['pessoa_id' => $usuario->pessoa_id, 'pontos' => 0]);

        return [$usuario, $cliente];
    }

    private function solicitacao(string $protocolo = 'MKT-VINCULO-001'): MarketingLead
    {
        return MarketingLead::create([
            'protocolo' => $protocolo,
            'nome' => 'Interessado sem cadastro',
            'whatsapp' => '75999990000',
            'peca' => 'Filtro',
            'consentido_em' => now(),
            'status' => 'novo',
        ]);
    }

    private function dados(Cliente $cliente, MarketingLead $lead): array
    {
        return [
            'cliente_id' => $cliente->id,
            'protocolo_confirmacao' => $lead->protocolo,
            'confirmar_identidade' => '1',
        ];
    }

    public function test_somente_administrador_pode_vincular_ou_desvincular(): void
    {
        $lead = $this->solicitacao();
        [$clienteUsuario, $cliente] = $this->cliente();
        $dados = $this->dados($cliente, $lead);
        $desvincular = [
            'protocolo_confirmacao' => $lead->protocolo,
            'confirmar_desvinculacao' => '1',
        ];

        $this->post(route('marketing.leads.vincular-cliente', $lead), $dados)
            ->assertRedirect(route('erro-autenticacao'));
        $this->delete(route('marketing.leads.desvincular-cliente', $lead), $desvincular)
            ->assertRedirect(route('erro-autenticacao'));

        foreach ([$clienteUsuario, $this->admin(User::PERFIL_COLABORADOR)] as $usuario) {
            $this->actingAs($usuario)
                ->post(route('marketing.leads.vincular-cliente', $lead), $dados)
                ->assertForbidden();
            $this->delete(route('marketing.leads.desvincular-cliente', $lead), $desvincular)
                ->assertForbidden();
        }

        $this->assertNull($lead->fresh()->cliente_id);
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_admin_vincula_explicitamente_sem_criar_cadastro_nota_ou_cobranca(): void
    {
        $lead = $this->solicitacao();
        [, $cliente] = $this->cliente();
        $this->actingAs($this->admin())
            ->post(route('marketing.leads.vincular-cliente', $lead), [
                ...$this->dados($cliente, $lead),
                'nota_id' => 99999,
                'status' => 'concluido',
                'pontos' => 9999,
            ])
            ->assertRedirect(route('marketing.leads.show', $lead))
            ->assertSessionHas('marketing_vinculo_sucesso');

        $this->assertSame($cliente->id, (int) $lead->fresh()->cliente_id);
        $this->assertNull($lead->fresh()->nota_id);
        $this->assertSame('novo', $lead->fresh()->status);
        $this->assertSame(0, (int) $cliente->fresh()->pontos);
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('notas_itens', 0);
        $this->assertDatabaseCount('contas_receber', 0);
    }

    public function test_confirmacao_de_identidade_e_protocolo_sao_obrigatorios(): void
    {
        $lead = $this->solicitacao();
        [, $cliente] = $this->cliente();
        $this->actingAs($this->admin());

        $this->post(route('marketing.leads.vincular-cliente', $lead), [
            ...$this->dados($cliente, $lead), 'confirmar_identidade' => '0',
        ])->assertSessionHasErrors('confirmar_identidade');
        $this->post(route('marketing.leads.vincular-cliente', $lead), [
            ...$this->dados($cliente, $lead), 'protocolo_confirmacao' => 'MKT-OUTRO',
        ])->assertSessionHasErrors('protocolo_confirmacao');
        $this->post(route('marketing.leads.vincular-cliente', $lead), [
            ...$this->dados($cliente, $lead), 'cliente_id' => 999999,
        ])->assertSessionHasErrors('cliente_id');

        $this->assertNull($lead->fresh()->cliente_id);
    }

    public function test_vinculo_existente_nao_e_substituido_por_outro_cliente(): void
    {
        $lead = $this->solicitacao();
        [, $primeiro] = $this->cliente();
        [, $segundo] = $this->cliente();
        $this->actingAs($this->admin());

        $this->post(route('marketing.leads.vincular-cliente', $lead), $this->dados($primeiro, $lead))
            ->assertRedirect(route('marketing.leads.show', $lead));
        $this->post(route('marketing.leads.vincular-cliente', $lead), $this->dados($segundo, $lead))
            ->assertSessionHasErrors('cliente_id');

        $this->assertSame($primeiro->id, (int) $lead->fresh()->cliente_id);
    }

    public function test_nao_altera_vinculo_de_solicitacao_encerrada_ou_convertida(): void
    {
        $lead = $this->solicitacao();
        [, $cliente] = $this->cliente();
        $this->actingAs($this->admin());

        $lead->update(['status' => 'descartado']);
        $this->post(route('marketing.leads.vincular-cliente', $lead), $this->dados($cliente, $lead))
            ->assertSessionHasErrors('vinculo');

        $lead->update(['status' => 'novo']);
        $nota = Nota::create([
            'cliente_id' => $cliente->id, 'tipo' => 'Venda',
            'status' => 'Aberto', 'subtotal' => '10.00',
            'desconto' => '0.00', 'total' => '10.00',
        ]);
        $lead->forceFill(['cliente_id' => $cliente->id, 'nota_id' => $nota->id])->save();

        $this->delete(route('marketing.leads.desvincular-cliente', $lead), [
            'protocolo_confirmacao' => $lead->protocolo,
            'confirmar_desvinculacao' => '1',
        ])->assertSessionHasErrors('vinculo');

        $this->assertSame($cliente->id, (int) $lead->fresh()->cliente_id);
        $this->assertSame($nota->id, (int) $lead->fresh()->nota_id);
    }

    public function test_admin_desvincula_e_portal_para_de_exibir_solicitacao(): void
    {
        config()->set('marketing.portal_solicitacoes_habilitado', true);
        $lead = $this->solicitacao();
        [$usuario, $cliente] = $this->cliente();
        MarketingClienteAcesso::create([
            'cliente_id' => $cliente->id,
            'portal_inicio' => false,
            'solicitacoes' => true,
        ]);

        $admin = $this->admin();
        $this->actingAs($admin)
            ->post(route('marketing.leads.vincular-cliente', $lead), $this->dados($cliente, $lead))
            ->assertRedirect();

        $this->actingAs($usuario)->get(route('cliente.solicitacoes.index'))
            ->assertOk()->assertSee($lead->protocolo);

        $this->actingAs($admin)
            ->delete(route('marketing.leads.desvincular-cliente', $lead), [
                'protocolo_confirmacao' => $lead->protocolo,
                'confirmar_desvinculacao' => '1',
            ])
            ->assertRedirect(route('marketing.leads.show', $lead))
            ->assertSessionHas('marketing_vinculo_sucesso');

        $this->assertNull($lead->fresh()->cliente_id);
        $this->actingAs($usuario)->get(route('cliente.solicitacoes.index'))
            ->assertOk()->assertDontSee($lead->protocolo);
        $this->get(route('cliente.solicitacoes.show', $lead))->assertNotFound();
    }

    public function test_conversao_nao_troca_cliente_ja_conferido(): void
    {
        config()->set('marketing.conversao_nota_habilitada', true);
        $lead = $this->solicitacao();
        [, $primeiro] = $this->cliente();
        [, $segundo] = $this->cliente();
        $lead->forceFill(['cliente_id' => $primeiro->id])->save();
        $item = $lead->itens()->create([
            'descricao' => 'Peça aguardando revisão',
            'quantidade' => 1,
            'ordem' => 1,
        ]);

        $this->actingAs($this->admin())
            ->post(route('marketing.leads.converter-nota', $lead), [
                'cliente_id' => $segundo->id,
                'precos' => [$item->id => '49.90'],
                'confirmar_revisao' => '1',
            ])
            ->assertSessionHasErrors('cliente_id');

        $this->assertSame($primeiro->id, (int) $lead->fresh()->cliente_id);
        $this->assertNull($lead->fresh()->nota_id);
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_tela_exibe_vinculacao_controlada_para_admin(): void
    {
        $lead = $this->solicitacao();
        $this->get(route('marketing.leads.show', $lead))
            ->assertRedirect(route('erro-autenticacao'));
        $this->actingAs($this->admin(User::PERFIL_COLABORADOR))
            ->get(route('marketing.leads.show', $lead))->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('marketing.leads.show', $lead))
            ->assertOk()
            ->assertSee('Vincular cliente confirmado')
            ->assertSee('Digite o protocolo da solicitação');
    }
}
