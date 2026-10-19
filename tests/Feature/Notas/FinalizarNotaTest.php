<?php

namespace Tests\Feature\Notas;

use App\Actions\Notas\AlterarEtapaNota;
use App\Actions\Notas\CancelarNota;
use App\Actions\Notas\CriarNota;
use App\Actions\Notas\FinalizarNota;
use App\Actions\OrdemServico\AlterarEtapaOrdemServico;
use App\Actions\OrdemServico\CriarOrdemServico;
use App\Actions\OrdemServico\FinalizarOrdemServico;
use App\Models\CategoriaFinanceira;
use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\Etapa;
use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\OrdemServico;
use App\Models\Produto;
use App\Models\SetorServico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class FinalizarNotaTest extends TestCase
{
    use RefreshDatabase;

    private function criarCliente(): Cliente
    {
        $pessoaId = DB::table('pessoas')->insertGetId([
            'nome' => 'Cliente Teste',
            'cpf' => '12345678901',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clienteId = DB::table('clientes')->insertGetId([
            'pessoa_id' => $pessoaId,
            'pontos' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Cliente::findOrFail($clienteId);
    }

    private function criarCategoriaVendasEServicos(): CategoriaFinanceira
    {
        return CategoriaFinanceira::updateOrCreate(
            [
                'nome' => 'VENDAS E SERVIÇOS',
                'tipo' => 'entrada',
            ],
            [
                'ativo' => true,
            ]
        );
    }

    private function criarFormaPagamento(): int
    {
        return DB::table('formas_pagamento')->insertGetId([
            'nome' => 'PIX Teste '.uniqid(),
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function criarProduto(
        int $quantidade = 10,
        float $preco = 100
    ): Produto {
        $produtoId = DB::table('produtos')->insertGetId([
            'nome' => 'Produto Teste '.uniqid(),
            'descricao' => 'Produto utilizado nos testes.',
            'quantidade' => $quantidade,
            'estoque_minimo' => 1,
            'preco_uni' => $preco,
            'codigo_fabricante' => 'FAB-'.uniqid(),
            'codigo_barras' => null,
            'status' => true,
            'fornecedor_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Produto::findOrFail($produtoId);
    }

    private function criarNota(
        Cliente $cliente,
        float $total = 100
    ): Nota {
        return Nota::create([
            'cliente_id' => $cliente->id,
            'veiculo_cliente_id' => null,
            'tipo' => 'Venda',
            'status' => 'Aberto',
            'subtotal' => $total,
            'desconto' => 0,
            'total' => $total,
            'observacoes' => null,
            'km' => null,
            'km_proxima_troca_oleo' => null,
        ]);
    }

    private function criarItemProduto(
        Nota $nota,
        Produto $produto,
        int $quantidade = 1,
        float $valorUnitario = 100
    ): NotasItem {
        return NotasItem::create([
            'nota_id' => $nota->id,
            'itemable_type' => $produto->getMorphClass(),
            'itemable_id' => $produto->id,
            'descricao' => $produto->nome,
            'quantidade' => $quantidade,
            'valor_unitario' => $valorUnitario,
            'desconto' => 0,
            'valor_total' => $quantidade * $valorUnitario,
            'garantia_meses' => null,
            'garantia_km' => null,
        ]);
    }

    private function criarFinalizador(): FinalizarNota
    {
        return app(FinalizarNota::class);
    }

    private function finalizarNota(
        Nota $nota,
        array $dadosFinanceiros = []
    ): Nota {
        $categoria = $this->criarCategoriaVendasEServicos();

        $dadosPadrao = [
            'categoria_financeira_id' => $categoria->id,
            'parcelas' => [
                [
                    'numero' => 1,
                    'valor' => (float) $nota->total,
                    'data_vencimento' => now()
                        ->addDays(30)
                        ->toDateString(),
                ],
            ],
        ];

        return $this->criarFinalizador()->execute(
            $nota,
            array_replace_recursive(
                $dadosPadrao,
                $dadosFinanceiros
            )
        );
    }

    private function criarVeiculoCliente(
        Cliente $cliente
    ): int {
        $agora = now();

        $montadoraId = DB::table(
            'montadoras'
        )->insertGetId([
            'nome' => 'Montadora Workflow '.uniqid(),
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        $veiculoId = DB::table(
            'veiculos'
        )->insertGetId([
            'nome' => 'Veículo Workflow',
            'montadora_id' => $montadoraId,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        $veiculoClienteId = DB::table(
            'veiculos_clientes'
        )->insertGetId([
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculoId,
            'placa' => 'TES1T23',
            'ano' => 2024,
            'cor' => 'Branco',
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        DB::table(
            'cliente_veiculo_cliente'
        )->insert([
            'cliente_id' => $cliente->id,
            'veiculo_cliente_id' => $veiculoClienteId,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        return $veiculoClienteId;
    }

    private function criarSetorServico(): SetorServico
    {
        return SetorServico::create([
            'setor' => 'Mecânica Workflow '.uniqid(),
            'nivel' => 1,
        ]);
    }

    private function criarOrdemServicoWorkflow(
        Cliente $cliente,
        int $veiculoClienteId,
        SetorServico $setorServico,
        float $valor = 300
    ): OrdemServico {
        return app(
            CriarOrdemServico::class
        )->execute([
            'cliente_id' => $cliente->id,
            'veiculo_cliente_id' => $veiculoClienteId,
            'setor_servico_id' => $setorServico->id,
            'descricao' => 'Serviço de workflow',
            'valor' => number_format(
                $valor,
                2,
                ',',
                '.'
            ),
        ]);
    }

    private function criarNotaComOrdemServico(
        Cliente $cliente,
        int $veiculoClienteId,
        OrdemServico $ordemServico
    ): Nota {
        return app(
            CriarNota::class
        )->execute([
            'cliente_id' => $cliente->id,
            'veiculo_cliente_id' => $veiculoClienteId,
            'km' => 50000,
            'km_proxima_troca_oleo' => 60000,
            'itens' => [
                [
                    'itemable_type' => OrdemServico::class,
                    'itemable_id' => $ordemServico->id,
                    'descricao' => $ordemServico->descricao,
                    'quantidade' => 1,
                    'valor_unitario' => (float) $ordemServico->valor,
                    'desconto' => 0,
                    'garantia_dias' => 90,
                ],
            ],
        ]);
    }

    private function etapa(
        string $slug
    ): Etapa {
        return Etapa::query()
            ->where(
                'slug',
                $slug
            )
            ->firstOrFail();
    }

    public function test_finalizar_nota_baixa_estoque_do_produto(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota($cliente, 200);

        $this->criarItemProduto(
            $nota,
            $produto,
            2,
            100
        );

        $this->finalizarNota($nota);

        $produto->refresh();

        $this->assertSame(
            8,
            $produto->quantidade
        );

        $this->assertDatabaseHas(
            'movimentacao_estoques',
            [
                'produto_id' => $produto->id,
                'tipo' => 'saida',
                'quantidade' => 2,
                'origem_type' => NotasItem::class,
            ]
        );
    }

    public function test_item_que_nao_e_produto_nao_movimenta_estoque(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $nota = $this->criarNota(
            $cliente,
            300
        );

        $montadoraId = DB::table(
            'montadoras'
        )->insertGetId([
            'nome' => 'Montadora Teste '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $veiculoId = DB::table(
            'veiculos'
        )->insertGetId([
            'nome' => 'Veículo Teste',
            'montadora_id' => $montadoraId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pessoaId = DB::table(
            'pessoas'
        )->insertGetId([
            'nome' => 'Cliente OS Teste',
            'cpf' => '98765432109',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clienteOsId = DB::table(
            'clientes'
        )->insertGetId([
            'pessoa_id' => $pessoaId,
            'pontos' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $veiculoClienteId = DB::table(
            'veiculos_clientes'
        )->insertGetId([
            'cliente_id' => $clienteOsId,
            'veiculo_id' => $veiculoId,
            'placa' => 'ABC1234',
            'ano' => 2020,
            'cor' => 'Preto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $setorServicoId = DB::table(
            'setor_servicos'
        )->insertGetId([
            'setor' => 'Mecânica',
            'nivel' => 'Normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ordemServicoId = DB::table(
            'ordem_servicos'
        )->insertGetId([
            'veiculo_cliente_id' => $veiculoClienteId,
            'setor_servico_id' => $setorServicoId,
            'descricao' => 'Serviço teste',
            'valor' => 300,
            'status' => 'finalizada',
            'data_abertura' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $item = NotasItem::create([
            'nota_id' => $nota->id,
            'itemable_type' => OrdemServico::class,
            'itemable_id' => $ordemServicoId,
            'descricao' => 'Serviço teste',
            'quantidade' => 1,
            'valor_unitario' => 300,
            'desconto' => 0,
            'valor_total' => 300,
            'garantia_meses' => null,
            'garantia_km' => null,
        ]);

        $this->finalizarNota(
            $nota
        );

        $this->assertDatabaseMissing(
            'movimentacao_estoques',
            [
                'origem_type' => NotasItem::class,
                'origem_id' => $item->id,
                'tipo' => 'saida',
            ]
        );

        $this->assertDatabaseHas(
            'contas_receber',
            [
                'nota_id' => $nota->id,
                'cliente_id' => $cliente->id,
                'valor_original' => 300,
                'status' => 'aberta',
            ]
        );
    }

    public function test_finalizacao_cria_apenas_uma_saida_por_item(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            100
        );

        $item = $this->criarItemProduto(
            $nota,
            $produto,
            1,
            100
        );

        $this->finalizarNota(
            $nota
        );

        $this->assertDatabaseCount(
            'movimentacao_estoques',
            1
        );

        $this->assertDatabaseHas(
            'movimentacao_estoques',
            [
                'origem_type' => NotasItem::class,
                'origem_id' => $item->id,
                'tipo' => 'saida',
            ]
        );
    }

    public function test_nao_permite_finalizar_nota_que_nao_esteja_aberta(): void
    {
        $cliente = $this->criarCliente();

        $nota = $this->criarNota(
            $cliente,
            100
        );

        $nota->update([
            'status' => 'Finalizado',
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->finalizarNota(
            $nota
        );
    }

    public function test_nao_permite_finalizar_nota_sem_itens(): void
    {
        $cliente = $this->criarCliente();

        $nota = $this->criarNota(
            $cliente,
            100
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->finalizarNota(
            $nota
        );
    }

    public function test_nao_permite_saida_maior_que_o_estoque(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(1);

        $nota = $this->criarNota(
            $cliente,
            200
        );

        $this->criarItemProduto(
            $nota,
            $produto,
            2,
            100
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        try {
            $this->finalizarNota(
                $nota
            );
        } finally {
            $this->assertDatabaseCount(
                'movimentacao_estoques',
                0
            );

            $produto->refresh();

            $this->assertSame(
                1,
                $produto->quantidade
            );

            $this->assertDatabaseCount(
                'contas_receber',
                0
            );
        }
    }

    public function test_falha_em_um_produto_faz_rollback_das_baixas_anteriores(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto1 = $this->criarProduto(10);

        $produto2 = $this->criarProduto(1);

        $nota = $this->criarNota(
            $cliente,
            300
        );

        $this->criarItemProduto(
            $nota,
            $produto1,
            2,
            100
        );

        $this->criarItemProduto(
            $nota,
            $produto2,
            2,
            50
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        try {
            $this->finalizarNota(
                $nota
            );
        } finally {
            $produto1->refresh();

            $this->assertSame(
                10,
                $produto1->quantidade
            );

            $produto2->refresh();

            $this->assertSame(
                1,
                $produto2->quantidade
            );

            $this->assertDatabaseCount(
                'movimentacao_estoques',
                0
            );

            $this->assertDatabaseCount(
                'contas_receber',
                0
            );

            $nota->refresh();

            $this->assertSame(
                'Aberto',
                $nota->status
            );
        }
    }

    public function test_nao_permite_item_com_referencia_invalida(): void
    {
        $cliente = $this->criarCliente();

        $nota = $this->criarNota(
            $cliente,
            100
        );

        NotasItem::create([
            'nota_id' => $nota->id,
            'itemable_type' => Produto::class,
            'itemable_id' => 999999,
            'descricao' => 'Item inválido',
            'quantidade' => 1,
            'valor_unitario' => 100,
            'desconto' => 0,
            'valor_total' => 100,
            'garantia_meses' => null,
            'garantia_km' => null,
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->finalizarNota(
            $nota
        );
    }

    public function test_nao_permite_finalizacao_quando_item_ja_possui_baixa(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            100
        );

        $item = $this->criarItemProduto(
            $nota,
            $produto,
            1,
            100
        );

        DB::table(
            'movimentacao_estoques'
        )->insert([
            'produto_id' => $produto->id,
            'tipo' => 'saida',
            'quantidade' => 1,
            'saldo_anterior' => 10,
            'saldo_posterior' => 9,
            'valor_unitario' => 100,
            'origem_type' => $item->getMorphClass(),
            'origem_id' => $item->id,
            'usuario_id' => null,
            'observacoes' => 'Baixa pré-existente para teste.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->finalizarNota(
            $nota
        );
    }

    public function test_finalizacao_cria_conta_a_receber(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            250
        );

        $this->criarItemProduto(
            $nota,
            $produto,
            1,
            250
        );

        $this->finalizarNota(
            $nota
        );

        $this->assertDatabaseHas(
            'contas_receber',
            [
                'nota_id' => $nota->id,
                'cliente_id' => $cliente->id,
                'valor_original' => 250,
                'desconto' => 0,
                'juros' => 0,
                'multa' => 0,
                'status' => 'aberta',
            ]
        );

        $conta = ContaReceber::where(
            'nota_id',
            $nota->id
        )->firstOrFail();

        $categoria = CategoriaFinanceira::findOrFail(
            $conta->categoria_financeira_id
        );

        $this->assertSame(
            'VENDAS E SERVIÇOS',
            $categoria->nome
        );

        $this->assertSame(
            'entrada',
            $categoria->tipo
        );

        $this->assertTrue(
            $categoria->ativo
        );
    }

    public function test_conta_a_receber_usa_o_total_da_nota(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            500
        );

        $nota->update([
            'subtotal' => 600,
            'desconto' => 100,
            'total' => 500,
        ]);

        $this->criarItemProduto(
            $nota,
            $produto,
            1,
            600
        );

        $this->finalizarNota(
            $nota
        );

        $this->assertDatabaseHas(
            'contas_receber',
            [
                'nota_id' => $nota->id,
                'valor_original' => 500,
            ]
        );
    }

    public function test_cliente_da_conta_a_receber_e_o_mesmo_da_nota(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            100
        );

        $this->criarItemProduto(
            $nota,
            $produto,
            1,
            100
        );

        $this->finalizarNota(
            $nota
        );

        $conta = ContaReceber::where(
            'nota_id',
            $nota->id
        )->firstOrFail();

        $this->assertSame(
            $nota->cliente_id,
            $conta->cliente_id
        );
    }

    public function test_finalizacao_reutiliza_conta_a_receber_existente_sem_duplicar(): void
    {
        $categoria = $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            100
        );

        $this->criarItemProduto(
            $nota,
            $produto,
            1,
            100
        );

        $conta = ContaReceber::create([
            'cliente_id' => $cliente->id,
            'nota_id' => $nota->id,
            'categoria_financeira_id' => $categoria->id,
            'descricao' => 'Conta existente',
            'valor_original' => 100,
            'desconto' => 0,
            'juros' => 0,
            'multa' => 0,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(30)->toDateString(),
            'status' => 'aberta',
        ]);

        $conta->parcelas()->create([
            'numero' => 1,
            'valor' => 100,
            'data_vencimento' => now()->addDays(30)->toDateString(),
        ]);

        $this->finalizarNota(
            $nota
        );

        $nota->refresh();

        $produto->refresh();

        $this->assertSame(
            'Finalizado',
            $nota->status
        );

        $this->assertSame(
            9,
            $produto->quantidade
        );

        $this->assertDatabaseCount(
            'contas_receber',
            1
        );

        $this->assertDatabaseHas(
            'contas_receber',
            [
                'id' => $conta->id,
                'nota_id' => $nota->id,
                'valor_original' => 100,
                'status' => 'aberta',
            ]
        );
    }

    public function test_falha_na_finalizacao_faz_rollback_da_conta_a_receber(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto1 = $this->criarProduto(10);

        $produto2 = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            200
        );

        $this->criarItemProduto(
            $nota,
            $produto1,
            1,
            100
        );

        $this->criarItemProduto(
            $nota,
            $produto2,
            1,
            100
        );

        $item2 = $nota->itens()
            ->orderByDesc('id')
            ->firstOrFail();

        DB::table('notas_itens')
            ->where(
                'id',
                $item2->id
            )
            ->update([
                'itemable_id' => 999999,
            ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        try {
            $this->finalizarNota(
                $nota
            );
        } finally {
            $this->assertDatabaseCount(
                'contas_receber',
                0
            );

            $this->assertDatabaseCount(
                'movimentacao_estoques',
                0
            );

            $produto1->refresh();

            $this->assertSame(
                10,
                $produto1->quantidade
            );

            $produto2->refresh();

            $this->assertSame(
                10,
                $produto2->quantidade
            );

            $nota->refresh();

            $this->assertSame(
                'Aberto',
                $nota->status
            );
        }
    }

    public function test_permite_finalizar_venda_de_balcao_sem_cliente(): void
    {
        $produto = $this->criarProduto(10);

        $nota = Nota::create([
            'cliente_id' => null,
            'veiculo_cliente_id' => null,
            'tipo' => 'Venda',
            'status' => 'Aberto',
            'subtotal' => 100,
            'desconto' => 0,
            'total' => 100,
            'observacoes' => null,
            'km' => null,
            'km_proxima_troca_oleo' => null,
        ]);

        $this->criarItemProduto(
            $nota,
            $produto,
            1,
            100
        );

        $this->finalizarNota(
            $nota
        );

        $nota->refresh();

        $produto->refresh();

        $this->assertSame(
            'Finalizado',
            $nota->status
        );

        $this->assertSame(
            9,
            $produto->quantidade
        );

        $this->assertDatabaseCount(
            'movimentacao_estoques',
            1
        );

        $this->assertDatabaseCount(
            'contas_receber',
            0
        );
    }

    public function test_cancelamento_reverte_baixa_de_estoque(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            100
        );

        $this->criarItemProduto(
            $nota,
            $produto,
            2,
            50
        );

        $this->finalizarNota(
            $nota
        );

        $nota->refresh();

        $produto->refresh();

        $this->assertSame(
            'Finalizado',
            $nota->status
        );

        $this->assertSame(
            8,
            $produto->quantidade
        );

        app(
            CancelarNota::class
        )->execute(
            $nota
        );

        $produto->refresh();

        $this->assertSame(
            10,
            $produto->quantidade
        );

        $this->assertDatabaseHas(
            'movimentacao_estoques',
            [
                'produto_id' => $produto->id,
                'tipo' => 'entrada',
                'quantidade' => 2,
            ]
        );
    }

    public function test_cancelamento_cancela_conta_a_receber(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            100
        );

        $this->criarItemProduto(
            $nota,
            $produto,
            1,
            100
        );

        $this->finalizarNota(
            $nota
        );

        $conta = ContaReceber::where(
            'nota_id',
            $nota->id
        )->firstOrFail();

        $this->assertSame(
            'aberta',
            $conta->status
        );

        app(
            CancelarNota::class
        )->execute(
            $nota
        );

        $nota->refresh();

        $conta->refresh();

        $produto->refresh();

        $this->assertSame(
            'Cancelado',
            $nota->status
        );

        $this->assertSame(
            'cancelada',
            $conta->status
        );

        $this->assertSame(
            10,
            $produto->quantidade
        );

        $this->assertDatabaseCount(
            'recebimentos',
            0
        );
    }

    public function test_cancelamento_preserva_conta_a_receber_como_historico(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            250
        );

        $this->criarItemProduto(
            $nota,
            $produto,
            1,
            250
        );

        $this->finalizarNota(
            $nota
        );

        $conta = ContaReceber::where(
            'nota_id',
            $nota->id
        )->firstOrFail();

        $contaId = $conta->id;

        app(
            CancelarNota::class
        )->execute(
            $nota
        );

        $this->assertDatabaseHas(
            'contas_receber',
            [
                'id' => $contaId,
                'nota_id' => $nota->id,
                'cliente_id' => $cliente->id,
                'valor_original' => 250,
                'status' => 'cancelada',
            ]
        );
    }

    public function test_nao_permite_cancelar_nota_com_recebimento(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            100
        );

        $this->criarItemProduto(
            $nota,
            $produto,
            1,
            100
        );

        $this->finalizarNota(
            $nota
        );

        $conta = ContaReceber::where(
            'nota_id',
            $nota->id
        )->firstOrFail();

        $formaPagamentoId =
            $this->criarFormaPagamento();

        $parcela = $conta
            ->parcelas()
            ->firstOrFail();

        DB::table(
            'recebimentos'
        )->insert([
            'conta_receber_id' => $conta->id,
            'parcela_conta_receber_id' => $parcela->id,
            'forma_pagamento_id' => $formaPagamentoId,
            'valor' => 50,
            'data_pagamento' => now(),
            'usuario_id' => null,
            'observacoes' => 'Recebimento de teste.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        try {
            app(
                CancelarNota::class
            )->execute(
                $nota
            );
        } finally {
            $nota->refresh();

            $conta->refresh();

            $produto->refresh();

            $this->assertSame(
                'Finalizado',
                $nota->status
            );

            $this->assertSame(
                'aberta',
                $conta->status
            );

            $this->assertSame(
                9,
                $produto->quantidade
            );

            $this->assertDatabaseCount(
                'movimentacao_estoques',
                1
            );

            $this->assertDatabaseCount(
                'recebimentos',
                1
            );
        }
    }

    public function test_cancelamento_funciona_sem_conta_a_receber(): void
    {
        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            100
        );

        $item = $this->criarItemProduto(
            $nota,
            $produto,
            1,
            100
        );

        $nota->update([
            'status' => 'Finalizado',
        ]);

        $produto->update([
            'quantidade' => 9,
        ]);

        DB::table(
            'movimentacao_estoques'
        )->insert([
            'produto_id' => $produto->id,
            'tipo' => 'saida',
            'quantidade' => 1,
            'saldo_anterior' => 10,
            'saldo_posterior' => 9,
            'valor_unitario' => 100,
            'origem_type' => NotasItem::class,
            'origem_id' => $item->id,
            'usuario_id' => null,
            'observacoes' => 'Baixa de teste.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $produto->refresh();

        $this->assertSame(
            9,
            $produto->quantidade
        );

        app(
            CancelarNota::class
        )->execute(
            $nota
        );

        $nota->refresh();

        $produto->refresh();

        $this->assertSame(
            'Cancelado',
            $nota->status
        );

        $this->assertSame(
            10,
            $produto->quantidade
        );

        $this->assertDatabaseCount(
            'contas_receber',
            0
        );

        $this->assertDatabaseCount(
            'movimentacao_estoques',
            2
        );
    }

    public function test_permite_cancelar_nota_aberta(): void
    {
        $cliente = $this->criarCliente();

        $nota = $this->criarNota(
            $cliente,
            100
        );

        app(
            CancelarNota::class
        )->execute(
            $nota
        );

        $nota->refresh();

        $this->assertSame(
            'Cancelado',
            $nota->status
        );

        $this->assertDatabaseCount(
            'movimentacao_estoques',
            0
        );

        $this->assertDatabaseCount(
            'contas_receber',
            0
        );
    }

    public function test_nao_permite_cancelar_nota_duas_vezes(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(10);

        $nota = $this->criarNota(
            $cliente,
            100
        );

        $this->criarItemProduto(
            $nota,
            $produto,
            1,
            100
        );

        $this->finalizarNota(
            $nota
        );

        app(
            CancelarNota::class
        )->execute(
            $nota
        );

        $nota->refresh();

        $this->assertSame(
            'Cancelado',
            $nota->status
        );

        $conta = ContaReceber::where(
            'nota_id',
            $nota->id
        )->firstOrFail();

        $this->assertSame(
            'cancelada',
            $conta->status
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        app(
            CancelarNota::class
        )->execute(
            $nota
        );
    }

    public function test_finalizar_nota_move_nota_para_etapa_final_e_cria_historico(): void
    {
        $cliente = $this->criarCliente();

        $produto = $this->criarProduto(
            10,
            100
        );

        $nota = app(
            CriarNota::class
        )->execute([
            'cliente_id' => $cliente->id,
            'veiculo_cliente_id' => null,
            'itens' => [
                [
                    'itemable_type' => Produto::class,
                    'itemable_id' => $produto->id,
                    'descricao' => $produto->nome,
                    'quantidade' => 1,
                    'valor_unitario' => 100,
                    'desconto' => 0,
                ],
            ],
        ]);

        $diagnostico = $this->etapa(
            'diagnostico'
        );

        $nota = app(
            AlterarEtapaNota::class
        )->execute(
            $nota,
            $diagnostico
        );

        $historicosAntes =
            $nota
                ->historicoEtapas()
                ->count();

        $this->finalizarNota(
            $nota
        );

        $nota->refresh();

        $etapaFinal = Etapa::query()
            ->paraNota()
            ->ativas()
            ->finais()
            ->firstOrFail();

        $this->assertSame(
            'Finalizado',
            $nota->status
        );

        $this->assertSame(
            $etapaFinal->id,
            $nota->etapa_id
        );

        $this->assertSame(
            'Pronto para entrega',
            $nota->etapa->nome
        );

        $this->assertSame(
            $historicosAntes + 1,
            $nota
                ->historicoEtapas()
                ->count()
        );

        $this->assertDatabaseHas(
            'nota_etapa_historicos',
            [
                'nota_id' => $nota->id,
                'etapa_origem_id' => $diagnostico->id,
                'etapa_destino_id' => $etapaFinal->id,
                'etapa_origem_nome' => 'Diagnóstico',
                'etapa_destino_nome' => 'Pronto para entrega',
                'motivo' => 'Movimentação automática pela finalização da Nota.',
            ]
        );
    }

    public function test_finalizar_nota_finaliza_ordem_servico_vinculada_e_move_para_etapa_final(): void
    {
        $cliente = $this->criarCliente();

        $veiculoClienteId =
            $this->criarVeiculoCliente(
                $cliente
            );

        $setorServico =
            $this->criarSetorServico();

        $ordemServico =
            $this->criarOrdemServicoWorkflow(
                $cliente,
                $veiculoClienteId,
                $setorServico,
                300
            );

        $emManutencao = $this->etapa(
            'em-manutencao'
        );

        $ordemServico = app(
            AlterarEtapaOrdemServico::class
        )->execute(
            $ordemServico,
            $emManutencao
        );

        $nota = $this->criarNotaComOrdemServico(
            $cliente,
            $veiculoClienteId,
            $ordemServico
        );

        $diagnostico = $this->etapa(
            'diagnostico'
        );

        $nota = app(
            AlterarEtapaNota::class
        )->execute(
            $nota,
            $diagnostico
        );

        $historicosNotaAntes =
            $nota
                ->historicoEtapas()
                ->count();

        $historicosOsAntes =
            $ordemServico
                ->historicoEtapas()
                ->count();

        $this->finalizarNota(
            $nota
        );

        $nota->refresh();

        $ordemServico->refresh();

        $etapaFinalNota = Etapa::query()
            ->paraNota()
            ->ativas()
            ->finais()
            ->firstOrFail();

        $etapaFinalOs = Etapa::query()
            ->paraOrdemServico()
            ->ativas()
            ->finais()
            ->firstOrFail();

        $this->assertSame(
            'Finalizado',
            $nota->status
        );

        $this->assertSame(
            $etapaFinalNota->id,
            $nota->etapa_id
        );

        $this->assertSame(
            'finalizada',
            $ordemServico->status
        );

        $this->assertSame(
            $etapaFinalOs->id,
            $ordemServico->etapa_id
        );

        $this->assertNotNull(
            $ordemServico->data_fechamento
        );

        $this->assertSame(
            $historicosNotaAntes + 1,
            $nota
                ->historicoEtapas()
                ->count()
        );

        $this->assertSame(
            $historicosOsAntes + 1,
            $ordemServico
                ->historicoEtapas()
                ->count()
        );

        $this->assertDatabaseHas(
            'ordem_servico_etapa_historicos',
            [
                'ordem_servico_id' => $ordemServico->id,

                'etapa_origem_id' => $emManutencao->id,

                'etapa_destino_id' => $etapaFinalOs->id,

                'etapa_origem_nome' => 'Em manutenção',

                'etapa_destino_nome' => 'Pronto para entrega',

                'motivo' => 'Movimentação automática pela finalização da Ordem de Serviço.',
            ]
        );

        $this->assertDatabaseHas(
            'nota_etapa_historicos',
            [
                'nota_id' => $nota->id,

                'etapa_origem_id' => $diagnostico->id,

                'etapa_destino_id' => $etapaFinalNota->id,

                'etapa_origem_nome' => 'Diagnóstico',

                'etapa_destino_nome' => 'Pronto para entrega',

                'motivo' => 'Movimentação automática pela finalização da Nota.',
            ]
        );
    }

    public function test_ordem_servico_cancelada_bloqueia_finalizacao_da_nota_e_faz_rollback(): void
    {
        $cliente = $this->criarCliente();

        $veiculoClienteId =
            $this->criarVeiculoCliente(
                $cliente
            );

        $setorServico =
            $this->criarSetorServico();

        $ordemServico =
            $this->criarOrdemServicoWorkflow(
                $cliente,
                $veiculoClienteId,
                $setorServico,
                300
            );

        $nota = $this->criarNotaComOrdemServico(
            $cliente,
            $veiculoClienteId,
            $ordemServico
        );

        $etapaNotaAntes =
            $nota->etapa_id;

        $etapaOsAntes =
            $ordemServico->etapa_id;

        $historicosNotaAntes =
            $nota
                ->historicoEtapas()
                ->count();

        $historicosOsAntes =
            $ordemServico
                ->historicoEtapas()
                ->count();

        $ordemServico->update([
            'status' => 'cancelada',
            'data_fechamento' => now(),
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            "A O.S. #{$ordemServico->id} está cancelada e não pode ser finalizada junto com a Nota."
        );

        try {
            $this->finalizarNota(
                $nota
            );
        } finally {
            $nota->refresh();

            $ordemServico->refresh();

            $this->assertSame(
                'Aberto',
                $nota->status
            );

            $this->assertSame(
                $etapaNotaAntes,
                $nota->etapa_id
            );

            $this->assertSame(
                'cancelada',
                $ordemServico->status
            );

            $this->assertSame(
                $etapaOsAntes,
                $ordemServico->etapa_id
            );

            $this->assertSame(
                $historicosNotaAntes,
                $nota
                    ->historicoEtapas()
                    ->count()
            );

            $this->assertSame(
                $historicosOsAntes,
                $ordemServico
                    ->historicoEtapas()
                    ->count()
            );

            $this->assertDatabaseCount(
                'movimentacao_estoques',
                0
            );

            $this->assertDatabaseCount(
                'contas_receber',
                0
            );
        }
    }

    public function test_ordem_servico_ja_finalizada_nao_gera_historico_duplicado_ao_finalizar_nota(): void
    {
        $cliente = $this->criarCliente();

        $veiculoClienteId =
            $this->criarVeiculoCliente(
                $cliente
            );

        $setorServico =
            $this->criarSetorServico();

        $ordemServico =
            $this->criarOrdemServicoWorkflow(
                $cliente,
                $veiculoClienteId,
                $setorServico,
                300
            );

        $ordemServico = app(
            FinalizarOrdemServico::class
        )->execute(
            $ordemServico
        );

        $historicosOsAntes =
            $ordemServico
                ->historicoEtapas()
                ->count();

        $dataFechamentoAntes =
            $ordemServico->data_fechamento;

        $nota = $this->criarNotaComOrdemServico(
            $cliente,
            $veiculoClienteId,
            $ordemServico
        );

        $this->finalizarNota(
            $nota
        );

        $ordemServico->refresh();

        $this->assertSame(
            'finalizada',
            $ordemServico->status
        );

        $this->assertSame(
            $historicosOsAntes,
            $ordemServico
                ->historicoEtapas()
                ->count()
        );

        $this->assertTrue(
            $dataFechamentoAntes->equalTo(
                $ordemServico->data_fechamento
            )
        );
    }
}
