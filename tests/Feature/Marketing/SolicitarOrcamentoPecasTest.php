<?php

namespace Tests\Feature\Marketing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SolicitarOrcamentoPecasTest extends TestCase
{
    use RefreshDatabase;

    private function dados(array $extra = []): array
    {
        return array_merge([
            'nome' => 'Cliente de Teste',
            'whatsapp' => '(75) 99999-0000',
            'peca' => 'Pivô de suspensão',
            'veiculo' => 'Chevrolet Onix',
            'ano' => '2018',
            'motor' => '1.4',
            'aceite_privacidade' => '1',
            'utm_source' => 'google',
            'utm_campaign' => 'pecas_teste',
            'gclid' => 'gclid_teste',
        ], $extra);
    }

    public function test_formulario_e_publico_e_visivel(): void
    {
        $this->get('/pecas/orcamento?utm_source=google')
            ->assertOk()
            ->assertSee('name="peca"', false)
            ->assertSee('name="whatsapp"', false)
            ->assertSee('name="aceite_privacidade"', false)
            ->assertSee('name="utm_source" value="google"', false);
    }

    public function test_visitante_registra_solicitacao_e_recebe_protocolo(): void
    {
        $this->post(route('marketing.pecas.store'), $this->dados())
            ->assertRedirect(route('marketing.pecas.orcamento'))
            ->assertSessionHas('marketing_protocolo')
            ->assertSessionHas('marketing_whatsapp');

        $this->assertDatabaseCount('marketing_leads', 1);
        $this->assertDatabaseHas('marketing_leads', [
            'nome' => 'Cliente de Teste',
            'whatsapp' => '75999990000',
            'peca' => 'Pivô de suspensão',
            'veiculo' => 'Chevrolet Onix',
            'utm_source' => 'google',
            'utm_campaign' => 'pecas_teste',
            'gclid' => 'gclid_teste',
            'status' => 'novo',
        ]);
        $protocolo = DB::table('marketing_leads')->value('protocolo');
        $this->assertMatchesRegularExpression('/^MKT-[A-Z0-9]{12}$/', $protocolo);
        $this->get(route('marketing.pecas.orcamento'))
            ->assertOk()
            ->assertSee('Solicitação registrada')
            ->assertSee($protocolo);
    }

    public function test_campos_obrigatorios_e_aceite_sao_validados(): void
    {
        $this->post(route('marketing.pecas.store'), $this->dados([
            'peca' => '', 'aceite_privacidade' => null,
        ]))->assertSessionHasErrors(['peca', 'aceite_privacidade']);

        $this->assertDatabaseCount('marketing_leads', 0);
    }

    public function test_honeypot_e_whatsapp_invalido_sao_rejeitados(): void
    {
        $this->post(route('marketing.pecas.store'), $this->dados([
            'website' => 'spam.example', 'whatsapp' => '123',
        ]))->assertSessionHasErrors(['website', 'whatsapp']);

        $this->assertDatabaseCount('marketing_leads', 0);
    }

    public function test_solicitacao_nao_altera_cadastros_operacionais(): void
    {
        $this->post(route('marketing.pecas.store'), $this->dados());
        $this->assertDatabaseCount('produtos', 0);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('users', 0);
    }
}
