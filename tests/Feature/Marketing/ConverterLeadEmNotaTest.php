<?php

namespace Tests\Feature\Marketing;

use App\Models\Cliente;
use App\Models\Etapa;
use App\Models\MarketingLead;
use App\Models\Marca;
use App\Models\Pessoa;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConverterLeadEmNotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('marketing.conversao_nota_habilitada', true);
    }

    private function admin(string $perfil = User::PERFIL_ADMIN): User
    {
        return User::factory()->create(['perfil' => $perfil, 'status' => 1]);
    }

    private function leadComProduto(): array
    {
        $marca = Marca::firstOrCreate(['nome' => 'MARCA MARKETING'], ['ativo' => true]);
        $produto = Produto::create([
            'nome' => 'FILTRO PARA ORCAMENTO',
            'descricao' => 'Produto de teste para conversao de orçamento.',
            'codigo_fabricante' => 'MKT-TESTE-FILTRO',
            'quantidade' => '4.000',
            'estoque_minimo' => '0.000',
            'preco_uni' => '12.00',
            'marca_id' => $marca->id,
            'status' => true,
        ]);
        $lead = MarketingLead::create([
            'protocolo' => 'MKT-CONVERSAO0001',
            'nome' => 'Interessado',
            'whatsapp' => '75999990000',
            'peca' => 'Filtro',
            'consentido_em' => now(),
            'status' => 'novo',
        ]);
        $item = $lead->itens()->create([
            'produto_id' => $produto->id,
            'descricao' => $produto->nome,
            'quantidade' => 2,
            'ordem' => 1,
        ]);
        return [$lead, $item, $produto];
    }

    private function cliente(): Cliente
    {
        $pessoa = Pessoa::create(['nome' => 'Cliente confirmado no balcão']);
        return Cliente::create(['pessoa_id' => $pessoa->id]);
    }

    private function dados(Cliente $cliente, int $itemId): array
    {
        return [
            'cliente_id' => $cliente->id,
            'precos' => [(string) $itemId => '45.90'],
            'confirmar_revisao' => '1',
        ];
    }

    public function test_conversao_exige_administrador(): void
    {
        [$lead, $item] = $this->leadComProduto();
        $cliente = $this->cliente();

        $this->post(route('marketing.leads.converter-nota', $lead), $this->dados($cliente, $item->id))
            ->assertRedirect(route('erro-autenticacao'));

        foreach ([User::PERFIL_CLIENTE, User::PERFIL_COLABORADOR] as $perfil) {
            $this->actingAs($this->admin($perfil))
                ->post(route('marketing.leads.converter-nota', $lead), $this->dados($cliente, $item->id))
                ->assertForbidden();
        }
        $this->assertDatabaseCount('notas', 0);
        $this->assertNull($lead->fresh()->nota_id);
    }

    public function test_admin_gera_nota_aberta_sem_estoque_nem_cobranca(): void
    {
        [$lead, $item, $produto] = $this->leadComProduto();
        $cliente = $this->cliente();
        $estoque = $produto->quantidade;

        $this->actingAs($this->admin())
            ->post(route('marketing.leads.converter-nota', $lead), $this->dados($cliente, $item->id))
            ->assertRedirect(route('marketing.leads.show', $lead))
            ->assertSessionHas('marketing_nota_sucesso');

        $lead->refresh();
        $this->assertNotNull($lead->nota_id);
        $this->assertSame($cliente->id, (int) $lead->cliente_id);
        $this->assertDatabaseHas('notas', [
            'id' => $lead->nota_id,
            'cliente_id' => $cliente->id,
            'tipo' => 'Venda',
            'status' => 'Aberto',
            'total' => '91.80',
        ]);
        $this->assertDatabaseHas('notas_itens', [
            'nota_id' => $lead->nota_id,
            'itemable_id' => $produto->id,
            'itemable_type' => Produto::class,
            'quantidade' => 2,
            'valor_unitario' => '45.90',
        ]);
        $this->assertSame($estoque, $produto->fresh()->quantidade);
        $this->assertDatabaseCount('notas', 1);
        $this->assertDatabaseCount('notas_itens', 1);
        $this->assertDatabaseCount('contas_receber', 0);
    }

    public function test_repeticao_nao_gera_segunda_nota(): void
    {
        [$lead, $item] = $this->leadComProduto();
        $cliente = $this->cliente();
        $this->actingAs($this->admin());

        $this->post(route('marketing.leads.converter-nota', $lead), $this->dados($cliente, $item->id))
            ->assertRedirect();
        $this->post(route('marketing.leads.converter-nota', $lead), $this->dados($cliente, $item->id))
            ->assertSessionHasErrors('conversao');
        $this->assertDatabaseCount('notas', 1);
        $this->assertDatabaseCount('notas_itens', 1);
    }

    public function test_texto_livre_ou_preco_invalido_nao_gera_nota(): void
    {
        [$lead, $item] = $this->leadComProduto();
        $cliente = $this->cliente();
        $this->actingAs($this->admin());

        $this->post(route('marketing.leads.converter-nota', $lead), [
            'cliente_id' => $cliente->id,
            'precos' => [$item->id => '0'],
            'confirmar_revisao' => '1',
        ])->assertSessionHasErrors('precos.'.$item->id);

        $item->update(['produto_id' => null]);
        $this->post(route('marketing.leads.converter-nota', $lead), $this->dados($cliente, $item->id))
            ->assertSessionHasErrors('conversao');

        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('contas_receber', 0);
    }

    public function test_conversao_desabilitada_nao_escreve(): void
    {
        [$lead, $item] = $this->leadComProduto();
        $cliente = $this->cliente();
        config()->set('marketing.conversao_nota_habilitada', false);

        $this->actingAs($this->admin())
            ->post(route('marketing.leads.converter-nota', $lead), $this->dados($cliente, $item->id))
            ->assertNotFound();
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_detalhes_protegidos_exibem_itens_para_admin(): void
    {
        [$lead] = $this->leadComProduto();
        $this->get(route('marketing.leads.show', $lead))
            ->assertRedirect(route('erro-autenticacao'));

        $this->actingAs($this->admin(User::PERFIL_CLIENTE))
            ->get(route('marketing.leads.show', $lead))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('marketing.leads.show', $lead))
            ->assertOk()
            ->assertSee($lead->protocolo)
            ->assertSee('FILTRO PARA ORCAMENTO');
    }
}
