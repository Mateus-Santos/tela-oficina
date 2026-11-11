<?php

namespace Tests\Feature\Marketing;

use App\Models\MarketingLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PainelLeadsTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $perfil): User
    {
        return User::factory()->create([
            'perfil' => $perfil,
            'status' => 1,
        ]);
    }

    private function lead(string $protocolo, string $peca, string $status = 'novo'): MarketingLead
    {
        return MarketingLead::create([
            'protocolo' => $protocolo,
            'nome' => 'Solicitante do Teste',
            'whatsapp' => '75999990000',
            'peca' => $peca,
            'status' => $status,
            'consentido_em' => now(),
            'utm_source' => 'google',
            'utm_campaign' => 'teste_automacao',
        ]);
    }

    public function test_visitante_nao_acessa_dados_dos_leads(): void
    {
        $this->lead('MKT-TESTE000001', 'FILTRO SIGILOSO');

        $this->get(route('marketing.leads.index'))
            ->assertRedirect(route('erro-autenticacao'));
    }

    public function test_cliente_e_colaborador_nao_acessam_leads(): void
    {
        $this->lead('MKT-TESTE000002', 'FILTRO PRIVADO');

        foreach ([User::PERFIL_CLIENTE, User::PERFIL_COLABORADOR] as $perfil) {
            $this->actingAs($this->usuario($perfil))
                ->get(route('marketing.leads.index'))
                ->assertForbidden();
        }
    }

    public function test_admin_visualiza_lead_e_indicadores(): void
    {
        $this->lead('MKT-TESTE000003', 'FILTRO DE OLEO INTERNO');

        $this->actingAs($this->usuario(User::PERFIL_ADMIN))
            ->get(route('marketing.leads.index'))
            ->assertOk()
            ->assertSee('Orçamentos de peças')
            ->assertSee('MKT-TESTE000003')
            ->assertSee('FILTRO DE OLEO INTERNO')
            ->assertSee('Solicitante do Teste')
            ->assertSee('teste_automacao');
    }

    public function test_filtros_respeitam_busca_e_status(): void
    {
        $this->lead('MKT-TESTE000004', 'FILTRO ESPECIAL', 'novo');
        $this->lead('MKT-TESTE000005', 'AMORTECEDOR', 'concluido');

        $this->actingAs($this->usuario(User::PERFIL_ADMIN))
            ->get(route('marketing.leads.index', ['status' => 'novo', 'busca' => 'FILTRO']))
            ->assertOk()
            ->assertSee('FILTRO ESPECIAL')
            ->assertDontSee('AMORTECEDOR');
    }

    public function test_paginacao_limita_quinze_leads_por_pagina(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            $this->lead(sprintf('MKT-PAGINACAO%04d', $i), sprintf('PECA MARCADA %02d', $i));
        }

        $this->actingAs($this->usuario(User::PERFIL_ADMIN))
            ->get(route('marketing.leads.index'))
            ->assertOk()
            ->assertSee('PECA MARCADA 16')
            ->assertDontSee('PECA MARCADA 01');
    }
}
