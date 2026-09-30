<?php

namespace App\Actions\Notas;

use App\Actions\Estoque\RegistrarEntrada;
use App\Models\MovimentacaoEstoque;
use App\Models\Nota;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelarNota
{
    public function __construct(
        private RegistrarEntrada $registrarEntrada
    ) {
    }

    public function execute(Nota $nota): Nota
    {
        return DB::transaction(function () use ($nota) {
            $nota = Nota::query()
                ->lockForUpdate()
                ->with([
                    'itens.itemable',
                    'contaReceber',
                ])
                ->findOrFail($nota->id);

            /*
             * =====================================================
             * STATUS PERMITIDOS
             * =====================================================
             *
             * Aberto:
             * - ainda não houve baixa de estoque;
             * - cancela apenas Nota e financeiro.
             *
             * Finalizado / Concluido:
             * - houve baixa de estoque;
             * - precisa reverter estoque.
             */
            $notaAberta =
                $nota->status === 'Aberto';

            $notaFinalizada =
                in_array(
                    $nota->status,
                    [
                        'Finalizado',
                        'Concluido',
                    ],
                    true
                );

            if (
                !$notaAberta
                && !$notaFinalizada
            ) {
                throw new InvalidArgumentException(
                    'Esta nota não pode ser cancelada no status atual.'
                );
            }

            $contaReceber =
                $nota->contaReceber;

            /*
             * =====================================================
             * RECEBIMENTOS ATIVOS
             * =====================================================
             *
             * Recebimentos estornados permanecem como histórico,
             * mas não impedem o cancelamento.
             */
            if ($contaReceber) {
                $possuiRecebimentosAtivos =
                    $contaReceber
                        ->recebimentos()
                        ->whereNull('estornado_em')
                        ->exists();

                if ($possuiRecebimentosAtivos) {
                    throw new InvalidArgumentException(
                        "Não é possível cancelar a Nota #{$nota->id} porque sua Conta a Receber possui recebimentos ativos. Estorne os recebimentos antes de cancelar."
                    );
                }
            }

            /*
             * =====================================================
             * REVERSÃO DE ESTOQUE
             * =====================================================
             *
             * Somente Notas já finalizadas tiveram saída de estoque.
             *
             * Nota Aberta NÃO deve gerar entrada no estoque.
             */
            if ($notaFinalizada) {
                $itensProdutos =
                    $nota->itens
                        ->filter(
                            fn ($item) =>
                                $item->itemable_type
                                === Produto::class
                        )
                        ->sortBy(
                            fn ($item) =>
                                $item->itemable?->id
                                ?? PHP_INT_MAX
                        )
                        ->values();

                foreach ($itensProdutos as $item) {
                    if (!$item->itemable) {
                        throw new InvalidArgumentException(
                            "O item #{$item->id} possui um produto inválido."
                        );
                    }

                    /*
                     * Procura exatamente a baixa realizada
                     * quando a Nota foi finalizada.
                     */
                    $saida =
                        MovimentacaoEstoque::query()
                            ->where(
                                'tipo',
                                'saida'
                            )
                            ->where(
                                'origem_type',
                                $item->getMorphClass()
                            )
                            ->where(
                                'origem_id',
                                $item->id
                            )
                            ->first();

                    if (!$saida) {
                        throw new InvalidArgumentException(
                            "Não foi encontrada a baixa de estoque do item #{$item->id}."
                        );
                    }

                    /*
                     * Proteção contra reversão duplicada.
                     */
                    $reversaoExistente =
                        MovimentacaoEstoque::query()
                            ->where(
                                'tipo',
                                'entrada'
                            )
                            ->where(
                                'origem_type',
                                $item->getMorphClass()
                            )
                            ->where(
                                'origem_id',
                                $item->id
                            )
                            ->where(
                                'observacoes',
                                'like',
                                'Reversão do cancelamento%'
                            )
                            ->exists();

                    if ($reversaoExistente) {
                        throw new InvalidArgumentException(
                            "O item #{$item->id} já possui uma reversão de estoque."
                        );
                    }

                    /*
                     * A quantidade devolvida vem da movimentação
                     * original de saída, e não do valor atual
                     * armazenado no item.
                     */
                    $this->registrarEntrada->execute(
                        produto: $item->itemable,
                        quantidade:
                            (float) $saida->quantidade,
                        valorUnitario:
                            $saida->valor_unitario !== null
                                ? (float) $saida->valor_unitario
                                : null,
                        origem: $item,
                        observacoes:
                            "Reversão do cancelamento da Nota #{$nota->id}, item #{$item->id}."
                    );
                }
            }

            /*
             * =====================================================
             * CONTA A RECEBER
             * =====================================================
             *
             * Se já estiver cancelada, não há necessidade
             * de gerar erro. A Nota ainda pode ser cancelada.
             */
            if (
                $contaReceber
                && $contaReceber->status !== 'cancelada'
            ) {
                $contaReceber->update([
                    'status' =>
                        'cancelada',

                    'data_quitacao' =>
                        null,
                ]);
            }

            /*
             * =====================================================
             * CANCELAR NOTA
             * =====================================================
             */
            $nota->update([
                'status' =>
                    'Cancelado',
            ]);

            return $nota->fresh([
                'contaReceber',
            ]);
        });
    }
}
