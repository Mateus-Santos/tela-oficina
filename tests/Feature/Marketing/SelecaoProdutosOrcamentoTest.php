<?php

namespace Tests\Feature\Marketing;

use App\Models\Marca;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelecaoProdutosOrcamentoTest extends TestCase
{
    use RefreshDatabase;

    private function dados(array $alteracoes = []): array
    {
        return array_merge([
            'nome' => 'Cliente Marketing',
            'whatsapp' => '(75) 99999-0000',
            'aceite_privacidade' => '1',
            'utm_source' => 'google',
            'cidade' => 'Feira de Santana',
            'uf' => 'ba',
        ], $alteracoes);
    }

    private function produto(string $nome, string $estoque = '2.000', bool $ativo = true, ?string $referencia = null): Produto
    {
        $marca = Marca::firstOrCreate(['nome' => 'MARCA PUBLICA'], ['ativo' => true]);

        return Produto::create([
            'nome' => $nome,
            'descricao' => 'DESCRICAO INTERNA RESTRITA',
            'codigo_fabricante' => $referencia ?? 'REF-TESTE-'.$nome,
            'quantidade' => $estoque,
            'estoque_minimo' => '0.000',
            'preco_uni' => '159.99',
            'status' => $ativo,
            'marca_id' => $marca->id,
        ]);
    }

    public function test_busca_publica_retorna_somente_produtos_e_campos_permitidos(): void
    {
        $visivel = $this->produto('FILTRO DISPONIVEL');
        $this->produto('FILTRO ZERADO', '0.000');
        $this->produto('FILTRO INATIVO', '5.000', false);

        $this->getJson(route('marketing.pecas.produtos', ['busca' => 'FILTRO']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visivel->id)
            ->assertJsonPath('data.0.nome', 'FILTRO DISPONIVEL')
            ->assertJsonPath('data.0.marca', 'MARCA PUBLICA')
            ->assertJsonPath('data.0.referencia', 'REF-TESTE-FILTRO DISPONIVEL')
            ->assertJsonMissingPath('data.0.codigo')
            ->assertJsonMissingPath('data.0.codigo_fabricante')
            ->assertJsonMissingPath('data.0.quantidade')
            ->assertDontSee('159.99')
            ->assertDontSee('DESCRICAO INTERNA RESTRITA')
            ->assertDontSee('estoque_atual');
    }

    public function test_mesmo_nome_e_marca_sao_diferenciados_por_referencia_e_pesquisaveis(): void
    {
        $primeiro = $this->produto('FILTRO OLEO BLINDADO', '2.000', true, 'W712/95');
        $segundo = $this->produto('FILTRO OLEO BLINDADO', '2.000', true, 'W719/5');

        $this->getJson(route('marketing.pecas.produtos', ['busca' => 'FILTRO OLEO']))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $primeiro->id)
            ->assertJsonPath('data.0.referencia', 'W712/95')
            ->assertJsonPath('data.1.id', $segundo->id)
            ->assertJsonPath('data.1.referencia', 'W719/5');

        $this->getJson(route('marketing.pecas.produtos', ['busca' => 'W719/5']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $segundo->id)
            ->assertJsonPath('data.0.referencia', 'W719/5');
    }

    public function test_interface_exibe_mascara_quantidade_e_referencia(): void
    {
        $this->get(route('marketing.pecas.orcamento'))
            ->assertOk()
            ->assertSee('id="mkt-whatsapp"', false)
            ->assertSee('formatarTelefone', false)
            ->assertSee('Diminuir quantidade de', false)
            ->assertSee('Aumentar quantidade de', false)
            ->assertSee('Ref.:', false);
    }

    public function test_selecao_gera_itens_sem_estoque_nota_ou_cobranca(): void
    {
        $filtro = $this->produto('FILTRO DE OLEO');
        $pivo = $this->produto('PIVO SUSPENSAO');

        $this->post(route('marketing.pecas.store'), $this->dados([
            'itens' => [
                ['produto_id' => $filtro->id, 'quantidade' => 2],
                ['produto_id' => $pivo->id, 'quantidade' => 1],
            ],
        ]))->assertRedirect(route('marketing.pecas.orcamento'))
            ->assertSessionHas('marketing_protocolo');

        $this->assertDatabaseHas('marketing_leads', [
            'peca' => 'FILTRO DE OLEO',
            'cidade' => 'Feira de Santana',
            'uf' => 'BA',
            'utm_source' => 'google',
        ]);
        $linkWhatsApp = session('marketing_whatsapp');
        $this->assertIsString($linkWhatsApp);
        $this->assertStringContainsString(
            rawurlencode('Ref. fabricante: REF-TESTE-FILTRO DE OLEO'),
            $linkWhatsApp
        );
        $this->assertDatabaseCount('marketing_lead_itens', 2);
        $this->assertDatabaseHas('marketing_lead_itens', [
            'produto_id' => $filtro->id,
            'descricao' => 'FILTRO DE OLEO',
            'quantidade' => 2,
            'ordem' => 1,
        ]);
        $this->assertSame('2.000', $filtro->fresh()->quantidade);
        $this->assertDatabaseCount('notas', 0);
        $this->assertDatabaseCount('notas_itens', 0);
        $this->assertDatabaseCount('contas_receber', 0);
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_texto_livre_continua_aceito_com_item_sem_produto(): void
    {
        $this->post(route('marketing.pecas.store'), $this->dados([
            'peca' => 'Bucha da suspensão',
        ]))->assertRedirect(route('marketing.pecas.orcamento'));

        $this->assertDatabaseHas('marketing_lead_itens', [
            'produto_id' => null,
            'descricao' => 'Bucha da suspensão',
            'quantidade' => 1,
        ]);
    }

    public function test_valida_identidade_disponibilidade_e_quantidades(): void
    {
        $zerado = $this->produto('FILTRO ZERADO', '0.000');
        $ativo = $this->produto('FILTRO ATIVO');

        foreach ([
            [['produto_id' => $zerado->id, 'quantidade' => 1]],
            [['produto_id' => 999999, 'quantidade' => 1]],
            [['produto_id' => $ativo->id, 'quantidade' => 0]],
            [['produto_id' => $ativo->id, 'quantidade' => 100]],
            [['produto_id' => $ativo->id, 'quantidade' => 1],
             ['produto_id' => $ativo->id, 'quantidade' => 2]],
        ] as $itens) {
            $this->post(route('marketing.pecas.store'), $this->dados(['itens' => $itens]))
                ->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('marketing_leads', 0);
        $this->assertDatabaseCount('marketing_lead_itens', 0);
    }

    public function test_formulario_tem_busca_e_rejeita_envio_vazio(): void
    {
        $this->get(route('marketing.pecas.orcamento'))
            ->assertOk()
            ->assertSee('id="mkt-busca-produto"', false);

        $this->post(route('marketing.pecas.store'), $this->dados())
            ->assertSessionHasErrors('peca');

        $this->getJson(route('marketing.pecas.produtos', ['busca' => 'A']))
            ->assertUnprocessable();
        $this->assertDatabaseCount('marketing_leads', 0);
    }
}
