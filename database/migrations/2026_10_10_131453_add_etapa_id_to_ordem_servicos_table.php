<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'ordem_servicos',
            function (Blueprint $table) {
                $table->foreignId(
                    'etapa_id'
                )
                    ->nullable()
                    ->after('status')
                    ->constrained('etapas')
                    ->nullOnDelete();

                $table->index([
                    'status',
                    'etapa_id',
                ]);
            }
        );

        $etapaInicial =
            DB::table('etapas')
                ->where(
                    'tipo',
                    'inicial'
                )
                ->where(
                    'aplica_ordem_servico',
                    true
                )
                ->where(
                    'ativo',
                    true
                )
                ->orderBy('ordem')
                ->first();

        $etapaAguardandoAprovacao =
            DB::table('etapas')
                ->where(
                    'slug',
                    'aguardando-aprovacao'
                )
                ->first();

        $etapaEmManutencao =
            DB::table('etapas')
                ->where(
                    'slug',
                    'em-manutencao'
                )
                ->first();

        $etapaFinal =
            DB::table('etapas')
                ->where(
                    'tipo',
                    'final'
                )
                ->where(
                    'aplica_ordem_servico',
                    true
                )
                ->where(
                    'ativo',
                    true
                )
                ->orderByDesc('ordem')
                ->first();

        if (
            ! $etapaInicial
            || ! $etapaAguardandoAprovacao
            || ! $etapaEmManutencao
            || ! $etapaFinal
        ) {
            throw new RuntimeException(
                'Não foi possível localizar as etapas necessárias para migrar as Ordens de Serviço.'
            );
        }

        /*
         * =====================================================
         * ETAPA DOS REGISTROS EXISTENTES
         * =====================================================
         */

        DB::table('ordem_servicos')
            ->where(
                'status',
                'aberta'
            )
            ->update([
                'etapa_id' => $etapaInicial->id,
            ]);

        DB::table('ordem_servicos')
            ->where(
                'status',
                'em_andamento'
            )
            ->update([
                'etapa_id' => $etapaEmManutencao->id,
            ]);

        DB::table('ordem_servicos')
            ->where(
                'status',
                'aguardando_aprovacao'
            )
            ->update([
                'etapa_id' => $etapaAguardandoAprovacao->id,
            ]);

        DB::table('ordem_servicos')
            ->where(
                'status',
                'finalizada'
            )
            ->update([
                'etapa_id' => $etapaFinal->id,
            ]);

        DB::table('ordem_servicos')
            ->where(
                'status',
                'cancelada'
            )
            ->update([
                'etapa_id' => $etapaInicial->id,
            ]);

        /*
         * =====================================================
         * NORMALIZAR STATUS
         * =====================================================
         *
         * "em_andamento" e "aguardando_aprovacao"
         * deixam de ser estados sistêmicos.
         *
         * Passam a ser representados pela etapa.
         */
        DB::table('ordem_servicos')
            ->whereIn(
                'status',
                [
                    'em_andamento',
                    'aguardando_aprovacao',
                ]
            )
            ->update([
                'status' => 'aberta',
            ]);

        /*
         * =====================================================
         * HISTÓRICO INICIAL
         * =====================================================
         */
        $etapasPorId =
            DB::table('etapas')
                ->get()
                ->keyBy('id');

        DB::table('ordem_servicos')
            ->select([
                'id',
                'etapa_id',
            ])
            ->orderBy('id')
            ->chunkById(
                500,
                function ($ordens) use (
                    $etapasPorId
                ) {
                    $agora = now();

                    $historicos = [];

                    foreach ($ordens as $ordem) {
                        $etapa =
                            $etapasPorId->get(
                                $ordem->etapa_id
                            );

                        if (! $etapa) {
                            continue;
                        }

                        $historicos[] = [
                            'ordem_servico_id' => $ordem->id,

                            'etapa_origem_id' => null,

                            'etapa_destino_id' => $etapa->id,

                            'etapa_origem_nome' => null,

                            'etapa_destino_nome' => $etapa->nome,

                            'user_id' => null,

                            'motivo' => 'Etapa definida automaticamente durante a implantação do fluxo operacional.',

                            'created_at' => $agora,

                            'updated_at' => $agora,
                        ];
                    }

                    if (! empty($historicos)) {
                        DB::table(
                            'ordem_servico_etapa_historicos'
                        )->insert(
                            $historicos
                        );
                    }
                }
            );
    }

    public function down(): void
    {
        /*
         * Ao desfazer a migration, tentamos restaurar os
         * estados operacionais antigos com base na etapa atual.
         */
        $etapaAguardandoAprovacao =
            DB::table('etapas')
                ->where(
                    'slug',
                    'aguardando-aprovacao'
                )
                ->value('id');

        $etapaEmManutencao =
            DB::table('etapas')
                ->where(
                    'slug',
                    'em-manutencao'
                )
                ->value('id');

        if ($etapaAguardandoAprovacao) {
            DB::table('ordem_servicos')
                ->where(
                    'status',
                    'aberta'
                )
                ->where(
                    'etapa_id',
                    $etapaAguardandoAprovacao
                )
                ->update([
                    'status' => 'aguardando_aprovacao',
                ]);
        }

        if ($etapaEmManutencao) {
            DB::table('ordem_servicos')
                ->where(
                    'status',
                    'aberta'
                )
                ->where(
                    'etapa_id',
                    $etapaEmManutencao
                )
                ->update([
                    'status' => 'em_andamento',
                ]);
        }

        Schema::table(
            'ordem_servicos',
            function (Blueprint $table) {
                $table->dropIndex([
                    'status',
                    'etapa_id',
                ]);

                $table->dropConstrainedForeignId(
                    'etapa_id'
                );
            }
        );
    }
};
