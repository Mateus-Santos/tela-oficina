<?php

namespace Tests\Feature\Marketing;

use App\Models\MarketingLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtualizarStatusLeadTest extends TestCase
{
    use RefreshDatabase;

    private function lead(): MarketingLead
    {
        return MarketingLead::create([
            'protocolo' => 'MKT-STATUS00001',
            'nome' => 'Solicitante confidencial',
            'whatsapp' => '75999990000',
            'peca' => 'Filtro de óleo',
            'status' => 'novo',
            'consentido_em' => now(),
            'utm_source' => 'google',
        ]);
    }

    private function usuario(string $perfil, bool $ativo = true): User
    {
        return User::factory()->create([
            'perfil' => $perfil,
            'status' => $ativo,
        ]);
    }

    public function test_visitante_nao_altera_status(): void
    {
        $lead = $this->lead();

        $this->patch(route('marketing.leads.status.update', $lead), [
            'status' => 'concluido',
        ])->assertRedirect(route('erro-autenticacao'));

        $this->assertSame('novo', $lead->fresh()->status);
    }

    public function test_cliente_e_colaborador_nao_alteram_status(): void
    {
        $lead = $this->lead();

        foreach ([User::PERFIL_CLIENTE, User::PERFIL_COLABORADOR] as $perfil) {
            $this->actingAs($this->usuario($perfil))
                ->patch(route('marketing.leads.status.update', $lead), [
                    'status' => 'concluido',
                ])
                ->assertForbidden();
            $this->assertSame('novo', $lead->fresh()->status);
        }
    }

    public function test_admin_altera_status_e_recebe_confirmacao(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->usuario(User::PERFIL_ADMIN))
            ->patch(route('marketing.leads.status.update', $lead), [
                'status' => 'em_atendimento',
            ])
            ->assertRedirect(route('marketing.leads.index'))
            ->assertSessionHas('marketing_status_sucesso');

        $this->assertSame('em_atendimento', $lead->fresh()->status);
    }

    public function test_todos_os_estados_configurados_sao_aceitos(): void
    {
        $lead = $this->lead();
        $this->actingAs($this->usuario(User::PERFIL_ADMIN));

        foreach (array_keys(MarketingLead::STATUS_LABELS) as $status) {
            $this->patch(route('marketing.leads.status.update', $lead), [
                'status' => $status,
            ])->assertRedirect(route('marketing.leads.index'));
            $this->assertSame($status, $lead->fresh()->status);
        }
    }

    public function test_status_invalido_e_ausente_sao_rejeitados(): void
    {
        $lead = $this->lead();
        $this->actingAs($this->usuario(User::PERFIL_ADMIN));

        foreach ([['status' => 'aprovado_inexistente'], []] as $dados) {
            $this->patch(route('marketing.leads.status.update', $lead), $dados)
                ->assertSessionHasErrors('status');
            $this->assertSame('novo', $lead->fresh()->status);
        }
    }

    public function test_tentativa_de_alterar_outros_campos_e_ignorada(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->usuario(User::PERFIL_ADMIN))
            ->patch(route('marketing.leads.status.update', $lead), [
                'status' => 'aguardando_cliente',
                'nome' => 'Nome adulterado',
                'peca' => 'Produto adulterado',
                'whatsapp' => '00000000000',
                'utm_source' => 'alterada',
            ])->assertRedirect(route('marketing.leads.index'));

        $lead->refresh();
        $this->assertSame('aguardando_cliente', $lead->status);
        $this->assertSame('Solicitante confidencial', $lead->nome);
        $this->assertSame('Filtro de óleo', $lead->peca);
        $this->assertSame('75999990000', $lead->whatsapp);
        $this->assertSame('google', $lead->utm_source);
    }

    public function test_lead_inexistente_retorna_404(): void
    {
        $this->actingAs($this->usuario(User::PERFIL_ADMIN))
            ->patch('/marketing/leads/999999999/status', [
                'status' => 'concluido',
            ])->assertNotFound();
    }

    public function test_painel_mostra_controles_de_status_so_para_admin(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->usuario(User::PERFIL_ADMIN))
            ->get(route('marketing.leads.index'))
            ->assertOk()
            ->assertSee(route('marketing.leads.status.update', $lead), false)
            ->assertSee('name="status"', false)
            ->assertSee('Em atendimento')
            ->assertSee('Salvar');
    }
}
