<?php

namespace Tests\Feature\Usabilidade;

use App\Models\Nota;
use App\Models\NotasItem;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetornoNotaAposAjusteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => true,
        ]));
    }

    private function produto(string $saldo = '1.000'): Produto
    {
        return Produto::query()->create([
            'nome' => 'PRODUTO TESTE U01',
            'descricao' => 'Teste de retorno a nota',
            'codigo_fabricante' => 'U01-REF',
            'quantidade' => $saldo,
            'estoque_minimo' => '0.000',
            'preco_uni' => '20.00',
            'status' => true,
        ]);
    }

    private function notaComProduto(Produto $produto): Nota
    {
        $nota = Nota::query()->create([
            'tipo' => 'Venda',
            'status' => 'Aberto',
            'subtotal' => '20.00',
            'desconto' => '0.00',
            'total' => '20.00',
        ]);

        NotasItem::query()->create([
            'nota_id' => $nota->id,
            'itemable_type' => Produto::class,
            'itemable_id' => $produto->id,
            'descricao' => 'Produto teste',
            'quantidade' => 1,
            'valor_unitario' => '20.00',
            'desconto' => '0.00',
            'valor_total' => '20.00',
        ]);

        return $nota;
    }

    public function test_ajuste_originado_da_nota_retorna_para_show(): void
    {
        $produto = $this->produto();
        $nota = $this->notaComProduto($produto);
        $params = ['produto' => $produto, 'nota_id' => $nota->id];

        $this->get(route('estoque.ajuste', $params))
            ->assertOk()
            ->assertSee('action="'.e(route('estoque.registrarAjuste', $params)).'"', false);

        $this->post(route('estoque.registrarAjuste', $params), [
            'novo_saldo' => '3.000',
            'observacoes' => 'Conferencia fisica U01',
        ])->assertRedirect(route('notas.show', $nota->id));

        $this->assertSame('3.000', $produto->fresh()->quantidade);
        $this->assertDatabaseHas('movimentacao_estoques', [
            'produto_id' => $produto->id,
            'tipo' => 'ajuste',
        ]);
    }

    public function test_ajuste_sem_nota_retorna_para_estoque(): void
    {
        $produto = $this->produto();

        $this->post(route('estoque.registrarAjuste', $produto), [
            'novo_saldo' => '2.000',
            'observacoes' => 'Conferencia sem nota U01',
        ])->assertRedirect(route('estoque.index'));
    }

    public function test_nota_arbitraria_nao_gera_redirecionamento_para_outra_nota(): void
    {
        $produto = $this->produto();
        $outraNota = Nota::query()->create([
            'tipo' => 'Venda',
            'status' => 'Aberto',
        ]);
        $params = ['produto' => $produto, 'nota_id' => $outraNota->id];

        $this->get(route('estoque.ajuste', $params))
            ->assertOk()
            ->assertDontSee('action="'.e(route('estoque.registrarAjuste', $params)).'"', false);

        $this->post(route('estoque.registrarAjuste', $params), [
            'novo_saldo' => '2.000',
            'observacoes' => 'Origem indevida U01',
        ])->assertRedirect(route('estoque.index'));
    }

    public function test_criar_nota_redireciona_para_show_da_nota_criada(): void
    {
        $nota = new Nota(['tipo' => 'Venda', 'status' => 'Aberto']);
        $nota->id = 314;
        $nota->setRelation('itens', new \Illuminate\Database\Eloquent\Collection([
            new NotasItem(),
        ]));

        $request = \Mockery::mock(\App\Http\Requests\Notas\StoreNotaRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(['itens' => []]);
        $request->shouldReceive('user')->once()->andReturn((object) ['id' => 12]);

        $acao = \Mockery::mock(\App\Actions\Notas\CriarNota::class);
        $acao->shouldReceive('execute')->once()
            ->with(['itens' => []], 12)->andReturn($nota);

        $resposta = (new \App\Http\Controllers\NotasItemController())
            ->store($request, $acao);

        $this->assertSame(route('notas.show', 314), $resposta->getTargetUrl());
    }

    public function test_editar_nota_redireciona_para_show_da_nota_editada(): void
    {
        $nota = Nota::query()->create([
            'tipo' => 'Venda',
            'status' => 'Aberto',
        ]);

        $request = \Mockery::mock(\App\Http\Requests\Notas\UpdateNotaRequest::class);
        $request->shouldReceive('validated')->once()
            ->andReturn(['observacoes' => 'Revisada']);

        $acao = \Mockery::mock(\App\Actions\Notas\AtualizarNota::class);
        $acao->shouldReceive('execute')->once()
            ->withArgs(function ($recebida, $dados) use ($nota) {
                return $recebida instanceof Nota
                    && $recebida->id === $nota->id
                    && $dados === ['observacoes' => 'Revisada'];
            })
            ->andReturn($nota);

        $resposta = (new \App\Http\Controllers\NotasItemController())
            ->update($request, (string) $nota->id, $acao);

        $this->assertSame(route('notas.show', $nota->id), $resposta->getTargetUrl());
    }
}
