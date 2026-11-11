<?php

namespace Tests\Feature\Marketing;

use App\Models\MarketingLead;
use App\Models\MarketingLeadItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FundacaoOrcamentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_campos_de_integracao_sao_opcionais_e_preservam_lead_existente(): void
    {
        foreach (['cliente_id', 'nota_id', 'cidade', 'uf', 'ip_criptografado'] as $campo) {
            $this->assertTrue(Schema::hasColumn('marketing_leads', $campo));
        }

        $lead = $this->novoLead();
        $this->assertNull($lead->cliente_id);
        $this->assertNull($lead->nota_id);
        $this->assertNull($lead->ip_criptografado);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_itens_de_solicitacao_nao_geram_estoque_venda_ou_cobranca(): void
    {
        $lead = $this->novoLead();

        MarketingLeadItem::create([
            'marketing_lead_id' => $lead->id,
            'produto_id' => null,
            'descricao' => 'Filtro de óleo sem referência confirmada',
            'quantidade' => 2,
            'ordem' => 1,
        ]);

        $this->assertSame(1, $lead->itens()->count());
        $this->assertSame(2, $lead->itens()->first()->quantidade);
        $this->assertDatabaseCount('produtos', 0);
        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('notas_itens', 0);
        $this->assertDatabaseCount('contas_receber', 0);
    }

    private function novoLead(): MarketingLead
    {
        return MarketingLead::create([
            'protocolo' => 'MKT-TESTE005E01',
            'nome' => 'Cliente Homologacao',
            'whatsapp' => '75999990000',
            'peca' => 'Filtro de óleo',
            'consentido_em' => now(),
            'status' => 'novo',
        ]);
    }
}
