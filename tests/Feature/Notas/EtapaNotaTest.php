<?php

namespace Tests\Feature\Notas;

use App\Actions\Notas\AlterarEtapaNota;
use App\Actions\Notas\CriarNota;
use App\Models\Etapa;
use App\Models\Nota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class EtapaNotaTest extends TestCase
{
    use RefreshDatabase;

    private function criarNota(): Nota
    {
        return app(
            CriarNota::class
        )->execute([
            'cliente_id' => null,
            'veiculo_cliente_id' => null,
            'km' => null,
            'km_proxima_troca_oleo' => null,
            'itens' => [],
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

    public function test_nota_criada_nasce_na_etapa_inicial(): void
    {
        $nota =
            $this->criarNota();

        $etapaInicial =
            Etapa::query()
                ->paraNota()
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
            $nota->etapa_id
        );

        $this->assertSame(
            'Recepção',
            $nota->etapa->nome
        );
    }

    public function test_criacao_da_nota_gera_historico_inicial(): void
    {
        $nota =
            $this->criarNota();

        $this->assertDatabaseCount(
            'nota_etapa_historicos',
            1
        );

        $this->assertDatabaseHas(
            'nota_etapa_historicos',
            [
                'nota_id' => $nota->id,

                'etapa_origem_id' => null,

                'etapa_destino_id' => $nota->etapa_id,

                'etapa_origem_nome' => null,

                'etapa_destino_nome' => 'Recepção',
            ]
        );
    }

    public function test_nota_aberta_pode_mudar_de_etapa(): void
    {
        $nota =
            $this->criarNota();

        $diagnostico =
            $this->etapa(
                'diagnostico'
            );

        $nota =
            app(
                AlterarEtapaNota::class
            )->execute(
                $nota,
                $diagnostico
            );

        $this->assertSame(
            $diagnostico->id,
            $nota->etapa_id
        );

        $this->assertSame(
            'Diagnóstico',
            $nota->etapa->nome
        );
    }

    public function test_mudanca_valida_de_etapa_cria_historico(): void
    {
        $nota =
            $this->criarNota();

        $etapaInicialId =
            $nota->etapa_id;

        $diagnostico =
            $this->etapa(
                'diagnostico'
            );

        app(
            AlterarEtapaNota::class
        )->execute(
            $nota,
            $diagnostico
        );

        $this->assertDatabaseCount(
            'nota_etapa_historicos',
            2
        );

        $this->assertDatabaseHas(
            'nota_etapa_historicos',
            [
                'nota_id' => $nota->id,

                'etapa_origem_id' => $etapaInicialId,

                'etapa_destino_id' => $diagnostico->id,

                'etapa_origem_nome' => 'Recepção',

                'etapa_destino_nome' => 'Diagnóstico',
            ]
        );
    }

    public function test_mover_para_mesma_etapa_nao_cria_historico_duplicado(): void
    {
        $nota =
            $this->criarNota();

        $quantidadeAntes =
            $nota
                ->historicoEtapas()
                ->count();

        app(
            AlterarEtapaNota::class
        )->execute(
            $nota,
            $nota->etapa
        );

        $quantidadeDepois =
            $nota
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

    public function test_nota_finalizada_nao_pode_mudar_de_etapa(): void
    {
        $nota =
            $this->criarNota();

        $nota->update([
            'status' => 'Finalizado',
        ]);

        $diagnostico =
            $this->etapa(
                'diagnostico'
            );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Somente notas com status Aberto podem mudar de etapa.'
        );

        app(
            AlterarEtapaNota::class
        )->execute(
            $nota,
            $diagnostico
        );
    }

    public function test_nota_cancelada_nao_pode_mudar_de_etapa(): void
    {
        $nota =
            $this->criarNota();

        $nota->update([
            'status' => 'Cancelado',
        ]);

        $diagnostico =
            $this->etapa(
                'diagnostico'
            );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Somente notas com status Aberto podem mudar de etapa.'
        );

        app(
            AlterarEtapaNota::class
        )->execute(
            $nota,
            $diagnostico
        );
    }

    public function test_etapa_inativa_nao_pode_ser_usada_na_nota(): void
    {
        $nota =
            $this->criarNota();

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
            AlterarEtapaNota::class
        )->execute(
            $nota,
            $diagnostico
        );
    }

    public function test_etapa_incompativel_nao_pode_ser_usada_na_nota(): void
    {
        $nota =
            $this->criarNota();

        $diagnostico =
            $this->etapa(
                'diagnostico'
            );

        $diagnostico->update([
            'aplica_nota' => false,
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'A etapa selecionada não pode ser utilizada em Notas.'
        );

        app(
            AlterarEtapaNota::class
        )->execute(
            $nota,
            $diagnostico
        );
    }
}
