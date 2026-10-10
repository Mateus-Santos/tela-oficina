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
            'notas',
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
                    'aplica_nota',
                    true
                )
                ->where(
                    'ativo',
                    true
                )
                ->orderBy('ordem')
                ->first();

        $etapaFinal =
            DB::table('etapas')
                ->where(
                    'tipo',
                    'final'
                )
                ->where(
                    'aplica_nota',
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
            || ! $etapaFinal
        ) {
            throw new RuntimeException(
                'Não foi possível localizar as etapas inicial e final para migrar as Notas.'
            );
        }

        DB::table('notas')
            ->whereIn(
                'status',
                [
                    'Finalizado',
                    'Concluido',
                ]
            )
            ->update([
                'etapa_id' => $etapaFinal->id,
            ]);

        DB::table('notas')
            ->whereNotIn(
                'status',
                [
                    'Finalizado',
                    'Concluido',
                ]
            )
            ->update([
                'etapa_id' => $etapaInicial->id,
            ]);

        /*
         * Histórico inicial dos registros já existentes.
         *
         * user_id fica null porque não existe um usuário real
         * responsável por essa movimentação histórica.
         */
        DB::table('notas')
            ->select([
                'id',
                'etapa_id',
            ])
            ->orderBy('id')
            ->chunkById(
                500,
                function ($notas) use (
                    $etapaInicial,
                    $etapaFinal
                ) {
                    $agora = now();

                    $historicos = [];

                    foreach ($notas as $nota) {
                        $etapa =
                            (int) $nota->etapa_id
                            === (int) $etapaFinal->id
                                ? $etapaFinal
                                : $etapaInicial;

                        $historicos[] = [
                            'nota_id' => $nota->id,

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
                            'nota_etapa_historicos'
                        )->insert(
                            $historicos
                        );
                    }
                }
            );
    }

    public function down(): void
    {
        Schema::table(
            'notas',
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
