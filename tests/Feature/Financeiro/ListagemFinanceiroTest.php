<?php

namespace Tests\Feature\Financeiro;

use App\Models\CategoriaFinanceira;
use App\Models\Cliente;
use App\Models\ContaPagar;
use App\Models\ContaReceber;
use App\Models\FormaPagamento;
use App\Models\Fornecedor;
use App\Models\PagamentoContaPagar;
use App\Models\ParcelaContaPagar;
use App\Models\ParcelaContaReceber;
use App\Models\Recebimento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListagemFinanceiroTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Fornecedor $fornecedor;

    private Cliente $cliente;

    private CategoriaFinanceira $categoriaSaida;

    private CategoriaFinanceira $categoriaEntrada;

    private FormaPagamento $formaPagamento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => true,
        ]);

        $fornecedorId = DB::table('fornecedores')
            ->insertGetId([
                'nome' => 'Fornecedor Agenda',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $this->fornecedor = Fornecedor::findOrFail(
            $fornecedorId
        );

        $pessoaId = DB::table('pessoas')
            ->insertGetId([
                'nome' => 'Cliente Agenda',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $this->cliente = Cliente::create([
            'pessoa_id' => $pessoaId,
        ]);

        $this->categoriaSaida =
            CategoriaFinanceira::firstOrCreate(
                [
                    'nome' => 'Despesas Agenda',
                    'tipo' => 'saida',
                ],
                [
                    'ativo' => true,
                ]
            );

        $this->categoriaEntrada =
            CategoriaFinanceira::firstOrCreate(
                [
                    'nome' => 'Receitas Agenda',
                    'tipo' => 'entrada',
                ],
                [
                    'ativo' => true,
                ]
            );

        $this->formaPagamento =
            FormaPagamento::create([
                'nome' => 'Dinheiro Agenda',
                'ativo' => true,
            ]);
    }

    private function criarContaPagar(
        string $status = 'aberta',
        float $valor = 100
    ): ContaPagar {
        return ContaPagar::create([
            'compra_id' => null,
            'fornecedor_id' => $this->fornecedor->id,
            'nota_id' => null,
            'categoria_financeira_id' =>
                $this->categoriaSaida->id,
            'forma_pagamento_id' =>
                $this->formaPagamento->id,
            'descricao' => 'Conta a pagar agenda '
                . uniqid(),
            'valor' => $valor,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()
                ->addDays(30)
                ->toDateString(),
            'status' => $status,
            'observacoes' => null,
        ]);
    }

    private function criarParcelaPagar(
        ContaPagar $conta,
        int $numero,
        float $valor,
        string $vencimento
    ): ParcelaContaPagar {
        return ParcelaContaPagar::create([
            'conta_pagar_id' => $conta->id,
            'numero' => $numero,
            'valor' => $valor,
            'data_vencimento' => $vencimento,
        ]);
    }

    private function pagarParcela(
        ContaPagar $conta,
        ParcelaContaPagar $parcela,
        ?float $valor = null
    ): PagamentoContaPagar {
        return PagamentoContaPagar::create([
            'conta_pagar_id' => $conta->id,
            'parcela_conta_pagar_id' => $parcela->id,
            'valor' => $valor ?? (float) $parcela->valor,
            'data_pagamento' => now()->toDateString(),
            'forma_pagamento' => 'Dinheiro',
            'forma_pagamento_id' =>
                $this->formaPagamento->id,
            'observacoes' => null,
            'estornado_em' => null,
            'motivo_estorno' => null,
        ]);
    }

    private function criarContaReceber(
        string $status = 'aberta',
        float $valor = 100
    ): ContaReceber {
        return ContaReceber::create([
            'cliente_id' => $this->cliente->id,
            'nota_id' => null,
            'categoria_financeira_id' =>
                $this->categoriaEntrada->id,
            'descricao' => 'Conta a receber agenda '
                . uniqid(),
            'valor_original' => $valor,
            'desconto' => 0,
            'juros' => 0,
            'multa' => 0,
            'data_emissao' => now()->toDateString(),
            'data_vencimento' => now()
                ->addDays(30)
                ->toDateString(),
            'data_quitacao' => $status === 'quitada'
                ? now()->toDateString()
                : null,
            'status' => $status,
            'observacoes' => null,
        ]);
    }

    private function criarParcelaReceber(
        ContaReceber $conta,
        int $numero,
        float $valor,
        string $vencimento
    ): ParcelaContaReceber {
        return ParcelaContaReceber::create([
            'conta_receber_id' => $conta->id,
            'numero' => $numero,
            'valor' => $valor,
            'data_vencimento' => $vencimento,
        ]);
    }

    private function receberParcela(
        ContaReceber $conta,
        ParcelaContaReceber $parcela,
        ?float $valor = null
    ): Recebimento {
        return Recebimento::create([
            'conta_receber_id' => $conta->id,
            'parcela_conta_receber_id' => $parcela->id,
            'forma_pagamento_id' =>
                $this->formaPagamento->id,
            'valor' => $valor ?? (float) $parcela->valor,
            'data_pagamento' => now(),
            'usuario_id' => $this->admin->id,
            'observacoes' => null,
            'estornado_em' => null,
            'motivo_estorno' => null,
        ]);
    }

    private function assertPaginatorIds(
        mixed $response,
        string $chave,
        array $esperados
    ): void {
        sort($esperados);

        $response->assertOk();

        $response->assertViewHas(
            $chave,
            function ($paginator) use ($esperados) {
                $atuais = collect(
                    $paginator->items()
                )
                    ->pluck('id')
                    ->sort()
                    ->values()
                    ->all();

                return $atuais === array_values(
                    $esperados
                );
            }
        );
    }

    public function test_contas_pagar_padrao_mostra_apenas_parcelas_pendentes(): void
    {
        $conta = $this->criarContaPagar(
            'parcialmente_paga',
            500
        );

        $parcelas = collect();

        for ($numero = 1; $numero <= 5; $numero++) {
            $parcelas->push(
                $this->criarParcelaPagar(
                    $conta,
                    $numero,
                    100,
                    now()
                        ->addDays($numero)
                        ->toDateString()
                )
            );
        }

        $this->pagarParcela(
            $conta,
            $parcelas[0]
        );

        $this->pagarParcela(
            $conta,
            $parcelas[1]
        );

        $this->pagarParcela(
            $conta,
            $parcelas[2]
        );

        $cancelada = $this->criarContaPagar(
            'cancelada',
            100
        );

        $this->criarParcelaPagar(
            $cancelada,
            1,
            100,
            now()->addDay()->toDateString()
        );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route('contas-pagar.index')
            );

        $this->assertPaginatorIds(
            $response,
            'parcelasPagar',
            [
                $parcelas[3]->id,
                $parcelas[4]->id,
            ]
        );
    }

    public function test_filtro_paga_mostra_parcela_liquidada(): void
    {
        $conta = $this->criarContaPagar(
            'paga'
        );

        $parcela = $this->criarParcelaPagar(
            $conta,
            1,
            100,
            now()->subDay()->toDateString()
        );

        $this->pagarParcela(
            $conta,
            $parcela
        );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'contas-pagar.index',
                    ['status' => 'paga']
                )
            );

        $this->assertPaginatorIds(
            $response,
            'parcelasPagar',
            [$parcela->id]
        );
    }

    public function test_filtro_cancelada_mostra_conta_cancelada(): void
    {
        $conta = $this->criarContaPagar(
            'cancelada'
        );

        $parcela = $this->criarParcelaPagar(
            $conta,
            1,
            100,
            now()->addDay()->toDateString()
        );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'contas-pagar.index',
                    ['status' => 'cancelada']
                )
            );

        $this->assertPaginatorIds(
            $response,
            'parcelasPagar',
            [$parcela->id]
        );
    }

    public function test_filtro_vencida_em_contas_pagar_mostra_so_pendente_vencida(): void
    {
        $conta = $this->criarContaPagar(
            'parcialmente_paga',
            300
        );

        $vencida = $this->criarParcelaPagar(
            $conta,
            1,
            100,
            now()->subDays(5)->toDateString()
        );

        $futura = $this->criarParcelaPagar(
            $conta,
            2,
            100,
            now()->addDays(5)->toDateString()
        );

        $pagaVencida = $this->criarParcelaPagar(
            $conta,
            3,
            100,
            now()->subDays(3)->toDateString()
        );

        $this->pagarParcela(
            $conta,
            $pagaVencida
        );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'contas-pagar.index',
                    ['status' => 'vencida']
                )
            );

        $this->assertPaginatorIds(
            $response,
            'parcelasPagar',
            [$vencida->id]
        );

        $this->assertNotSame(
            $vencida->id,
            $futura->id
        );
    }

    public function test_contas_receber_padrao_mostra_apenas_parcelas_pendentes(): void
    {
        $conta = $this->criarContaReceber(
            'parcial',
            300
        );

        $parcela1 =
            $this->criarParcelaReceber(
                $conta,
                1,
                100,
                now()->addDay()->toDateString()
            );

        $parcela2 =
            $this->criarParcelaReceber(
                $conta,
                2,
                100,
                now()->addDays(2)->toDateString()
            );

        $parcela3 =
            $this->criarParcelaReceber(
                $conta,
                3,
                100,
                now()->addDays(3)->toDateString()
            );

        $this->receberParcela(
            $conta,
            $parcela1
        );

        $cancelada = $this->criarContaReceber(
            'cancelada'
        );

        $this->criarParcelaReceber(
            $cancelada,
            1,
            100,
            now()->addDay()->toDateString()
        );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route('contas-receber.index')
            );

        $this->assertPaginatorIds(
            $response,
            'parcelasReceber',
            [
                $parcela2->id,
                $parcela3->id,
            ]
        );
    }

    public function test_filtro_quitada_mostra_parcela_recebida(): void
    {
        $conta = $this->criarContaReceber(
            'quitada'
        );

        $parcela =
            $this->criarParcelaReceber(
                $conta,
                1,
                100,
                now()->subDay()->toDateString()
            );

        $this->receberParcela(
            $conta,
            $parcela
        );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'contas-receber.index',
                    ['status' => 'quitada']
                )
            );

        $this->assertPaginatorIds(
            $response,
            'parcelasReceber',
            [$parcela->id]
        );
    }

    public function test_filtro_cancelada_mostra_conta_a_receber_cancelada(): void
    {
        $conta = $this->criarContaReceber(
            'cancelada'
        );

        $parcela =
            $this->criarParcelaReceber(
                $conta,
                1,
                100,
                now()->addDay()->toDateString()
            );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'contas-receber.index',
                    ['status' => 'cancelada']
                )
            );

        $this->assertPaginatorIds(
            $response,
            'parcelasReceber',
            [$parcela->id]
        );
    }

    public function test_filtro_vencida_em_contas_receber_mostra_so_pendente_vencida(): void
    {
        $conta = $this->criarContaReceber(
            'parcial',
            300
        );

        $vencida =
            $this->criarParcelaReceber(
                $conta,
                1,
                100,
                now()->subDays(5)->toDateString()
            );

        $this->criarParcelaReceber(
            $conta,
            2,
            100,
            now()->addDays(5)->toDateString()
        );

        $recebidaVencida =
            $this->criarParcelaReceber(
                $conta,
                3,
                100,
                now()->subDays(3)->toDateString()
            );

        $this->receberParcela(
            $conta,
            $recebidaVencida
        );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route(
                    'contas-receber.index',
                    ['status' => 'vencida']
                )
            );

        $this->assertPaginatorIds(
            $response,
            'parcelasReceber',
            [$vencida->id]
        );
    }
}
