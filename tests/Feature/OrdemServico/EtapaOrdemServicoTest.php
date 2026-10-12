<?php

namespace Tests\Feature\OrdemServico;

use App\Actions\OrdemServico\AlterarEtapaOrdemServico;
use App\Actions\OrdemServico\CriarOrdemServico;
use App\Models\Cliente;
use App\Models\Etapa;
use App\Models\OrdemServico;
use App\Models\Pessoa;
use App\Models\SetorServico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class EtapaOrdemServicoTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;

    private int $veiculoClienteId;

    private SetorServico $setorServico;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente =
            $this->criarCliente();

        $this->veiculoClienteId =
            $this->criarVeiculoCliente(
                $this->cliente
            );

        $this->setorServico =
            SetorServico::create([
                'setor' => 'Mecânica',

                'nivel' => 1,
            ]);
    }

    private function criarCliente(): Cliente
    {
        $pessoa =
            Pessoa::create([
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
        $agora =
            now();

        $montadoraId =
            DB::table(
                'montadoras'
            )->insertGetId([
                'nome' => 'Montadora Teste',

                'created_at' => $agora,

                'updated_at' => $agora,
            ]);

        $veiculoId =
            DB::table(
                'veiculos'
            )->insertGetId([
                'nome' => 'Veículo Teste',

                'montadora_id' => $montadoraId,

                'created_at' => $agora,

                'updated_at' => $agora,
            ]);

        $veiculoClienteId =
            DB::table(
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

    public function test_ordem_servico_criada_nasce_na_etapa_inicial(): void
    {
        $ordemServico =
            $this->criarOrdemServico();

        $etapaInicial =
            Etapa::query()
                ->paraOrdemServico()
                ->where(
                    'tipo',
                    'inicial'
                )
                ->where(
                    'ativo',
                    true
                )
                ->firstOrFail();

        $this->assertSame(
            $etapaInicial->id,
            $ordemServico->etapa_id
        );

        $this->assertSame(
            'Recepção',
            $ordemServico->etapa->nome
        );

        $this->assertSame(
            'aberta',
            $ordemServico->status
        );
    }

    public function test_criacao_da_ordem_servico_gera_historico_inicial(): void
    {
        $ordemServico =
            $this->criarOrdemServico();

        $this->assertDatabaseCount(
            'ordem_servico_etapa_historicos',
            1
        );

        $this->assertDatabaseHas(
            'ordem_servico_etapa_historicos',
            [
                'ordem_servico_id' => $ordemServico->id,

                'etapa_origem_id' => null,

                'etapa_destino_id' => $ordemServico->etapa_id,

                'etapa_origem_nome' => null,

                'etapa_destino_nome' => 'Recepção',
            ]
        );
    }

    public function test_ordem_servico_aberta_pode_mudar_de_etapa(): void
    {
        $ordemServico =
            $this->criarOrdemServico();

        $diagnostico =
            $this->etapa(
                'diagnostico'
            );

        $ordemServico =
            app(
                AlterarEtapaOrdemServico::class
            )->execute(
                $ordemServico,
                $diagnostico
            );

        $this->assertSame(
            $diagnostico->id,
            $ordemServico->etapa_id
        );

        $this->assertSame(
            'Diagnóstico',
            $ordemServico->etapa->nome
        );
    }

    public function test_mudanca_valida_de_etapa_cria_historico(): void
    {
        $ordemServico =
            $this->criarOrdemServico();

        $etapaInicialId =
            $ordemServico->etapa_id;

        $diagnostico =
            $this->etapa(
                'diagnostico'
            );

        app(
            AlterarEtapaOrdemServico::class
        )->execute(
            $ordemServico,
            $diagnostico
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

                'etapa_destino_id' => $diagnostico->id,

                'etapa_origem_nome' => 'Recepção',

                'etapa_destino_nome' => 'Diagnóstico',
            ]
        );
    }

    public function test_mover_para_mesma_etapa_nao_cria_historico_duplicado(): void
    {
        $ordemServico =
            $this->criarOrdemServico();

        $quantidadeAntes =
            $ordemServico
                ->historicoEtapas()
                ->count();

        app(
            AlterarEtapaOrdemServico::class
        )->execute(
            $ordemServico,
            $ordemServico->etapa
        );

        $quantidadeDepois =
            $ordemServico
                ->historicoEtapas()
                ->count();

        $this->assertSame(
            $quantidadeAntes,
            $quantidadeDepois
        );

        $this->assertSame(
            1,
            $quantidadeDepois
        );
    }

    public function test_ordem_servico_finalizada_nao_pode_mudar_de_etapa(): void
    {
        $ordemServico =
            $this->criarOrdemServico();

        $ordemServico->update([
            'status' => 'finalizada',
        ]);

        $diagnostico =
            $this->etapa(
                'diagnostico'
            );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Somente ordens de serviço abertas podem mudar de etapa.'
        );

        app(
            AlterarEtapaOrdemServico::class
        )->execute(
            $ordemServico,
            $diagnostico
        );
    }

    public function test_ordem_servico_cancelada_nao_pode_mudar_de_etapa(): void
    {
        $ordemServico =
            $this->criarOrdemServico();

        $ordemServico->update([
            'status' => 'cancelada',
        ]);

        $diagnostico =
            $this->etapa(
                'diagnostico'
            );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Somente ordens de serviço abertas podem mudar de etapa.'
        );

        app(
            AlterarEtapaOrdemServico::class
        )->execute(
            $ordemServico,
            $diagnostico
        );
    }

    public function test_etapa_inativa_nao_pode_ser_usada_na_ordem_servico(): void
    {
        $ordemServico =
            $this->criarOrdemServico();

        $diagnostico =
            $this->etapa(
                'diagnostico'
            );

        $diagnostico->update([
            'ativo' => false,
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'A etapa selecionada está inativa.'
        );

        app(
            AlterarEtapaOrdemServico::class
        )->execute(
            $ordemServico,
            $diagnostico
        );
    }

    public function test_etapa_incompativel_nao_pode_ser_usada_na_ordem_servico(): void
    {
        $ordemServico =
            $this->criarOrdemServico();

        $diagnostico =
            $this->etapa(
                'diagnostico'
            );

        $diagnostico->update([
            'aplica_ordem_servico' => false,
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'A etapa selecionada não pode ser utilizada em Ordens de Serviço.'
        );

        app(
            AlterarEtapaOrdemServico::class
        )->execute(
            $ordemServico,
            $diagnostico
        );
    }
}
