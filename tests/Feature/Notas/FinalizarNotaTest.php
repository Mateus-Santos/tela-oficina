<?php

namespace Tests\Feature\Notas;

use App\Actions\Notas\CancelarNota;
use App\Actions\Notas\FinalizarNota;
use App\Models\CategoriaFinanceira;
use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\Produto;
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

    private function criarProduto(
        int $quantidade = 10,
        float $preco = 100
    ): Produto {
        $produtoId = DB::table('produtos')->insertGetId([
            'nome' => 'Produto Teste ' . uniqid(),
            'descricao' => 'Produto utilizado nos testes.',
            'quantidade' => $quantidade,
            'estoque_minimo' => 1,
            'preco_uni' => $preco,
            'codigo_fabricante' => 'FAB-' . uniqid(),
            'codigo_barras' => null,
            'status' => true,
            'fornecedor_id' => null,
            'marca' => 'Marca Teste',
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

        $this->criarFinalizador()->execute($nota);

        $produto->refresh();

        $this->assertSame(
            8,
            $produto->quantidade
        );

        $this->assertDatabaseHas('movimentacao_estoques', [
            'produto_id' => $produto->id,
            'tipo' => 'saida',
            'quantidade' => 2,
            'origem_type' => NotasItem::class,
        ]);
    }

    public function test_item_que_nao_e_produto_nao_movimenta_estoque(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();
        $nota = $this->criarNota($cliente, 300);

        $montadoraId = DB::table('montadoras')->insertGetId([
            'nome' => 'Montadora Teste ' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $veiculoId = DB::table('veiculos')->insertGetId([
            'nome' => 'Veículo Teste',
            'montadora_id' => $montadoraId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pessoaId = DB::table('pessoas')->insertGetId([
            'nome' => 'Cliente OS Teste',
            'cpf' => '98765432109',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clienteOsId = DB::table('clientes')->insertGetId([
            'pessoa_id' => $pessoaId,
            'pontos' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $veiculoClienteId = DB::table('veiculos_clientes')->insertGetId([
            'cliente_id' => $clienteOsId,
            'veiculo_id' => $veiculoId,
            'placa' => 'ABC1234',
            'ano' => 2020,
            'cor' => 'Preto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $setorServicoId = DB::table('setor_servicos')->insertGetId([
            'setor' => 'Mecânica',
            'nivel' => 'Normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ordemServicoId = DB::table('ordem_servicos')->insertGetId([
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
            'itemable_type' => 'App\\Models\\OrdemServico',
            'itemable_id' => $ordemServicoId,
            'descricao' => 'Serviço teste',
            'quantidade' => 1,
            'valor_unitario' => 300,
            'desconto' => 0,
            'valor_total' => 300,
            'garantia_meses' => null,
            'garantia_km' => null,
        ]);

        $this->criarFinalizador()->execute($nota);

        $this->assertDatabaseMissing('movimentacao_estoques', [
            'origem_type' => NotasItem::class,
            'origem_id' => $item->id,
            'tipo' => 'saida',
        ]);

        $this->assertDatabaseHas('contas_receber', [
            'nota_id' => $nota->id,
            'cliente_id' => $cliente->id,
            'valor_original' => 300,
            'status' => 'aberta',
        ]);
    }

    public function test_finalizacao_cria_apenas_uma_saida_por_item(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();
        $produto = $this->criarProduto(10);
        $nota = $this->criarNota($cliente, 100);

        $item = $this->criarItemProduto(
            $nota,
            $produto,
            1,
            100
        );

        $this->criarFinalizador()->execute($nota);

        $this->assertDatabaseCount(
            'movimentacao_estoques',
            1
        );

        $this->assertDatabaseHas('movimentacao_estoques', [
            'origem_type' => NotasItem::class,
            'origem_id' => $item->id,
            'tipo' => 'saida',
        ]);
    }

    public function test_nao_permite_finalizar_nota_que_nao_esteja_aberta(): void
    {
        $cliente = $this->criarCliente();
        $nota = $this->criarNota($cliente, 100);

        $nota->update([
            'status' => 'Finalizado',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->criarFinalizador()->execute($nota);
    }

    public function test_nao_permite_finalizar_nota_sem_itens(): void
    {
        $cliente = $this->criarCliente();
        $nota = $this->criarNota($cliente, 100);

        $this->expectException(InvalidArgumentException::class);

        $this->criarFinalizador()->execute($nota);
    }

    public function test_nao_permite_saida_maior_que_o_estoque(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();
        $produto = $this->criarProduto(1);
        $nota = $this->criarNota($cliente, 200);

        $this->criarItemProduto(
            $nota,
            $produto,
            2,
            100
        );

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->criarFinalizador()->execute($nota);
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

        $nota = $this->criarNota($cliente, 300);

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

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->criarFinalizador()->execute($nota);
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
        $nota = $this->criarNota($cliente, 100);

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

        $this->expectException(InvalidArgumentException::class);

        $this->criarFinalizador()->execute($nota);
    }

    public function test_nao_permite_finalizacao_quando_item_ja_possui_baixa(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();
        $produto = $this->criarProduto(10);
        $nota = $this->criarNota($cliente, 100);

        $item = $this->criarItemProduto(
            $nota,
            $produto,
            1,
            100
        );

        DB::table('movimentacao_estoques')->insert([
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

        $this->expectException(InvalidArgumentException::class);

        $this->criarFinalizador()->execute($nota);
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

        $this->criarFinalizador()->execute($nota);

        $this->assertDatabaseHas('contas_receber', [
            'nota_id' => $nota->id,
            'cliente_id' => $cliente->id,
            'valor_original' => 250,
            'desconto' => 0,
            'juros' => 0,
            'multa' => 0,
            'status' => 'aberta',
        ]);

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

        $this->criarFinalizador()->execute($nota);

        $this->assertDatabaseHas('contas_receber', [
            'nota_id' => $nota->id,
            'valor_original' => 500,
        ]);
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

        $this->criarFinalizador()->execute($nota);

        $conta = ContaReceber::where(
            'nota_id',
            $nota->id
        )->firstOrFail();

        $this->assertSame(
            $nota->cliente_id,
            $conta->cliente_id
        );
    }

    public function test_nao_cria_conta_a_receber_duplicada(): void
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

        ContaReceber::create([
            'cliente_id' => $cliente->id,
            'nota_id' => $nota->id,
            'categoria_financeira_id' => $categoria->id,
            'descricao' => 'Conta existente',
            'valor_original' => 100,
            'desconto' => 0,
            'juros' => 0,
            'multa' => 0,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->toDateString(),
            'status' => 'aberta',
        ]);

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->criarFinalizador()->execute($nota);
        } finally {
            $this->assertDatabaseCount(
                'contas_receber',
                1
            );

            $this->assertDatabaseCount(
                'movimentacao_estoques',
                0
            );

            $produto->refresh();

            $this->assertSame(
                10,
                $produto->quantidade
            );
        }
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
            ->where('id', $item2->id)
            ->update([
                'itemable_id' => 999999,
            ]);

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->criarFinalizador()->execute($nota);
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

    public function test_nao_permite_finalizar_nota_sem_cliente(): void
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

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->criarFinalizador()->execute($nota);
        } finally {
            $this->assertDatabaseCount(
                'contas_receber',
                0
            );

            $this->assertDatabaseCount(
                'movimentacao_estoques',
                0
            );

            $produto->refresh();

            $this->assertSame(
                10,
                $produto->quantidade
            );
        }
    }

    public function test_cancelamento_reverte_baixa_de_estoque(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();
        $produto = $this->criarProduto(10);
        $nota = $this->criarNota($cliente, 100);

        $this->criarItemProduto(
            $nota,
            $produto,
            2,
            50
        );

        $this->criarFinalizador()->execute($nota);

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

        app(CancelarNota::class)->execute($nota);

        $produto->refresh();

        $this->assertSame(
            10,
            $produto->quantidade
        );

        $this->assertDatabaseHas('movimentacao_estoques', [
            'produto_id' => $produto->id,
            'tipo' => 'entrada',
            'quantidade' => 2,
        ]);
    }

    public function test_nao_permite_cancelar_nota_aberta(): void
    {
        $cliente = $this->criarCliente();
        $nota = $this->criarNota($cliente, 100);

        $this->expectException(InvalidArgumentException::class);

        app(CancelarNota::class)->execute($nota);
    }

    public function test_nao_permite_cancelar_nota_duas_vezes(): void
    {
        $this->criarCategoriaVendasEServicos();

        $cliente = $this->criarCliente();
        $produto = $this->criarProduto(10);
        $nota = $this->criarNota($cliente, 100);

        $this->criarItemProduto(
            $nota,
            $produto,
            1,
            100
        );

        $this->criarFinalizador()->execute($nota);

        app(CancelarNota::class)->execute($nota);

        $nota->refresh();

        $this->assertSame(
            'Cancelado',
            $nota->status
        );

        $this->expectException(InvalidArgumentException::class);

        app(CancelarNota::class)->execute($nota);
    }
}
