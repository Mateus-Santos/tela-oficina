<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->alterarIdentidade('sky', 'fornecedor_s');
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Reversão automática bloqueada: '
            .'a identidade da fonte exige restauração '
            .'controlada de backup.'
        );
    }

    private function alterarIdentidade(
        string $anterior,
        string $novo
    ): void {
        DB::transaction(function () use ($anterior, $novo) {
            $fonteAnterior = DB::table('catalogo_fontes')
                ->where('codigo', $anterior)
                ->lockForUpdate()
                ->first();

            $fonteNova = DB::table('catalogo_fontes')
                ->where('codigo', $novo)
                ->lockForUpdate()
                ->first();

            if ($fonteAnterior && $fonteNova) {
                throw new RuntimeException(
                    'Migração Fornecedor S bloqueada: '
                    .'as duas identidades da fonte já existem.'
                );
            }

            // A chave única de produto, origem e aplicação
            // não pode receber colisões durante a conversão.
            $colisao = DB::table('produto_aplicacoes as a')
                ->join(
                    'produto_aplicacoes as b',
                    function (JoinClause $join) {
                        $join->on(
                            'a.produto_id',
                            '=',
                            'b.produto_id'
                        )->on(
                            'a.chave_aplicacao',
                            '=',
                            'b.chave_aplicacao'
                        );
                    }
                )
                ->where('a.origem', $anterior)
                ->where('b.origem', $novo)
                ->exists();

            if ($colisao) {
                throw new RuntimeException(
                    'Migração Fornecedor S bloqueada: '
                    .'existem aplicações com identidade conflitante.'
                );
            }

            if ($fonteAnterior) {
                DB::table('catalogo_fontes')
                    ->where('id', $fonteAnterior->id)
                    ->update([
                        'codigo' => $novo,
                        'nome' => 'Fornecedor S',
                    ]);
            }

            DB::table('produto_aplicacoes')
                ->where('origem', $anterior)
                ->update(['origem' => $novo]);
        });
    }
};
