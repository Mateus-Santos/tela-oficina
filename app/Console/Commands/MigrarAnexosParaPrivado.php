<?php

namespace App\Console\Commands;

use App\Models\Anexo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class MigrarAnexosParaPrivado extends Command
{
    protected $signature =
        'anexos:migrar-para-privado';

    protected $description =
        'Move anexos do disco público para o armazenamento privado';

    public function handle(): int
    {
        $publico =
            Storage::disk('public');

        $privado =
            Storage::disk('private');

        $migrados = 0;
        $jaPrivados = 0;
        $ausentes = 0;
        $erros = 0;

        Anexo::query()
            ->orderBy('id')
            ->each(
                function (Anexo $anexo) use (
                    $publico,
                    $privado,
                    &$migrados,
                    &$jaPrivados,
                    &$ausentes,
                    &$erros
                ) {
                    $caminho =
                        $anexo->arquivo;

                    if (! $caminho) {
                        $ausentes++;

                        $this->warn(
                            "Anexo #{$anexo->id}: caminho vazio."
                        );

                        return;
                    }

                    try {
                        if (
                            $privado->exists(
                                $caminho
                            )
                        ) {
                            if (
                                $publico->exists(
                                    $caminho
                                )
                            ) {
                                if (
                                    $privado->size(
                                        $caminho
                                    )
                                    !==
                                    $publico->size(
                                        $caminho
                                    )
                                ) {
                                    throw new RuntimeException(
                                        'Arquivo existe nos dois discos com tamanhos diferentes.'
                                    );
                                }

                                $publico->delete(
                                    $caminho
                                );
                            }

                            $jaPrivados++;

                            $this->line(
                                "Anexo #{$anexo->id}: já está privado."
                            );

                            return;
                        }

                        if (
                            ! $publico->exists(
                                $caminho
                            )
                        ) {
                            $ausentes++;

                            $this->warn(
                                "Anexo #{$anexo->id}: arquivo não encontrado."
                            );

                            return;
                        }

                        $conteudo =
                            $publico->get(
                                $caminho
                            );

                        $privado->put(
                            $caminho,
                            $conteudo
                        );

                        if (
                            ! $privado->exists(
                                $caminho
                            )
                        ) {
                            throw new RuntimeException(
                                'Falha ao criar arquivo privado.'
                            );
                        }

                        if (
                            $privado->size(
                                $caminho
                            )
                            !==
                            $publico->size(
                                $caminho
                            )
                        ) {
                            $privado->delete(
                                $caminho
                            );

                            throw new RuntimeException(
                                'Falha de integridade após a cópia.'
                            );
                        }

                        $publico->delete(
                            $caminho
                        );

                        $migrados++;

                        $this->info(
                            "Anexo #{$anexo->id}: migrado."
                        );
                    } catch (
                        Throwable $exception
                    ) {
                        $erros++;

                        $this->error(
                            "Anexo #{$anexo->id}: "
                            .$exception->getMessage()
                        );
                    }
                }
            );

        $this->newLine();

        $this->table(
            [
                'Resultado',
                'Quantidade',
            ],
            [
                [
                    'Migrados',
                    $migrados,
                ],
                [
                    'Já privados',
                    $jaPrivados,
                ],
                [
                    'Ausentes',
                    $ausentes,
                ],
                [
                    'Erros',
                    $erros,
                ],
            ]
        );

        if (
            $ausentes > 0
            || $erros > 0
        ) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
