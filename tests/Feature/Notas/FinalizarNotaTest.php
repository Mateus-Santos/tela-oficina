<?php

namespace Tests\Feature\Notas;

use App\Actions\Notas\CancelarNota;
use App\Actions\Notas\FinalizarNota;
use App\Models\MovimentacaoEstoque;
use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\OrdemServico;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class FinalizarNotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalizar_nota_baixa_estoque_do_produto(): void
    {
        $produto = $this->criarProduto(10);
        $nota = $this->criarNota();

        $item = $this->adicionarProduto($nota, $produto, 2);

        app(FinalizarNota::class)->execute($nota);

        $this->assertDatabaseHas('notas', [
            'id' => $nota->id,
            'status' => 'Finalizado',
        ]);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade' => 8,
        ]);

        $this->assertDatabaseHas('movimentacao_estoques', [
            'produto_id' => $produto->id,
            'tipo' => 'saida',
            'quantidade' => 2,
            'origem_type' => NotasItem::class,
            'origem_id' => $item->id,
        ]);
    }

    public function test_item_que_nao_e_produto_nao_movimenta_estoque(): void
    {
        $produto = $this->criarProduto(10);
        $nota = $this->criarNota();

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
            'nome' => 'Pessoa Teste',
            'cpf' => null,
            'rg' => null,
            'data_nascimento' => null,
            'telefone_1' => null,
            'telefone_2' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clienteId = DB::table('clientes')->insertGetId([
            'pessoa_id' => $pessoaId,
            'pontos' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $veiculoClienteId = DB::table('veiculos_clientes')->insertGetId([
            'placa' => 'TST' . rand(1000, 9999),
            'ano' => 2024,
            'cor' => 'Preto',
            'cliente_id' => $clienteId,
            'veiculo_id' => $veiculoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $setorServicoId = DB::table('setor_servicos')->insertGetId([
            'setor' => 'Oficina',
            'nivel' => 'Normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $servicoId = DB::table('ordem_servicos')->insertGetId([
            'data_abertura' => now(),
            'data_fechamento' => null,
            'descricao' => 'Serviço de teste',
            'valor' => 100,
            'status' => 'aberta',
            'setor_servico_id' => $setorServicoId,
            'veiculo_cliente_id' => $veiculoClienteId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $nota->itens()->create([
            'itemable_type' => OrdemServico::class,
            'itemable_id' => $servicoId,
            'descricao' => 'Serviço de teste',
            'quantidade' => 1,
            'valor_unitario' => 100,
            'desconto' => 0,
            'valor_total' => 100,
        ]);

        app(FinalizarNota::class)->execute($nota);

        $produto->refresh();

        $this->assertSame(10, (int) $produto->quantidade);

        $this->assertDatabaseCount('movimentacao_estoques', 0);

        $this->assertDatabaseHas('notas', [
            'id' => $nota->id,
            'status' => 'Finalizado',
        ]);
    }

    public function test_finalizacao_cria_apenas_uma_saida_por_item(): void
    {
        $produto = $this->criarProduto(10);
        $nota = $this->criarNota();

        $item = $this->adicionarProduto($nota, $produto, 2);

        app(FinalizarNota::class)->execute($nota);

        $this->assertDatabaseCount('movimentacao_estoques', 1);

        $this->assertDatabaseHas('movimentacao_estoques', [
            'produto_id' => $produto->id,
            'tipo' => 'saida',
            'quantidade' => 2,
            'origem_type' => NotasItem::class,
            'origem_id' => $item->id,
        ]);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade' => 8,
        ]);
    }

    public function test_nao_permite_finalizar_nota_que_nao_esteja_aberta(): void
    {
        $nota = $this->criarNota('Finalizado');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Somente notas com status Aberto podem ser finalizadas.'
        );

        app(FinalizarNota::class)->execute($nota);
    }

    public function test_nao_permite_finalizar_nota_sem_itens(): void
    {
        $nota = $this->criarNota();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Não é possível finalizar uma nota sem itens.'
        );

        app(FinalizarNota::class)->execute($nota);
    }

    public function test_nao_permite_saida_maior_que_o_estoque(): void
    {
        $produto = $this->criarProduto(3);
        $nota = $this->criarNota();

        $this->adicionarProduto($nota, $produto, 5);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Estoque insuficiente.');

        app(FinalizarNota::class)->execute($nota);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade' => 3,
        ]);

        $this->assertDatabaseCount('movimentacao_estoques', 0);

        $this->assertDatabaseHas('notas', [
            'id' => $nota->id,
            'status' => 'Aberto',
        ]);
    }

    public function test_falha_em_um_produto_faz_rollback_das_baixas_anteriores(): void
    {
        $produtoDisponivel = $this->criarProduto(10);
        $produtoIndisponivel = $this->criarProduto(1);

        $nota = $this->criarNota();

        $this->adicionarProduto($nota, $produtoDisponivel, 3);
        $this->adicionarProduto($nota, $produtoIndisponivel, 2);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Estoque insuficiente.');

        app(FinalizarNota::class)->execute($nota);

        $this->assertDatabaseHas('produtos', [
            'id' => $produtoDisponivel->id,
            'quantidade' => 10,
        ]);

        $this->assertDatabaseHas('produtos', [
            'id' => $produtoIndisponivel->id,
            'quantidade' => 1,
        ]);

        $this->assertDatabaseCount('movimentacao_estoques', 0);

        $this->assertDatabaseHas('notas', [
            'id' => $nota->id,
            'status' => 'Aberto',
        ]);
    }

    public function test_nao_permite_item_com_referencia_invalida(): void
    {
        $nota = $this->criarNota();

        $nota->itens()->create([
            'itemable_type' => Produto::class,
            'itemable_id' => 999999,
            'descricao' => 'Produto inexistente',
            'quantidade' => 1,
            'valor_unitario' => 100,
            'desconto' => 0,
            'valor_total' => 100,
            'garantia_dias' => null,
            'garantia_inicio' => null,
            'garantia_fim' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'possui um produto ou serviço inválido.'
        );

        app(FinalizarNota::class)->execute($nota);
    }

    public function test_nao_permite_finalizacao_quando_item_ja_possui_baixa(): void
    {
        $produto = $this->criarProduto(10);
        $nota = $this->criarNota();

        $item = $this->adicionarProduto($nota, $produto, 2);

        MovimentacaoEstoque::create([
            'produto_id' => $produto->id,
            'tipo' => 'saida',
            'quantidade' => 2,
            'saldo_anterior' => 10,
            'saldo_posterior' => 8,
            'valor_unitario' => 100,
            'origem_type' => NotasItem::class,
            'origem_id' => $item->id,
            'usuario_id' => null,
            'observacoes' => 'Baixa criada previamente para teste.',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'já possui uma baixa de estoque registrada.'
        );

        app(FinalizarNota::class)->execute($nota);

        $this->assertDatabaseCount('movimentacao_estoques', 1);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade' => 10,
        ]);
    }

    public function test_cancelamento_reverte_baixa_de_estoque(): void
    {
        $produto = $this->criarProduto(10);
        $nota = $this->criarNota();

        $item = $this->adicionarProduto($nota, $produto, 3);

        app(FinalizarNota::class)->execute($nota);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade' => 7,
        ]);

        app(CancelarNota::class)->execute($nota);

        $this->assertDatabaseHas('notas', [
            'id' => $nota->id,
            'status' => 'Cancelado',
        ]);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade' => 10,
        ]);

        $this->assertDatabaseCount('movimentacao_estoques', 2);

        $this->assertDatabaseHas('movimentacao_estoques', [
            'produto_id' => $produto->id,
            'tipo' => 'entrada',
            'quantidade' => 3,
            'origem_type' => NotasItem::class,
            'origem_id' => $item->id,
        ]);
    }

    public function test_nao_permite_cancelar_nota_aberta(): void
    {
        $nota = $this->criarNota();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Somente notas finalizadas podem ser canceladas.'
        );

        app(CancelarNota::class)->execute($nota);
    }

    public function test_nao_permite_cancelar_nota_duas_vezes(): void
    {
        $produto = $this->criarProduto(10);
        $nota = $this->criarNota();

        $this->adicionarProduto($nota, $produto, 2);

        app(FinalizarNota::class)->execute($nota);
        app(CancelarNota::class)->execute($nota);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'quantidade' => 10,
        ]);

        $this->assertDatabaseCount('movimentacao_estoques', 2);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Somente notas finalizadas podem ser canceladas.'
        );

        app(CancelarNota::class)->execute($nota);

        $this->assertDatabaseCount('movimentacao_estoques', 2);
    }

    private function criarProduto(int $quantidade): Produto
    {
        static $codigo = 1;

        return Produto::create([
            'nome' => 'Produto de Teste',
            'marca' => 'Marca Teste',
            'descricao' => 'Produto criado para teste automatizado.',
            'quantidade' => $quantidade,
            'estoque_minimo' => 0,
            'preco_uni' => 100,
            'codigo_fabricante' => 'TEST-' . str_pad(
                $codigo++,
                6,
                '0',
                STR_PAD_LEFT
            ),
            'codigo_barras' => null,
            'status' => true,
            'fornecedor_id' => null,
        ]);
    }

    private function criarNota(string $status = 'Aberto'): Nota
    {
        return Nota::create([
            'cliente_id' => null,
            'veiculo_cliente_id' => null,
            'tipo' => 'Venda',
            'status' => $status,
            'subtotal' => 0,
            'desconto' => 0,
            'total' => 0,
            'observacoes' => null,
            'km' => null,
            'km_proxima_troca_oleo' => null,
        ]);
    }

    private function adicionarProduto(
        Nota $nota,
        Produto $produto,
        int $quantidade
    ): NotasItem {
        return $nota->itens()->create([
            'itemable_type' => $produto->getMorphClass(),
            'itemable_id' => $produto->id,
            'descricao' => $produto->nome,
            'quantidade' => $quantidade,
            'valor_unitario' => 100,
            'desconto' => 0,
            'valor_total' => $quantidade * 100,
            'garantia_dias' => null,
            'garantia_inicio' => null,
            'garantia_fim' => null,
        ]);
    }
}
