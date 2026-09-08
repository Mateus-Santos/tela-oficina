<?php

namespace Tests\Feature\Financeiro;

use App\Actions\Financeiro\AtualizarContaReceber;
use App\Actions\Financeiro\CriarContaReceber;
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
        $cpf = str_pad(
            (string) random_int(1, 99999999999),
            11,
            '0',
            STR_PAD_LEFT
        );

        $pessoaId = DB::table('pessoas')->insertGetId([
            'nome' => 'Cliente Teste',
            'cpf' => $cpf,
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

    private function criarCategoria(): CategoriaFinanceira
    {
        return CategoriaFinanceira::create([
            'nome' => 'VENDAS E SERVIÇOS',
            'tipo' => 'entrada',
            'ativo' => true,
        ]);
    }

    private function criarFormaPagamento(
        string $nome = 'PIX Teste'
    ): FormaPagamento {
        return FormaPagamento::create([
            'nome' => $nome . ' ' . uniqid(),
            'ativo' => true,
        ]);
    }

    private function criarNota(Cliente $cliente): Nota
    {
        return Nota::create([
            'cliente_id' => $cliente->id,
            'tipo' => 'Servico',
            'status' => 'Aberto',
            'descricao' => 'Nota de teste',
            'valor_total' => 100,
        ]);
    }

    private function criarConta(
        ?Cliente $cliente = null,
        array $dados = []
    ): ContaReceber {
        $cliente ??= $this->criarCliente();

        return app(CriarContaReceber::class)->execute(array_merge([
            'cliente_id' => $cliente->id,
            'descricao' => 'Conta a receber de teste',
            'valor_original' => 100,
            'desconto' => 0,
            'juros' => 0,
            'multa' => 0,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()->addDays(30)->toDateString(),
            'observacoes' => null,
        ], $dados));
    }

    public function test_cria_conta_a_receber(): void
    {
        $cliente = $this->criarCliente();

        $conta = $this->criarConta($cliente);

        $this->assertDatabaseHas('contas_receber', [
            'id' => $conta->id,
            'cliente_id' => $cliente->id,
            'descricao' => 'Conta a receber de teste',
            'valor_original' => 100,
            'status' => 'aberta',
        ]);
    }

    public function test_cria_conta_com_nota_e_define_cliente_pela_nota(): void
    {
        $cliente = $this->criarCliente();
        $nota = $this->criarNota($cliente);

        $conta = $this->criarConta(
            $cliente,
            [
                'nota_id' => $nota->id,
                'cliente_id' => null,
            ]
        );

        $this->assertSame(
            $cliente->id,
            $conta->cliente_id
        );

        $this->assertSame(
            $nota->id,
            $conta->nota_id
        );
    }

    public function test_nao_permite_cliente_diferente_da_nota(): void
    {
        $clienteNota = $this->criarCliente();
        $outroCliente = $this->criarCliente();
        $nota = $this->criarNota($clienteNota);

        $this->expectException(ValidationException::class);

        app(CriarContaReceber::class)->execute([
            'nota_id' => $nota->id,
            'cliente_id' => $outroCliente->id,
            'descricao' => 'Conta inválida',
            'valor_original' => 100,
            'data_vencimento' => now()->addDays(30)->toDateString(),
        ]);
    }

    public function test_nao_permite_conta_sem_cliente_e_sem_nota(): void
    {
        $this->expectException(ValidationException::class);

        app(CriarContaReceber::class)->execute([
            'descricao' => 'Conta sem cliente',
            'valor_original' => 100,
            'data_vencimento' => now()->addDays(30)->toDateString(),
        ]);
    }

    public function test_nao_permite_valor_original_zero(): void
    {
        $this->expectException(ValidationException::class);

        $this->criarConta(
            null,
            [
                'valor_original' => 0,
            ]
        );
    }

    public function test_nao_permite_valor_original_negativo(): void
    {
        $this->expectException(ValidationException::class);

        $this->criarConta(
            null,
            [
                'valor_original' => -100,
            ]
        );
    }

    public function test_nao_permite_desconto_negativo(): void
    {
        $this->expectException(ValidationException::class);

        $this->criarConta(
            null,
            [
                'desconto' => -1,
            ]
        );
    }

    public function test_nao_permite_juros_negativos(): void
    {
        $this->expectException(ValidationException::class);

        $this->criarConta(
            null,
            [
                'juros' => -1,
            ]
        );
    }

    public function test_nao_permite_multa_negativa(): void
    {
        $this->expectException(ValidationException::class);

        $this->criarConta(
            null,
            [
                'multa' => -1,
            ]
        );
    }

    public function test_nao_permite_valor_final_zero_ou_negativo(): void
    {
        $this->expectException(ValidationException::class);

        $this->criarConta(
            null,
            [
                'valor_original' => 100,
                'desconto' => 100,
            ]
        );
    }

    public function test_calcula_valor_devido_com_desconto_juros_e_multa(): void
    {
        $conta = $this->criarConta(
            null,
            [
                'valor_original' => 100,
                'desconto' => 10,
                'juros' => 5,
                'multa' => 2,
            ]
        );

        $this->assertSame(
            '100.00',
            $conta->valor_original
        );

        $this->assertSame(
            '10.00',
            $conta->desconto
        );

        $this->assertSame(
            '5.00',
            $conta->juros
        );

        $this->assertSame(
            '2.00',
            $conta->multa
        );
    }

    public function test_nao_cria_duas_contas_para_a_mesma_nota(): void
    {
        $cliente = $this->criarCliente();
        $nota = $this->criarNota($cliente);

        $this->criarConta(
            $cliente,
            [
                'nota_id' => $nota->id,
            ]
        );

        $this->expectException(ValidationException::class);

        $this->criarConta(
            $cliente,
            [
                'nota_id' => $nota->id,
            ]
        );
    }

    public function test_atualiza_conta_sem_recebimentos(): void
    {
        $conta = $this->criarConta();

        $contaAtualizada = app(AtualizarContaReceber::class)->execute(
            $conta,
            [
                'cliente_id' => $conta->cliente_id,
                'descricao' => 'Descrição atualizada',
                'valor_original' => 150,
                'desconto' => 10,
                'juros' => 5,
                'multa' => 2,
                'data_emissao' => now()->toDateString(),
                'data_vencimento' => now()->addDays(45)->toDateString(),
                'observacoes' => 'Atualização de teste.',
            ]
        );

        $this->assertSame(
            'Descrição atualizada',
            $contaAtualizada->descricao
        );

        $this->assertSame(
            '150.00',
            $contaAtualizada->valor_original
        );

        $this->assertSame(
            'aberta',
            $contaAtualizada->status
        );
    }

    public function test_atualizacao_rejeita_nota_com_outra_conta(): void
    {
        $cliente = $this->criarCliente();
        $nota = $this->criarNota($cliente);

        $primeira = $this->criarConta(
            $cliente,
            [
                'nota_id' => $nota->id,
            ]
        );

        $segunda = $this->criarConta($cliente);

        $this->expectException(ValidationException::class);

        app(AtualizarContaReceber::class)->execute(
            $segunda,
            [
                'cliente_id' => $cliente->id,
                'nota_id' => $nota->id,
                'descricao' => 'Tentativa duplicada',
                'valor_original' => 100,
                'data_vencimento' => now()->addDays(30)->toDateString(),
            ]
        );

        $this->assertNotNull($primeira->id);
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

        app(AtualizarContaReceber::class)->execute(
            $conta,
            [
                'cliente_id' => $conta->cliente_id,
                'descricao' => 'Não deveria alterar',
                'valor_original' => 200,
                'data_vencimento' => now()->addDays(30)->toDateString(),
            ]
        );
    }

    public function test_nao_permite_atualizar_conta_quitada(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 100,
            'data_pagamento' => now(),
        ]);

        $this->assertSame(
            'quitada',
            $conta->fresh()->status
        );

        $this->expectException(ValidationException::class);

        app(AtualizarContaReceber::class)->execute(
            $conta,
            [
                'cliente_id' => $conta->cliente_id,
                'descricao' => 'Não deveria alterar',
                'valor_original' => 200,
                'data_vencimento' => now()->addDays(30)->toDateString(),
            ]
        );
    }

    public function test_nao_permite_atualizar_conta_cancelada(): void
    {
        $conta = $this->criarConta();

        $conta->update([
            'status' => 'cancelada',
        ]);

        $this->expectException(ValidationException::class);

        app(AtualizarContaReceber::class)->execute(
            $conta,
            [
                'cliente_id' => $conta->cliente_id,
                'descricao' => 'Não deveria alterar',
                'valor_original' => 200,
                'data_vencimento' => now()->addDays(30)->toDateString(),
            ]
        );
    }

    public function test_registra_recebimento_parcial(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        $recebimento = app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 40,
            'data_pagamento' => now(),
        ]);

        $this->assertInstanceOf(
            Recebimento::class,
            $recebimento
        );

        $this->assertDatabaseHas('recebimentos', [
            'id' => $recebimento->id,
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 40,
        ]);

        $this->assertSame(
            'parcial',
            $conta->fresh()->status
        );
    }

    public function test_recebimento_integral_quita_conta(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();
        $dataPagamento = now();

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 100,
            'data_pagamento' => $dataPagamento,
        ]);

        $conta->refresh();

        $this->assertSame(
            'quitada',
            $conta->status
        );

        $this->assertNotNull(
            $conta->data_quitacao
        );
    }

    public function test_multiplos_recebimentos_quitam_conta(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 30,
            'data_pagamento' => now(),
        ]);

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 70,
            'data_pagamento' => now(),
        ]);

        $conta->refresh();

        $this->assertSame(
            'quitada',
            $conta->status
        );

        $this->assertSame(
            2,
            $conta->recebimentos()->count()
        );
    }

    public function test_nao_permite_recebimento_acima_do_saldo(): void
    {
        $conta = $this->criarConta();
        $formaPagamento = $this->criarFormaPagamento();

        $this->expectException(ValidationException::class);

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 100.01,
            'data_pagamento' => now(),
        ]);

        $this->assertDatabaseCount(
            'recebimentos',
            0
        );

        $this->assertSame(
            'aberta',
            $conta->fresh()->status
        );
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
        $formaPagamento = $this->criarFormaPagamento();

        $conta->update([
            'status' => 'cancelada',
        ]);

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
        $formaPagamento = $this->criarFormaPagamento();

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 100,
            'data_pagamento' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 1,
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
        $conta = $this->criarConta(
            null,
            [
                'valor_original' => 100,
                'desconto' => 10,
            ]
        );

        $formaPagamento = $this->criarFormaPagamento();

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 90,
            'data_pagamento' => now(),
        ]);

        $this->assertSame(
            'quitada',
            $conta->fresh()->status
        );
    }

    public function test_recebimento_considera_juros_e_multa(): void
    {
        $conta = $this->criarConta(
            null,
            [
                'valor_original' => 100,
                'juros' => 5,
                'multa' => 2,
            ]
        );

        $formaPagamento = $this->criarFormaPagamento();

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 106,
            'data_pagamento' => now(),
        ]);

        $this->assertSame(
            'parcial',
            $conta->fresh()->status
        );

        app(RegistrarRecebimento::class)->execute([
            'conta_receber_id' => $conta->id,
            'forma_pagamento_id' => $formaPagamento->id,
            'valor' => 1,
            'data_pagamento' => now(),
        ]);

        $this->assertSame(
            'quitada',
            $conta->fresh()->status
        );
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
            'observacoes' => 'Pagamento realizado via teste.',
        ]);

        $this->assertSame(
            'Pagamento realizado via teste.',
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
                'valor' => 150,
                'data_pagamento' => now(),
            ]);

            $this->fail('Era esperado ValidationException.');
        } catch (ValidationException) {
            //
        }

        $this->assertDatabaseCount(
            'recebimentos',
            0
        );

        $this->assertSame(
            'aberta',
            $conta->fresh()->status
        );
    }

    public function test_cancelamento_de_conta_preserva_historico(): void
    {
        $conta = $this->criarConta();

        $id = $conta->id;
        $clienteId = $conta->cliente_id;

        $conta->update([
            'status' => 'cancelada',
        ]);

        $conta->refresh();

        $this->assertSame(
            $id,
            $conta->id
        );

        $this->assertSame(
            $clienteId,
            $conta->cliente_id
        );

        $this->assertSame(
            'cancelada',
            $conta->status
        );

        $this->assertDatabaseHas('contas_receber', [
            'id' => $id,
            'cliente_id' => $clienteId,
            'status' => 'cancelada',
        ]);
    }

    public function test_conta_pode_ser_atualizada_quando_esta_vencida_e_nao_tem_recebimentos(): void
    {
        $conta = $this->criarConta(
            null,
            [
                'data_vencimento' => now()->subDay()->toDateString(),
            ]
        );

        $conta->update([
            'status' => 'vencida',
        ]);

        $atualizada = app(AtualizarContaReceber::class)->execute(
            $conta,
            [
                'cliente_id' => $conta->cliente_id,
                'descricao' => 'Conta vencida atualizada',
                'valor_original' => 120,
                'desconto' => 0,
                'juros' => 0,
                'multa' => 0,
                'data_emissao' => now()->toDateString(),
                'data_vencimento' => now()->addDays(15)->toDateString(),
            ]
        );

        $this->assertSame(
            'aberta',
            $atualizada->status
        );
    }
}
