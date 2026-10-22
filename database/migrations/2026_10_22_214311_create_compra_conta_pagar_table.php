<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'compra_conta_pagar',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('conta_pagar_id')
                    ->constrained('contas_pagar')
                    ->cascadeOnDelete();

                $table->foreignId('compra_id')
                    ->constrained('compras')
                    ->restrictOnDelete();

                $table->decimal(
                    'valor_vinculado',
                    15,
                    2
                );

                $table->timestamps();

                /*
                 * Uma compra só pode compor
                 * uma conta a pagar.
                 */
                $table->unique('compra_id');

                $table->index('conta_pagar_id');
            }
        );

        /*
         * Antes do backfill, garantimos que
         * o legado não possui duas contas
         * apontando para a mesma compra.
         */
        $compraDuplicada = DB::table(
            'contas_pagar'
        )
            ->select('compra_id')
            ->whereNotNull('compra_id')
            ->groupBy('compra_id')
            ->havingRaw('COUNT(*) > 1')
            ->value('compra_id');

        if ($compraDuplicada !== null) {
            throw new \RuntimeException(
                'Não foi possível migrar os vínculos de compras: '
                . 'a compra #'
                . $compraDuplicada
                . ' está associada a mais de uma conta a pagar.'
            );
        }

        /*
         * Migração dos vínculos atuais.
         *
         * O valor atual da ContaPagar é usado
         * como fotografia financeira do vínculo
         * legado. Assim não recalculamos nem
         * alteramos dívidas históricas.
         */
        DB::table('contas_pagar')
            ->whereNotNull('compra_id')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($contas): void {
                    $agora = now();

                    $linhas = $contas
                        ->map(
                            fn ($conta) => [
                                'conta_pagar_id' =>
                                    $conta->id,

                                'compra_id' =>
                                    $conta->compra_id,

                                'valor_vinculado' =>
                                    $conta->valor,

                                'created_at' =>
                                    $agora,

                                'updated_at' =>
                                    $agora,
                            ]
                        )
                        ->all();

                    if ($linhas !== []) {
                        DB::table(
                            'compra_conta_pagar'
                        )->insert($linhas);
                    }
                }
            );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'compra_conta_pagar'
        );
    }
};
