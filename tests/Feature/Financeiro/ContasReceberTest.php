<?php

namespace Tests\Feature\Financeiro;

use App\Actions\Financeiro\AtualizarContaReceber;
use App\Actions\Financeiro\CriarContaReceber;
use App\Actions\Financeiro\EstornarRecebimento;
use App\Actions\Financeiro\RegistrarRecebimento;
use App\Models\CategoriaFinanceira;
use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\FormaPagamento;
use App\Models\Nota;
use App\Models\Recebimento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContasReceberTest extends TestCase
{
    use RefreshDatabase;

    private function criarCliente(): Cliente
    {
        $pessoaId = DB::table('pessoas')->insertGetId([
            'nome' => 'Cliente Teste',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Cliente::create([
            'pessoa_id' => $pessoaId,
        ]);
    }

    private function criarCategoria(): CategoriaFinanceira
    {
        return CategoriaFinanceira::firstOrCreate(
            [
                'nome' => 'Serviços',
                'tipo' => 'entrada',
            ],
            [
                'ativo' => true,
            ]
        );
    }

    private function criarFormaPagamento(): FormaPagamento
    {
        return FormaPagamento::create([
            'nome' => 'Dinheiro',
            'ativo' => true,
        ]);
    }

    private function criarNota(?Cliente $cliente = null): Nota
    {
        $cliente ??= $this->criarCliente();

        return Nota::create([
            'cliente_id' => $cliente->id,
            'tipo' => 'Orçamento',
            'status' => 'Aberto',
        ]);
    }

    private function criarConta(array $dados = []): ContaReceber
    {
        $cliente = $dados['cliente'] ?? $this->criarCliente();
        $categoria = $dados['categoria'] ?? $this->criarCategoria();

        return app(CriarContaReceber::class)->execute([
            'cliente_id' => $cliente->id,
            'categoria_financeira_id' => $categoria->id,
            'descricao' => $dados['descricao'] ?? 'Serviço mecânico',
            'valor_original' => $dados['valor_original'] ?? 100,
            'desconto' => $dados['desconto'] ?? 0,
            'juros' => $dados['juros'] ?? 0,
            'multa' => $dados['multa'] ?? 0,
            'data_emissao' => $dados['data_emissao'] ?? now()->toDateString(),
            'data_vencimento' => $dados['data_vencimento'] ?? now()->addDays(10)->toDateString(),
            'observacoes' => $dados['observacoes'] ?? null,
        ]);
    }

    public function test_cria_conta_a_receber(): void
    {
        $conta = $this->criarConta();

        $this->assertDatabaseHas('contas_receber', [
            'id' => $conta->id,
            'cliente_id' => $conta->cliente_id,
            'valor_original' => 100,
            'status' => 'aberta',
        ]);
    }

    public function test_cria_conta_com_nota_e_define_cliente_pela_nota(): void
    {
        $cliente = $this->criarCliente();
        $categoria = $this->criarCategoria();
        $nota = $this->criarNota($cliente);

        $conta = app(CriarContaReceber::class)->execute([
            'nota_id' => $nota->id,
            'categoria_financeira_id' => $categoria->id,
            'descricao' => 'Conta vinculada à nota',
            'valor_original' => 250,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(10)->toDateString(),
        ]);

        $this->assertSame($cliente->id, $conta->cliente_id);
        $this->assertSame($nota->id, $conta->nota_id);
    }

    public function test_nao_permite_cliente_diferente_da_nota(): void
    {
        $clienteNota = $this->criarCliente();
        $outroCliente = $this->criarCliente();
        $categoria = $this->criarCategoria();
        $nota = $this->criarNota($clienteNota);

        $this->expectException(ValidationException::class);

        app(CriarContaReceber::class)->execute([
            'nota_id' => $nota->id,
            'cliente_id' => $outroCliente->id,
            'categoria_financeira_id' => $categoria->id,
            'descricao' => 'Conta inválida',
            'valor_original' => 100,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(10)->toDateString(),
        ]);
    }

    public function test_nao_permite_conta_sem_cliente_e_sem_nota(): void
    {
        $this->expectException(ValidationException::class);

        app(CriarContaReceber::class)->execute([
            'descricao' => 'Conta inválida',
            'valor_original' => 100,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(10)->toDateString(),
        ]);
    }

    public function test_nao_permite_valor_original_zero(): void
    {
        $this->expectException(ValidationException::class);

        $this->criarConta([
            'valor_original' => 0,
        ]);
    }

    public function test_nao_permite_valor_original_negativo(): void
    {
        $this->expectException(ValidationException::class);

        $this->criarConta([
            'valor_original' => -10,
        ]);
    }

    public function test_nao_permite_desconto_negativo(): void
    {
        $this->expectException(ValidationException::class);

        $this->criarConta([
            'desconto' => -1,
        ]);
    }

    public function test_nao_permite_juros_negativos(): void
    {
        $this->expectException(ValidationException::class);

        $this->criarConta([
            'juros' => -1,
        ]);
    }

    public function test_nao_permite_multa_negativos(): void
    {
        $this->expectException(ValidationException::class);

        $this->criarConta([
            'multa' => -1,
        ]);
    }

    public function test_nao_permite_valor_final_zero_ou_negativo(): void
    {
        $this->expectException(ValidationException::class);

        $this->criarConta([
            'valor_original' => 100,
            'desconto' => 100,
        ]);
    }

    public function test_calcula_valor_devido_com_desconto_juros_e_multa(): void
    {
        $conta = $this->criarConta([
            'valor_original' => 100,
            'desconto' => 10,
            'juros' => 5,
            'multa' => 2,
        ]);

        $this->assertSame(100.0, (float) $conta->valor_original);
        $this->assertSame(10.0, (float) $conta->desconto);
        $this->assertSame(5.0, (float) $conta->juros);
        $this->assertSame(2.0, (float) $conta->multa);

        $valorDevido =
            (float) $conta->valor_original
            - (float) $conta->desconto
            + (float) $conta->juros
            + (float) $conta->multa;

        $this->assertSame(97.0, $valorDevido);
    }

    public function test_nao_cria_duas_contas_para_a_mesma_nota(): void
    {
        $cliente = $this->criarCliente();
        $categoria = $this->criarCategoria();
        $nota = $this->criarNota($cliente);

        app(CriarContaReceber::class)->execute([
            'nota_id' => $nota->id,
            'categoria_financeira_id' => $categoria->id,
            'descricao' => 'Primeira conta',
            'valor_original' => 100,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(10)->toDateString(),
        ]);

        $this->expectException(ValidationException::class);

        app(CriarContaReceber::class)->execute([
            'nota_id' => $nota->id,
            'categoria_financeira_id' => $categoria->id,
            'descricao' => 'Segunda conta',
            'valor_original' => 200,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(10)->toDateString(),
        ]);
    }

    public function test_atualiza_conta_sem_recebimentos(): void
    {
        $conta = $this->criarConta();

        app(AtualizarContaReceber::class)->execute($conta, [
            'cliente_id' => $conta->cliente_id,
            'categoria_financeira_id' => $conta->categoria_financeira_id,
            'descricao' => 'Descrição atualizada',
            'valor_original' => 150,
            'desconto' => 10,
            'juros' => 5,
            'multa' => 2,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(20)->toDateString(),
        ]);

        $conta->refresh();

        $this->assertSame('Descrição atualizada', $conta->descricao);
        $this->assertSame('150.00', $conta->valor_original);
        $this->assertSame('10.00', $conta->desconto);
        $this->assertSame('5.00', $conta->juros);
        $this->assertSame('2.00', $conta->multa);
        $this->assertSame('aberta', $conta->status);
    }

    public function test_atualizacao_rejeita_nota_com_outra_conta(): void
    {
        $cliente = $this->criarCliente();
        $categoria = $this->criarCategoria();

        $nota = $this->criarNota($cliente);

        app(CriarContaReceber::class)->execute([
            'nota_id' => $nota->id,
            'categoria_financeira_id' => $categoria->id,
            'descricao' => 'Conta existente',
            'valor_original' => 100,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(10)->toDateString(),
        ]);

        $outraConta = $this->criarConta();

        $this->expectException(ValidationException::class);

        app(AtualizarContaReceber::class)->execute($outraConta, [
            'nota_id' => $nota->id,
            'categoria_financeira_id' => $categoria->id,
            'descricao' => 'Tentativa inválida',
            'valor_original' => 100,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(10)->toDateString(),
        ]);
    }

    public function test_nao_permite_atualizar_conta_com_recebimento(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 50,
            'data_pagamento' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(AtualizarContaReceber::class)->execute($conta, [
            'cliente_id' => $conta->cliente_id,
            'categoria_financeira_id' => $conta->categoria_financeira_id,
            'descricao' => 'Tentativa inválida',
            'valor_original' => 150,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(20)->toDateString(),
        ]);
    }

    public function test_nao_permite_atualizar_conta_quitada(): void
    {
        $conta = $this->criarConta();
        $conta->update([
            'status' => 'quitada',
        ]);

        $this->expectException(ValidationException::class);

        app(AtualizarContaReceber::class)->execute($conta, [
            'cliente_id' => $conta->cliente_id,
            'categoria_financeira_id' => $conta->categoria_financeira_id,
            'descricao' => 'Tentativa inválida',
            'valor_original' => 150,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(20)->toDateString(),
        ]);
    }

    public function test_nao_permite_atualizar_conta_cancelada(): void
    {
        $conta = $this->criarConta();
        $conta->update([
            'status' => 'cancelada',
        ]);

        $this->expectException(ValidationException::class);

        app(AtualizarContaReceber::class)->execute($conta, [
            'cliente_id' => $conta->cliente_id,
            'categoria_financeira_id' => $conta->categoria_financeira_id,
            'descricao' => 'Tentativa inválida',
            'valor_original' => 150,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(20)->toDateString(),
        ]);
    }

    public function test_registra_recebimento_parcial(): void
    {
        $conta = $this->criarConta([
            'valor_original' => 100,
        ]);

        $formaPagamento = $this->criarFormaPagamento();

        $recebimento = app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 40,
            'data_pagamento' => now(),
        ]);

        $conta->refresh();

        $this->assertInstanceOf(Recebimento::class, $recebimento);
        $this->assertSame('parcial', $conta->status);
        $this->assertNull($conta->data_quitacao);
    }

    public function test_recebimento_integral_quita_conta(): void
    {
        $conta = $this->criarConta([
            'valor_original' => 100,
        ]);

        $formaPagamento = $this->criarFormaPagamento();
        $dataPagamento = now();

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 100,
            'data_pagamento' => $dataPagamento,
        ]);

        $conta->refresh();

        $this->assertSame('quitada', $conta->status);
        $this->assertNotNull($conta->data_quitacao);
    }

    public function test_multiplos_recebimentos_quitam_conta(): void
    {
        $conta = $this->criarConta([
            'valor_original' => 100,
        ]);

        $formaPagamento = $this->criarFormaPagamento();

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 40,
            'data_pagamento' => now(),
        ]);

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 60,
            'data_pagamento' => now(),
        ]);

        $conta->refresh();

        $this->assertSame('quitada', $conta->status);
        $this->assertNotNull($conta->data_quitacao);
    }

    public function test_nao_permite_recebimento_acima_do_saldo(): void
    {
        $conta = $this->criarConta([
            'valor_original' => 100,
        ]);

        $formaPagamento = $this->criarFormaPagamento();

        $this->expectException(ValidationException::class);

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 101,
            'data_pagamento' => now(),
        ]);
    }

    public function test_nao_permite_recebimento_zero(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        $this->expectException(ValidationException::class);

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 0,
            'data_pagamento' => now(),
        ]);
    }

    public function test_nao_permite_recebimento_negativo(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        $this->expectException(ValidationException::class);

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => -10,
            'data_pagamento' => now(),
        ]);
    }

    public function test_nao_permite_recebimento_em_conta_cancelada(): void
    {
        $conta = $this->criarConta();

        $conta->update([
            'status' => 'cancelada',
        ]);

        $formaPagamento = $this->criarFormaPagamento();

        $this->expectException(ValidationException::class);

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 50,
            'data_pagamento' => now(),
        ]);
    }

    public function test_nao_permite_recebimento_em_conta_quitada(): void
    {
        $conta = $this->criarConta();

        $conta->update([
            'status' => 'quitada',
        ]);

        $formaPagamento = $this->criarFormaPagamento();

        $this->expectException(ValidationException::class);

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 50,
            'data_pagamento' => now(),
        ]);
    }

    public function test_nao_permite_forma_de_pagamento_inexistente(): void
    {
        $conta = $this->criarConta();

        $this->expectException(ValidationException::class);

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => 999999,
            'valor' => 50,
            'data_pagamento' => now(),
        ]);
    }

    public function test_nao_permite_forma_de_pagamento_inativa(): void
    {
        $conta = $this->criarConta();

        $formaPagamento = $this->criarFormaPagamento();

        $formaPagamento->update([
            'ativo' => false,
        ]);

        $this->expectException(ValidationException::class);

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 50,
            'data_pagamento' => now(),
        ]);
    }

    public function test_recebimento_com_desconto_quita_pelo_valor_devido(): void
    {
        $conta = $this->criarConta([
            'valor_original' => 100,
            'desconto' => 10,
        ]);

        $formaPagamento = $this->criarFormaPagamento();

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 90,
            'data_pagamento' => now(),
        ]);

        $conta->refresh();

        $this->assertSame('quitada', $conta->status);
    }

    public function test_recebimento_considera_juros_e_multa(): void
    {
        $conta = $this->criarConta([
            'valor_original' => 100,
            'juros' => 5,
            'multa' => 2,
        ]);

        $formaPagamento = $this->criarFormaPagamento();

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 107,
            'data_pagamento' => now(),
        ]);

        $conta->refresh();

        $this->assertSame('quitada', $conta->status);
    }

    public function test_recebimento_preserva_observacoes(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        $recebimento = app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 50,
            'data_pagamento' => now(),
            'observacoes' => 'Pagamento em dinheiro.',
        ]);

        $this->assertSame(
            'Pagamento em dinheiro.',
            $recebimento->observacoes
        );
    }

    public function test_recebimento_nao_e_criado_quando_falha_validacao(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        try {
            app(RegistrarRecebimento::class)->execute([
                'conta_receber_id' => $conta->id,
                'forma_pagamento_id' => $formaPagamento->id,
                'valor' => 0,
                'data_pagamento' => now(),
            ]);
        } catch (ValidationException) {
        }

        $this->assertDatabaseCount('recebimentos', 0);
        $this->assertSame('aberta', $conta->fresh()->status);
    }

    public function test_cancelamento_de_conta_preserva_historico(): void
    {
        $conta = $this->criarConta([
            'observacoes' => 'Observação original.',
        ]);

        $conta->update([
            'status' => 'cancelada',
            'observacoes' => "Observação original.\nCancelamento: Cliente desistiu.",
        ]);

        $conta->refresh();

        $this->assertSame('cancelada', $conta->status);
        $this->assertStringContainsString(
            'Cliente desistiu.',
            $conta->observacoes
        );

        $this->assertDatabaseHas('contas_receber', [
            'id' => $conta->id,
            'status' => 'cancelada',
        ]);
    }

    public function test_conta_pode_ser_atualizada_quando_esta_vencida_e_nao_tem_recebimentos(): void
    {
        $conta = $this->criarConta([
            'data_vencimento' => now()->subDays(5)->toDateString(),
        ]);

        $this->assertTrue($conta->fresh()->estaVencida());

        app(AtualizarContaReceber::class)->execute($conta, [
            'cliente_id' => $conta->cliente_id,
            'categoria_financeira_id' => $conta->categoria_financeira_id,
            'descricao' => 'Conta vencida atualizada',
            'valor_original' => 200,
            'desconto' => 0,
            'juros' => 0,
            'multa' => 0,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(10)->toDateString(),
        ]);

        $conta->refresh();

        $this->assertSame(
            'Conta vencida atualizada',
            $conta->descricao
        );

        $this->assertSame(
            'aberta',
            $conta->status
        );
    }

    public function test_estorna_recebimento_e_reabre_conta(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        $recebimento = app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 100,
            'data_pagamento' => now(),
        ]);

        $this->assertSame(
            'quitada',
            $conta->fresh()->status
        );

        app(EstornarRecebimento::class)->execute(
            $conta,
            $recebimento,
            'Pagamento lançado incorretamente.'
        );

        $recebimento->refresh();
        $conta->refresh();

        $this->assertTrue(
            $recebimento->estaEstornado()
        );

        $this->assertNotNull(
            $recebimento->estornado_em
        );

        $this->assertSame(
            'Pagamento lançado incorretamente.',
            $recebimento->motivo_estorno
        );

        $this->assertSame(
            'aberta',
            $conta->status
        );

        $this->assertNull(
            $conta->data_quitacao
        );
    }

    public function test_permite_novo_recebimento_apos_estorno(): void
    {
        $conta = $this->criarConta([
            'valor_original' => 100,
        ]);

        $formaPagamento = $this->criarFormaPagamento();

        $primeiroRecebimento = app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 100,
            'data_pagamento' => now(),
        ]);

        $this->assertSame(
            'quitada',
            $conta->fresh()->status
        );

        app(EstornarRecebimento::class)->execute(
            $conta,
            $primeiroRecebimento,
            'Estorno para permitir novo recebimento.'
        );

        $conta->refresh();
        $primeiroRecebimento->refresh();

        $this->assertTrue(
            $primeiroRecebimento->estaEstornado()
        );

        $this->assertSame(
            'aberta',
            $conta->status
        );

        $segundoRecebimento = app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 100,
            'data_pagamento' => now(),
        ]);

        $conta->refresh();

        $this->assertInstanceOf(
            Recebimento::class,
            $segundoRecebimento
        );

        $this->assertNotSame(
            $primeiroRecebimento->id,
            $segundoRecebimento->id
        );

        $this->assertTrue(
            $primeiroRecebimento->fresh()->estaEstornado()
        );

        $this->assertFalse(
            $segundoRecebimento->fresh()->estaEstornado()
        );

        $this->assertSame(
            'quitada',
            $conta->status
        );

        $this->assertSame(
            '100.00',
            number_format(
                (float) $conta->recebimentos()
                    ->whereNull('estornado_em')
                    ->sum('valor'),
                2,
                '.',
                ''
            )
        );

        $this->assertDatabaseCount(
            'recebimentos',
            2
        );
    }

    public function test_estorno_de_recebimento_parcial_recalcula_saldo(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        $primeiro = app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 40,
            'data_pagamento' => now(),
        ]);

        $segundo = app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 60,
            'data_pagamento' => now(),
        ]);

        $this->assertSame(
            'quitada',
            $conta->fresh()->status
        );

        app(EstornarRecebimento::class)->execute(
            $segundo
                ->contaReceber()
                ->firstOrFail(),
            $segundo,
            'Segundo recebimento estornado.'
        );

        $conta->refresh();
        $segundo->refresh();

        $this->assertSame(
            'parcial',
            $conta->status
        );

        $this->assertSame(
            '40.00',
            number_format(
                (float) $conta->recebimentos()
                    ->whereNull('estornado_em')
                    ->sum('valor'),
                2,
                '.',
                ''
            )
        );

        $this->assertTrue(
            $segundo->estaEstornado()
        );

        $this->assertFalse(
            $primeiro->fresh()->estaEstornado()
        );
    }

    public function test_nao_permite_estornar_recebimento_duas_vezes(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        $recebimento = app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 50,
            'data_pagamento' => now(),
        ]);

        app(EstornarRecebimento::class)->execute(
            $conta,
            $recebimento,
            'Primeiro estorno.'
        );

        $this->expectException(\InvalidArgumentException::class);

        app(EstornarRecebimento::class)->execute(
            $conta,
            $recebimento,
            'Segundo estorno.'
        );
    }

    public function test_nao_permite_estorno_sem_motivo(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        $recebimento = app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 50,
            'data_pagamento' => now(),
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(EstornarRecebimento::class)->execute(
            $conta,
            $recebimento,
            '   '
        );
    }

    public function test_nao_permite_estornar_recebimento_de_conta_cancelada(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        $recebimento = app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 50,
            'data_pagamento' => now(),
        ]);

        $conta->update([
            'status' => 'cancelada',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(EstornarRecebimento::class)->execute(
            $conta,
            $recebimento,
            'Tentativa inválida.'
        );
    }

    public function test_nao_permite_estornar_recebimento_de_outra_conta(): void
    {
        $conta = $this->criarConta();
        $outraConta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        $recebimento = app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $outraConta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 50,
            'data_pagamento' => now(),
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(EstornarRecebimento::class)->execute(
            $conta,
            $recebimento,
            'Tentativa inválida.'
        );
    }
}
