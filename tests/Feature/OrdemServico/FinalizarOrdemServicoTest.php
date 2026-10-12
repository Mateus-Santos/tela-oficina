<?php

namespace Tests\Feature\OrdemServico;

use App\Actions\OrdemServico\AlterarEtapaOrdemServico;
use App\Actions\OrdemServico\CriarOrdemServico;
use App\Actions\OrdemServico\FinalizarOrdemServico;
use App\Models\Cliente;
use App\Models\Etapa;
use App\Models\OrdemServico;
use App\Models\Pessoa;
use App\Models\SetorServico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class FinalizarOrdemServicoTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;

    private int $veiculoClienteId;

    private SetorServico $setorServico;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente = $this->criarCliente();

        $this->veiculoClienteId = $this->criarVeiculoCliente(
            $this->cliente
        );

        $this->setorServico = SetorServico::create([
            'setor' => 'Mecânica',
            'nivel' => 1,
        ]);
    }

    private function criarCliente(): Cliente
    {
        $pessoa = Pessoa::create([
            'nome' => 'Cliente Teste',
        ]);

        return Cliente::create([
            'pessoa_id' => $pessoa->id,
            'pontos' => 0,
        ]);
    }

    private function criarVeiculoCliente(
        Cliente $cliente
    ): int {
        $agora = now();

        $montadoraId = DB::table(
            'montadoras'
        )->insertGetId([
            'nome' => 'Montadora Teste',
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        $veiculoId = DB::table(
            'veiculos'
        )->insertGetId([
            'nome' => 'Veículo Teste',
            'montadora_id' => $montadoraId,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        $veiculoClienteId = DB::table(
            'veiculos_clientes'
        )->insertGetId([
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculoId,
            'placa' => 'ABC1D23',
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

    private function criarOrdemServico(): OrdemServico
    {
        return app(
            CriarOrdemServico::class
        )->execute([
            'cliente_id' => $this->cliente->id,
            'veiculo_cliente_id' => $this->veiculoClienteId,
            'setor_servico_id' => $this->setorServico->id,
            'descricao' => 'Serviço de teste',
            'valor' => '150,00',
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

    public function test_finalizar_ordem_servico_altera_status_para_finalizada(): void
    {
        $ordemServico = $this->criarOrdemServico();

        $ordemServico = app(
            FinalizarOrdemServico::class
        )->execute(
            $ordemServico
        );

        $this->assertSame(
            'finalizada',
            $ordemServico->status
        );

        $this->assertDatabaseHas(
            'ordem_servicos',
            [
                'id' => $ordemServico->id,
                'status' => 'finalizada',
            ]
        );
    }

    public function test_finalizar_ordem_servico_move_para_etapa_final(): void
    {
        $ordemServico = $this->criarOrdemServico();

        $etapaFinal = Etapa::query()
            ->paraOrdemServico()
            ->ativas()
            ->finais()
            ->firstOrFail();

        $ordemServico = app(
            FinalizarOrdemServico::class
        )->execute(
            $ordemServico
        );

        $this->assertSame(
            $etapaFinal->id,
            $ordemServico->etapa_id
        );

        $this->assertSame(
            $etapaFinal->nome,
            $ordemServico->etapa->nome
        );
    }

    public function test_finalizar_ordem_servico_registra_historico_da_etapa(): void
    {
        $ordemServico = $this->criarOrdemServico();

        $etapaInicialId =
            $ordemServico->etapa_id;

        $etapaInicialNome =
            $ordemServico->etapa->nome;

        $etapaFinal = Etapa::query()
            ->paraOrdemServico()
            ->ativas()
            ->finais()
            ->firstOrFail();

        app(
            FinalizarOrdemServico::class
        )->execute(
            $ordemServico
        );

        $this->assertDatabaseCount(
            'ordem_servico_etapa_historicos',
            2
        );

        $this->assertDatabaseHas(
            'ordem_servico_etapa_historicos',
            [
                'ordem_servico_id' => $ordemServico->id,
                'etapa_origem_id' => $etapaInicialId,
                'etapa_destino_id' => $etapaFinal->id,
                'etapa_origem_nome' => $etapaInicialNome,
                'etapa_destino_nome' => $etapaFinal->nome,
                'motivo' => 'Movimentação automática pela finalização da Ordem de Serviço.',
            ]
        );
    }

    public function test_finalizar_ordem_servico_preserva_historico_do_fluxo_anterior(): void
    {
        $ordemServico = $this->criarOrdemServico();

        $diagnostico = $this->etapa(
            'diagnostico'
        );

        $ordemServico = app(
            AlterarEtapaOrdemServico::class
        )->execute(
            $ordemServico,
            $diagnostico
        );

        $this->assertSame(
            2,
            $ordemServico
                ->historicoEtapas()
                ->count()
        );

        app(
            FinalizarOrdemServico::class
        )->execute(
            $ordemServico
        );

        $this->assertDatabaseCount(
            'ordem_servico_etapa_historicos',
            3
        );

        $this->assertDatabaseHas(
            'ordem_servico_etapa_historicos',
            [
                'ordem_servico_id' => $ordemServico->id,
                'etapa_origem_nome' => 'Diagnóstico',
                'etapa_destino_nome' => 'Pronto para entrega',
            ]
        );
    }

    public function test_finalizar_ordem_servico_preenche_data_de_fechamento(): void
    {
        $ordemServico = $this->criarOrdemServico();

        $this->assertNull(
            $ordemServico->data_fechamento
        );

        $ordemServico = app(
            FinalizarOrdemServico::class
        )->execute(
            $ordemServico
        );

        $this->assertNotNull(
            $ordemServico->data_fechamento
        );
    }

    public function test_nao_permite_finalizar_ordem_servico_ja_finalizada(): void
    {
        $ordemServico = $this->criarOrdemServico();

        app(
            FinalizarOrdemServico::class
        )->execute(
            $ordemServico
        );

        $ordemServico->refresh();

        $quantidadeHistoricos =
            $ordemServico
                ->historicoEtapas()
                ->count();

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Somente ordens de serviço abertas podem ser finalizadas.'
        );

        try {
            app(
                FinalizarOrdemServico::class
            )->execute(
                $ordemServico
            );
        } finally {
            $this->assertSame(
                $quantidadeHistoricos,
                $ordemServico
                    ->historicoEtapas()
                    ->count()
            );
        }
    }

    public function test_nao_permite_finalizar_ordem_servico_cancelada(): void
    {
        $ordemServico = $this->criarOrdemServico();

        $ordemServico->update([
            'status' => 'cancelada',
            'data_fechamento' => now(),
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Somente ordens de serviço abertas podem ser finalizadas.'
        );

        app(
            FinalizarOrdemServico::class
        )->execute(
            $ordemServico
        );
    }

    public function test_finalizacao_falha_sem_etapa_final_ativa(): void
    {
        $ordemServico = $this->criarOrdemServico();

        Etapa::query()
            ->paraOrdemServico()
            ->finais()
            ->update([
                'ativo' => false,
            ]);

        $etapaAntes =
            $ordemServico->etapa_id;

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'A Ordem de Serviço precisa possuir exatamente uma etapa final ativa configurada.'
        );

        try {
            app(
                FinalizarOrdemServico::class
            )->execute(
                $ordemServico
            );
        } finally {
            $ordemServico->refresh();

            $this->assertSame(
                'aberta',
                $ordemServico->status
            );

            $this->assertSame(
                $etapaAntes,
                $ordemServico->etapa_id
            );

            $this->assertNull(
                $ordemServico->data_fechamento
            );
        }
    }
}
