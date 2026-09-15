<?php

namespace Tests\Feature\Compras;

use Mockery;
use App\Actions\Compra\AprovarCompra;
use App\Actions\Compra\CancelarCompra;
use App\Actions\Compra\ConferirCompra;
use App\Actions\Compra\CriarCompra;
use App\Actions\Compra\EstornarCompra;
use App\Actions\Compra\IniciarConferenciaCompra;
use App\Actions\Compra\RegistrarEntradaCompra;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Fornecedor;
use App\Actions\Estoque\RegistrarEntrada;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class CompraTest extends TestCase
{
    use RefreshDatabase;

    private function criarFornecedor(): Fornecedor
    {
        $fornecedorId = DB::table('fornecedores')->insertGetId([
            'nome' => 'Fornecedor Teste ' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Fornecedor::findOrFail($fornecedorId);
    }

    private function criarProduto(
        float $quantidade = 10,
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

    private function criarCompra(
        Fornecedor $fornecedor,
        float $valorTotal = 100
    ): Compra {
        return Compra::create([
            'fornecedor_id' => $fornecedor->id,
            'numero_nf' => (string) random_int(100000, 999999),
            'serie_nf' => '1',
            'chave_nf' => null,
            'data_emissao' => now()->toDateString(),
            'data_entrada' => now()->toDateString(),
            'valor_produtos' => $valorTotal,
            'desconto' => 0,
            'frete' => 0,
            'outras_despesas' => 0,
            'valor_total' => $valorTotal,
            'status' => Compra::STATUS_PENDENTE,
            'observacoes' => null,
        ]);
    }

    private function criarItem(
        Compra $compra,
        Produto $produto,
        float $quantidade = 1,
        float $valorUnitario = 100
    ): CompraItem {
        return CompraItem::create([
            'compra_id' => $compra->id,
            'produto_id' => $produto->id,
            'descricao' => $produto->nome,
            'quantidade' => $quantidade,
            'quantidade_conferida' => null,
            'valor_unitario' => $valorUnitario,
            'desconto' => 0,
            'valor_total' => $quantidade * $valorUnitario,
        ]);
    }

    private function criarCompraComItem(
        float $estoque = 10,
        float $quantidade = 1
    ): array {
        $fornecedor = $this->criarFornecedor();
        $produto = $this->criarProduto($estoque);
        $compra = $this->criarCompra(
            $fornecedor,
            $quantidade * 100
        );
        $item = $this->criarItem(
            $compra,
            $produto,
            $quantidade,
            100
        );

        return [$fornecedor, $produto, $compra, $item];
    }

    private function iniciarConferencia(Compra $compra): Compra
    {
        return app(IniciarConferenciaCompra::class)->execute($compra);
    }

    private function conferirCompra(
        Compra $compra,
        CompraItem $item,
        float $quantidade
    ): Compra {
        return app(ConferirCompra::class)->execute($compra, [
            'itens' => [
                $item->id => [
                    'quantidade_conferida' => $quantidade,
                ],
            ],
        ]);
    }

    private function aprovarCompra(Compra $compra): Compra
    {
        return app(AprovarCompra::class)->execute($compra);
    }

    private function prepararCompraAprovada(
        float $estoque = 10,
        float $quantidade = 1,
        ?float $quantidadeConferida = null
    ): array {
        [$fornecedor, $produto, $compra, $item] = $this->criarCompraComItem(
            $estoque,
            $quantidade
        );

        $this->iniciarConferencia($compra);

        $this->conferirCompra(
            $compra,
            $item,
            $quantidadeConferida ?? $quantidade
        );

        $this->aprovarCompra($compra);

        $compra->refresh();
        $item->refresh();
        $produto->refresh();

        return [$fornecedor, $produto, $compra, $item];
    }

    public function test_criar_compra_inicia_com_status_pendente(): void
    {
        $fornecedor = $this->criarFornecedor();
        $produto = $this->criarProduto();

        $compra = app(CriarCompra::class)->execute([
            'fornecedor_id' => $fornecedor->id,
            'numero_nf' => '123456',
            'serie_nf' => '1',
            'chave_nf' => null,
            'data_emissao' => now()->toDateString(),
            'data_entrada' => now()->toDateString(),
            'desconto' => 0,
            'frete' => 0,
            'outras_despesas' => 0,
            'observacoes' => null,
            'itens' => [
                [
                    'produto_id' => $produto->id,
                    'descricao' => $produto->nome,
                    'quantidade' => 2,
                    'valor_unitario' => 100,
                    'desconto' => 0,
                ],
            ],
        ], []);

        $this->assertSame(
            Compra::STATUS_PENDENTE,
            $compra->status
        );

        $this->assertDatabaseHas('compras', [
            'id' => $compra->id,
            'status' => Compra::STATUS_PENDENTE,
        ]);

        $this->assertDatabaseHas('compra_itens', [
            'compra_id' => $compra->id,
            'produto_id' => $produto->id,
            'quantidade' => 2,
        ]);
    }

    public function test_criacao_da_compra_nao_define_quantidade_conferida(): void
    {
        $fornecedor = $this->criarFornecedor();
        $produto = $this->criarProduto();

        $compra = app(CriarCompra::class)->execute([
            'fornecedor_id' => $fornecedor->id,
            'numero_nf' => '123457',
            'serie_nf' => '1',
            'chave_nf' => null,
            'data_emissao' => now()->toDateString(),
            'data_entrada' => now()->toDateString(),
            'desconto' => 0,
            'frete' => 0,
            'outras_despesas' => 0,
            'observacoes' => null,
            'itens' => [
                [
                    'produto_id' => $produto->id,
                    'descricao' => $produto->nome,
                    'quantidade' => 10,
                    'valor_unitario' => 50,
                    'desconto' => 0,
                ],
            ],
        ], []);

        $item = $compra->itens()->firstOrFail();

        $this->assertNull($item->quantidade_conferida);
    }

    public function test_iniciar_conferencia_altera_status(): void
    {
        [, , $compra, $item] = $this->criarCompraComItem();

        $this->iniciarConferencia($compra);

        $compra->refresh();

        $this->assertSame(
            Compra::STATUS_CONFERINDO,
            $compra->status
        );

        $item->refresh();

        $this->assertNull(
            $item->quantidade_conferida
        );
    }

    public function test_nao_permite_iniciar_conferencia_de_compra_que_nao_esteja_pendente(): void
    {
        [, , $compra] = $this->criarCompraComItem();

        $compra->update([
            'status' => Compra::STATUS_CANCELADA,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->iniciarConferencia($compra);
    }

    public function test_conferencia_registra_quantidade_realmente_recebida(): void
    {
        [, , $compra, $item] = $this->criarCompraComItem(
            10,
            10
        );

        $this->iniciarConferencia($compra);

        $this->conferirCompra(
            $compra,
            $item,
            9
        );

        $item->refresh();

        $this->assertSame(
            9.0,
            (float) $item->quantidade_conferida
        );

        $this->assertSame(
            10.0,
            (float) $item->quantidade
        );
    }

    public function test_nao_permite_conferencia_com_quantidade_zero(): void
    {
        [, , $compra, $item] = $this->criarCompraComItem();

        $this->iniciarConferencia($compra);

        $this->expectException(InvalidArgumentException::class);

        $this->conferirCompra(
            $compra,
            $item,
            0
        );
    }

    public function test_nao_permite_aprovar_compra_sem_conferencia(): void
    {
        [, , $compra] = $this->criarCompraComItem();

        $this->iniciarConferencia($compra);

        $this->expectException(InvalidArgumentException::class);

        $this->aprovarCompra($compra);
    }

    public function test_aprovar_compra_conferida(): void
    {
        [, , $compra, $item] = $this->prepararCompraAprovada(
            10,
            10,
            9
        );

        $compra->refresh();
        $item->refresh();

        $this->assertSame(
            Compra::STATUS_APROVADA,
            $compra->status
        );

        $this->assertSame(
            9.0,
            (float) $item->quantidade_conferida
        );
    }

    public function test_entrada_no_estoque_usa_quantidade_conferida(): void
    {
        [, $produto, $compra, $item] = $this->prepararCompraAprovada(
            10,
            10,
            9
        );

        app(RegistrarEntradaCompra::class)->execute($compra);

        $produto->refresh();

        $this->assertSame(
            19.0,
            (float) $produto->quantidade
        );

        $this->assertDatabaseHas('movimentacao_estoques', [
            'produto_id' => $produto->id,
            'tipo' => 'entrada',
            'quantidade' => 9,
            'origem_type' => $item->getMorphClass(),
            'origem_id' => $item->id,
        ]);
    }

    public function test_entrada_no_estoque_cria_apenas_uma_movimentacao_por_item(): void
    {
        [, $produto, $compra, $item] = $this->prepararCompraAprovada(
            10,
            2,
            2
        );

        app(RegistrarEntradaCompra::class)->execute($compra);

        $this->assertDatabaseCount(
            'movimentacao_estoques',
            1
        );

        $this->assertDatabaseHas('movimentacao_estoques', [
            'produto_id' => $produto->id,
            'tipo' => 'entrada',
            'origem_type' => $item->getMorphClass(),
            'origem_id' => $item->id,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(RegistrarEntradaCompra::class)->execute($compra);
    }

    public function test_nao_permite_lancar_estoque_de_compra_nao_aprovada(): void
    {
        [, , $compra] = $this->criarCompraComItem();

        $this->expectException(InvalidArgumentException::class);

        app(RegistrarEntradaCompra::class)->execute($compra);
    }

    public function test_cancelamento_antes_da_entrada_no_estoque(): void
    {
        [, $produto, $compra] = $this->criarCompraComItem(
            10,
            2
        );

        app(CancelarCompra::class)->execute($compra);

        $compra->refresh();
        $produto->refresh();

        $this->assertSame(
            Compra::STATUS_CANCELADA,
            $compra->status
        );

        $this->assertSame(
            10.0,
            (float) $produto->quantidade
        );

        $this->assertDatabaseCount(
            'movimentacao_estoques',
            0
        );
    }

    public function test_nao_permite_cancelar_compra_com_estoque_lancado(): void
    {
        [, $produto, $compra] = $this->prepararCompraAprovada(
            10,
            2,
            2
        );

        app(RegistrarEntradaCompra::class)->execute($compra);

        $produto->refresh();

        $this->assertSame(
            12.0,
            (float) $produto->quantidade
        );

        $this->expectException(InvalidArgumentException::class);

        app(CancelarCompra::class)->execute($compra);

        $compra->refresh();

        $this->assertSame(
            Compra::STATUS_APROVADA,
            $compra->status
        );
    }

    public function test_estorno_reverte_entrada_do_estoque(): void
    {
        [, $produto, $compra, $item] = $this->prepararCompraAprovada(
            10,
            2,
            2
        );

        app(RegistrarEntradaCompra::class)->execute($compra);

        $produto->refresh();

        $this->assertSame(
            12.0,
            (float) $produto->quantidade
        );

        app(EstornarCompra::class)->execute($compra);

        $produto->refresh();
        $compra->refresh();

        $this->assertSame(
            10.0,
            (float) $produto->quantidade
        );

        $this->assertSame(
            Compra::STATUS_CANCELADA,
            $compra->status
        );

        $this->assertDatabaseHas('movimentacao_estoques', [
            'produto_id' => $produto->id,
            'tipo' => 'entrada',
            'quantidade' => 2,
            'origem_type' => $item->getMorphClass(),
            'origem_id' => $item->id,
        ]);

        $this->assertDatabaseHas('movimentacao_estoques', [
            'produto_id' => $produto->id,
            'tipo' => 'saida',
            'quantidade' => 2,
            'origem_type' => $item->getMorphClass(),
            'origem_id' => $item->id,
        ]);
    }

    public function test_estorno_cancela_a_compra(): void
    {
        [, $produto, $compra] = $this->prepararCompraAprovada(
            10,
            1,
            1
        );

        app(RegistrarEntradaCompra::class)->execute($compra);
        app(EstornarCompra::class)->execute($compra);

        $compra->refresh();
        $produto->refresh();

        $this->assertSame(
            Compra::STATUS_CANCELADA,
            $compra->status
        );

        $this->assertSame(
            10.0,
            (float) $produto->quantidade
        );
    }

    public function test_nao_permite_estorno_duas_vezes(): void
    {
        [, $produto, $compra] = $this->prepararCompraAprovada(
            10,
            1,
            1
        );

        app(RegistrarEntradaCompra::class)->execute($compra);
        app(EstornarCompra::class)->execute($compra);

        $this->assertSame(
            10.0,
            (float) $produto->fresh()->quantidade
        );

        $this->expectException(InvalidArgumentException::class);

        app(EstornarCompra::class)->execute($compra);
    }

    public function test_estorno_com_estoque_insuficiente_faz_rollback(): void
    {
        [, $produto, $compra] = $this->prepararCompraAprovada(
            1,
            1,
            1
        );

        app(RegistrarEntradaCompra::class)->execute($compra);

        $produto->refresh();

        $this->assertSame(
            2.0,
            (float) $produto->quantidade
        );

        $produto->update([
            'quantidade' => 0,
        ]);

        $this->expectException(InvalidArgumentException::class);

        try {
            app(EstornarCompra::class)->execute($compra);
        } finally {
            $produto->refresh();
            $compra->refresh();

            $this->assertSame(
                0.0,
                (float) $produto->quantidade
            );

            $this->assertSame(
                Compra::STATUS_APROVADA,
                $compra->status
            );

            $this->assertDatabaseCount(
                'movimentacao_estoques',
                1
            );

            $this->assertDatabaseMissing('movimentacao_estoques', [
                'tipo' => 'saida',
                'origem_id' => $compra->itens()->firstOrFail()->id,
            ]);
        }
    }

    public function test_estorno_sem_entrada_nao_cancela_compra(): void
    {
        [, , $compra] = $this->prepararCompraAprovada(
            10,
            1,
            1
        );

        $this->expectException(InvalidArgumentException::class);

        try {
            app(EstornarCompra::class)->execute($compra);
        } finally {
            $compra->refresh();

            $this->assertSame(
                Compra::STATUS_APROVADA,
                $compra->status
            );

            $this->assertDatabaseCount(
                'movimentacao_estoques',
                0
            );
        }
    }

    public function test_falha_em_um_item_faz_rollback_das_entradas_anteriores(): void
    {
        $fornecedor = $this->criarFornecedor();
        $produto1 = $this->criarProduto(10);
        $produto2 = $this->criarProduto(10);

        $compra = $this->criarCompra($fornecedor, 300);

        $item1 = $this->criarItem($compra, $produto1, 2, 100);
        $item2 = $this->criarItem($compra, $produto2, 2, 50);

        $this->iniciarConferencia($compra);

        app(ConferirCompra::class)->execute($compra, [
            'itens' => [
                $item1->id => [
                    'quantidade_conferida' => 2,
                ],
                $item2->id => [
                    'quantidade_conferida' => 2,
                ],
            ],
        ]);

        $this->aprovarCompra($compra);

        $registrarEntradaReal = new RegistrarEntrada();

        $registrarEntradaMock = Mockery::mock(RegistrarEntrada::class);

        $registrarEntradaMock
            ->shouldReceive('execute')
            ->once()
            ->ordered()
            ->andReturnUsing(function (...$args) use ($registrarEntradaReal) {
                return $registrarEntradaReal->execute(...$args);
            });

        $registrarEntradaMock
            ->shouldReceive('execute')
            ->once()
            ->ordered()
            ->andThrow(new InvalidArgumentException(
                'Falha simulada no segundo item.'
            ));

        $this->app->instance(
            RegistrarEntrada::class,
            $registrarEntradaMock
        );

        $this->expectException(InvalidArgumentException::class);

        try {
            app(RegistrarEntradaCompra::class)->execute($compra);
        } finally {
            $produto1->refresh();
            $produto2->refresh();
            $compra->refresh();

            $this->assertSame(
                10.0,
                (float) $produto1->quantidade
            );

            $this->assertSame(
                10.0,
                (float) $produto2->quantidade
            );

            $this->assertDatabaseCount(
                'movimentacao_estoques',
                0
            );

            $this->assertSame(
                Compra::STATUS_APROVADA,
                $compra->status
            );
        }
    }

    public function test_nao_permite_aprovar_compra_com_item_sem_conferencia(): void
    {
        $fornecedor = $this->criarFornecedor();

        $produto1 = $this->criarProduto(10);
        $produto2 = $this->criarProduto(10);

        $compra = $this->criarCompra(
            $fornecedor,
            200
        );

        $item1 = $this->criarItem(
            $compra,
            $produto1,
            1,
            100
        );

        $this->criarItem(
            $compra,
            $produto2,
            1,
            100
        );

        $this->iniciarConferencia($compra);

        app(ConferirCompra::class)->execute($compra, [
            'itens' => [
                $item1->id => [
                    'quantidade_conferida' => 1,
                ],
                $compra->itens()->latest('id')->firstOrFail()->id => [
                    'quantidade_conferida' => 1,
                ],
            ],
        ]);

        $compra->itens()->latest('id')->firstOrFail()->update([
            'quantidade_conferida' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->aprovarCompra($compra);
    }
}
