<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table(
            'produtos_veiculos'
        )
            ->orderBy('id')
            ->chunkById(
                500,
                function ($vinculos) {
                    $agora = now();

                    foreach (
                        $vinculos
                        as $vinculo
                    ) {
                        $campos = [
                            'veiculo_id' =>
                                (int) $vinculo
                                    ->veiculo_id,

                            'mes_ano' => null,
                            'modelo' => null,
                            'motor' => null,
                            'comp' => null,
                            'valvulas' => null,
                            'cilindros' => null,
                            'combustivel' => null,
                            'complemento' => null,
                        ];

                        $chave = hash(
                            'sha256',
                            json_encode(
                                $campos,
                                JSON_UNESCAPED_UNICODE
                                | JSON_UNESCAPED_SLASHES
                            )
                        );

                        DB::table(
                            'produto_aplicacoes'
                        )->insertOrIgnore([
                            'produto_id' =>
                                $vinculo
                                    ->produto_id,

                            'veiculo_id' =>
                                $vinculo
                                    ->veiculo_id,

                            'catalogo_produto_aplicacao_id'
                                => null,

                            'chave_aplicacao' =>
                                $chave,

                            'mes_ano' => null,
                            'modelo' => null,
                            'motor' => null,
                            'comp' => null,
                            'valvulas' => null,
                            'cilindros' => null,
                            'combustivel' => null,
                            'complemento' => null,

                            'origem' =>
                                'manual',

                            'status_resolucao' =>
                                'resolvida',

                            'created_at' =>
                                $agora,

                            'updated_at' =>
                                $agora,
                        ]);
                    }
                }
            );
    }

    public function down(): void
    {
        /*
         * Não removemos aplicações no rollback:
         * após esta migração elas passam a fazer parte
         * do conhecimento operacional do Jaime.
         */
    }
};
