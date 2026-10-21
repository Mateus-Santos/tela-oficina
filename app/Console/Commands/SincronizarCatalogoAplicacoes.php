<?php

namespace App\Console\Commands;

use App\Services\Produto\SincronizarAplicacoesCatalogo;
use Illuminate\Console\Command;
use Throwable;

class SincronizarCatalogoAplicacoes extends Command
{
    protected $signature =
        'catalogo:sincronizar-aplicacoes
        {--fonte= : Código da fonte, por exemplo sky}
        {--chunk=500 : Quantidade por lote}
        {--dry-run : Apenas diagnostica sem gravar}';

    protected $description =
        'Sincroniza aplicações do catálogo com produtos operacionais';

    public function handle(
        SincronizarAplicacoesCatalogo $sincronizador
    ): int {
        $fonte =
            trim(
                (string) $this->option(
                    'fonte'
                )
            );

        $fonte =
            $fonte !== ''
                ? $fonte
                : null;

        $chunk =
            max(
                1,
                (int) $this->option(
                    'chunk'
                )
            );

        try {
            $diagnostico =
                $sincronizador
                    ->diagnosticar(
                        $fonte
                    );

            $this->table(
                [
                    'Métrica',
                    'Quantidade',
                ],
                [
                    [
                        'Aplicações no catálogo',
                        $diagnostico[
                            'total_catalogo'
                        ],
                    ],
                    [
                        'Com produto operacional',
                        $diagnostico[
                            'com_produto_operacional'
                        ],
                    ],
                    [
                        'Sem produto operacional',
                        $diagnostico[
                            'sem_produto_operacional'
                        ],
                    ],
                    [
                        'Resolvidas',
                        $diagnostico[
                            'resolvidas'
                        ],
                    ],
                    [
                        'Para revisão',
                        $diagnostico[
                            'revisao'
                        ],
                    ],
                    [
                        'Já materializadas',
                        $diagnostico[
                            'ja_materializadas'
                        ],
                    ],
                    [
                        'Produtos afetados',
                        $diagnostico[
                            'produtos_afetados'
                        ],
                    ],
                    [
                        'Colisões',
                        $diagnostico[
                            'colisoes'
                        ],
                    ],
                ]
            );

            if (
                $diagnostico[
                    'colisoes'
                ] > 0
            ) {
                $this->error(
                    'Sincronização bloqueada: existem colisões.'
                );

                return self::FAILURE;
            }

            if (
                $this->option(
                    'dry-run'
                )
            ) {
                $this->info(
                    'Dry-run concluído. Nenhuma alteração foi gravada.'
                );

                return self::SUCCESS;
            }

            $resultado =
                $sincronizador
                    ->sincronizar(
                        $fonte,
                        $chunk
                    );

            $this->newLine();

            $this->table(
                [
                    'Resultado',
                    'Quantidade',
                ],
                [
                    [
                        'Sincronizadas',
                        $resultado[
                            'sincronizadas'
                        ],
                    ],
                    [
                        'Resolvidas',
                        $resultado[
                            'resolvidas'
                        ],
                    ],
                    [
                        'Para revisão',
                        $resultado[
                            'revisao'
                        ],
                    ],
                    [
                        'Produtos reconstruídos',
                        $resultado[
                            'produtos_reconstruidos'
                        ],
                    ],
                    [
                        'Materializadas após',
                        $resultado[
                            'materializadas_apos'
                        ],
                    ],
                ]
            );

            $this->info(
                'Sincronização concluída.'
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}
