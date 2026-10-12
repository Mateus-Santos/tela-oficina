<?php

namespace Tests\Feature\OrdemServico;

use App\Actions\OrdemServico\AlterarEtapaOrdemServico;
use App\Actions\OrdemServico\CancelarOrdemServico;
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

class CancelarOrdemServicoTest extends TestCase
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

    public function test_cancelar_ordem_servico_altera_status_para_cancelada(): void
    {
        $ordemServico = $this->criarOrdemServico();

        $ordemServico = app(
            CancelarOrdemServico::class
        )->execute(
            $ordemServico
        );

        $this->assertSame(
            'cancelada',
            $ordemServico->status
        );

        $this->assertDatabaseHas(
            'ordem_servicos',
            [
                'id' => $ordemServico->id,
                'status' => 'cancelada',
            ]
        );
    }

    public function test_cancelar_ordem_servico_preenche_data_de_fechamento(): void
    {
        $ordemServico = $this->criarOrdemServico();

        $this->assertNull(
            $ordemServico->data_fechamento
        );

        $ordemServico = app(
            CancelarOrdemServico::class
        )->execute(
            $ordemServico
        );

        $this->assertNotNull(
            $ordemServico->data_fechamento
        );
    }

    public function test_cancelamento_mantem_etapa_em_que_ordem_servico_estava(): void
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

        $etapaAntes =
            $ordemServico->etapa_id;

        $ordemServico = app(
            CancelarOrdemServico::class
        )->execute(
            $ordemServico
        );

        $this->assertSame(
            $etapaAntes,
            $ordemServico->etapa_id
        );

        $this->assertSame(
            'Diagnóstico',
            $ordemServico->etapa->nome
        );
    }

    public function test_cancelamento_nao_cria_movimentacao_de_etapa(): void
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

        $quantidadeAntes =
            $ordemServico
                ->historicoEtapas()
                ->count();

        app(
            CancelarOrdemServico::class
        )->execute(
            $ordemServico
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
            2,
            $quantidadeDepois
        );
    }

    public function test_nao_permite_cancelar_ordem_servico_finalizada(): void
    {
        $ordemServico = $this->criarOrdemServico();

        $ordemServico->update([
            'status' => 'finalizada',
            'data_fechamento' => now(),
        ]);

        $etapaAntes =
            $ordemServico->etapa_id;

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Somente ordens de serviço abertas podem ser canceladas.'
        );

        try {
            app(
                CancelarOrdemServico::class
            )->execute(
                $ordemServico
            );
        } finally {
            $ordemServico->refresh();

            $this->assertSame(
                'finalizada',
                $ordemServico->status
            );

            $this->assertSame(
                $etapaAntes,
                $ordemServico->etapa_id
            );
        }
    }

    public function test_nao_permite_cancelar_ordem_servico_duas_vezes(): void
    {
        $ordemServico = $this->criarOrdemServico();

        app(
            CancelarOrdemServico::class
        )->execute(
            $ordemServico
        );

        $ordemServico->refresh();

        $etapaAntes =
            $ordemServico->etapa_id;

        $dataFechamentoAntes =
            $ordemServico->data_fechamento;

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Somente ordens de serviço abertas podem ser canceladas.'
        );

        try {
            app(
                CancelarOrdemServico::class
            )->execute(
                $ordemServico
            );
        } finally {
            $ordemServico->refresh();

            $this->assertSame(
                'cancelada',
                $ordemServico->status
            );

            $this->assertSame(
                $etapaAntes,
                $ordemServico->etapa_id
            );

            $this->assertTrue(
                $dataFechamentoAntes->equalTo(
                    $ordemServico->data_fechamento
                )
            );
        }
    }
}
