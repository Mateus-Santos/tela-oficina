<?php

namespace Tests\Feature\Estoque;

use App\Actions\Estoque\RegistrarAjuste;
use App\Actions\Estoque\RegistrarEntrada;
use App\Actions\Estoque\RegistrarSaida;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrecisaoEstoqueTest extends TestCase
{
    use RefreshDatabase;

    private function produto(string $saldo = '0.000'): Produto
    {
        return Produto::query()->create([
            'nome' => 'PIVO DE SUSPENSAO TESTE',
            'descricao' => 'Produto para testar quantidade decimal',
            'codigo_fabricante' => 'PVI2183-TESTE',
            'codigo_barras' => '7891111111111',
            'quantidade' => $saldo,
            'estoque_minimo' => '0.125',
            'preco_uni' => '10.00',
            'status' => true,
        ]);
    }

    public function test_entrada_e_saida_preservam_tres_casas_decimais(): void
    {
        $produto = $this->produto();

        $entrada = app(RegistrarEntrada::class)->execute($produto, '1.001');

        $this->assertSame('1.001', $entrada->saldo_posterior);
        $this->assertSame('1.001', $produto->fresh()->quantidade);
        $this->assertSame('0.125', $produto->fresh()->estoque_minimo);

        $saida = app(RegistrarSaida::class)->execute($produto, '1.001');

        $this->assertSame('0.000', $produto->fresh()->quantidade);
        $this->assertSame('1.001', $saida->quantidade);
        $this->assertSame('0.000', $saida->saldo_posterior);
    }

    public function test_ajuste_registra_o_saldo_preciso_e_permite_reversao(): void
    {
        $produto = $this->produto();
        app(RegistrarEntrada::class)->execute($produto, '1.000');

        $ajuste = app(RegistrarAjuste::class)->execute($produto, '1.001');

        $this->assertSame('0.001', $ajuste->quantidade);
        $this->assertSame('1.001', $ajuste->saldo_posterior);
        $this->assertSame('1.001', $produto->fresh()->quantidade);

        app(RegistrarSaida::class)->execute($produto, '1.001');
        $this->assertSame('0.000', $produto->fresh()->quantidade);
    }

    public function test_busca_geral_encontra_codigo_interno_fabricante_e_barras(): void
    {
        $produto = $this->produto();

        foreach ([$produto->codigo, 'PVI2183', '7891111111111', 'SUSPENSAO'] as $busca) {
            $this->assertTrue(
                Produto::query()->buscaGeral($busca)->whereKey($produto->id)->exists(),
                "Pesquisa não encontrou o produto pelo termo {$busca}"
            );
        }
    }
}
