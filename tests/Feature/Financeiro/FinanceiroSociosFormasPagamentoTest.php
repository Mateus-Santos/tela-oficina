<?php

namespace Tests\Feature\Financeiro;

use App\Actions\FinanceiroSocios\CriarContaReceberSocio;
use App\Actions\FinanceiroSocios\RegistrarRecebimentoSocio;
use App\Models\FormaPagamento;
use App\Models\Socio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FinanceiroSociosFormasPagamentoTest extends TestCase
{
    use RefreshDatabase;

    private function criarConta(): \App\Models\ContaReceberSocio
    {
        $socio = Socio::create(['nome' => 'Socio Teste']);
        return app(CriarContaReceberSocio::class)->execute($socio, [
            'devedor' => 'Devedor Teste',
            'descricao' => 'Emprestimo em dinheiro',
            'origem' => 'dinheiro',
            'valor' => '100.00',
            'data_operacao' => '2026-11-04',
            'primeiro_vencimento' => '2026-11-30',
            'quantidade_parcelas' => 1,
        ]);
    }

    public function test_recebimento_usa_forma_cadastrada_sem_tocar_no_caixa_da_oficina(): void
    {
        $forma = FormaPagamento::create(['nome' => 'Pix', 'ativo' => true]);
        $conta = $this->criarConta();
        $parcela = $conta->parcelas()->firstOrFail();

        $recebimento = app(RegistrarRecebimentoSocio::class)->execute($conta, [
            'parcela_id' => $parcela->id,
            'valor' => '25.00',
            'data_recebimento' => '2026-11-05',
            'forma_pagamento_id' => $forma->id,
        ]);

        $this->assertSame((int) $forma->id, (int) $recebimento->forma_pagamento_id);
        $this->assertSame('Pix', $recebimento->forma_pagamento_nome);
        $this->assertSame('Pix', $recebimento->meio_recebimento);
        $this->assertSame('parcial', $conta->fresh()->status);
        $this->assertSame(75.0, $conta->fresh()->saldo);
        $this->assertDatabaseCount('recebimentos', 0);
        $this->assertDatabaseCount('retiradas_socios', 0);

        $forma->update(['nome' => 'Pix atualizado', 'ativo' => false]);
        $this->assertSame('Pix', $recebimento->fresh()->forma_pagamento_nome);
    }

    public function test_nao_permite_forma_de_pagamento_inativa(): void
    {
        $forma = FormaPagamento::create(['nome' => 'Dinheiro', 'ativo' => false]);
        $conta = $this->criarConta();

        $this->expectException(InvalidArgumentException::class);
        app(RegistrarRecebimentoSocio::class)->execute($conta, [
            'parcela_id' => $conta->parcelas()->firstOrFail()->id,
            'valor' => '10.00',
            'data_recebimento' => '2026-11-05',
            'forma_pagamento_id' => $forma->id,
        ]);
    }

    public function test_mantem_nome_completo_e_snapshot_para_forma_longa(): void
    {
        $nome = str_repeat('Transferencia Bancaria ', 3);
        $forma = FormaPagamento::create(['nome' => $nome, 'ativo' => true]);
        $conta = $this->criarConta();
        $rec = app(RegistrarRecebimentoSocio::class)->execute($conta, [
            'parcela_id' => $conta->parcelas()->firstOrFail()->id,
            'valor' => '10.00',
            'data_recebimento' => '2026-11-05',
            'forma_pagamento_id' => $forma->id,
        ]);
        $this->assertSame($nome, $rec->forma_pagamento_nome);
        $this->assertSame(30, mb_strlen($rec->meio_recebimento));
    }
}
