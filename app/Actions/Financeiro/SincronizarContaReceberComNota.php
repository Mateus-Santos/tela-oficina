<?php

namespace App\Actions\Financeiro;

use App\Models\ContaReceber;
use App\Models\Nota;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SincronizarContaReceberComNota
{
    public function execute(Nota $nota): ?ContaReceber
    {
        return DB::transaction(function () use ($nota) {
            $nota = Nota::query()
                ->lockForUpdate()
                ->findOrFail($nota->id);

            $conta = ContaReceber::query()
                ->where('nota_id', $nota->id)
                ->lockForUpdate()
                ->first();

            /*
             * A Nota ainda não possui financeiro.
             *
             * Não existe nada para sincronizar.
             */
            if (!$conta) {
                return null;
            }

            if ($conta->status === 'cancelada') {
                throw ValidationException::withMessages([
                    'nota' =>
                        "A Conta a Receber #{$conta->id} vinculada à Nota está cancelada.",
                ]);
            }

            $novoTotalCentavos =
                $this->paraCentavos(
                    $nota->total
                );

            if ($novoTotalCentavos <= 0) {
                throw ValidationException::withMessages([
                    'nota' =>
                        'O total da Nota deve ser maior que zero para sincronizar o financeiro.',
                ]);
            }

            /*
             * =====================================================
             * RECEBIMENTOS ATIVOS
             * =====================================================
             *
             * Recebimentos estornados continuam existindo
             * historicamente, mas não fazem parte do valor recebido.
             */
            $recebimentosAtivos =
                $conta
                    ->recebimentos()
                    ->whereNull('estornado_em')
                    ->lockForUpdate()
                    ->get();

            $totalRecebidoCentavos = 0;

            foreach ($recebimentosAtivos as $recebimento) {
                $totalRecebidoCentavos +=
                    $this->paraCentavos(
                        $recebimento->valor
                    );
            }

            /*
             * =====================================================
             * REGRA CRÍTICA
             * =====================================================
             *
             * O total da Nota nunca pode ficar abaixo
             * do que efetivamente já foi recebido.
             */
            if (
                $novoTotalCentavos
                < $totalRecebidoCentavos
            ) {
                $valorEstornarCentavos =
                    $totalRecebidoCentavos
                    - $novoTotalCentavos;

                throw ValidationException::withMessages([
                    'nota' => sprintf(
                        'Não é possível reduzir a Nota para R$ %s porque existem R$ %s em recebimentos ativos. Estorne ao menos R$ %s antes de salvar esta alteração.',
                        number_format(
                            $novoTotalCentavos / 100,
                            2,
                            ',',
                            '.'
                        ),
                        number_format(
                            $totalRecebidoCentavos / 100,
                            2,
                            ',',
                            '.'
                        ),
                        number_format(
                            $valorEstornarCentavos / 100,
                            2,
                            ',',
                            '.'
                        )
                    ),
                ]);
            }

            /*
             * =====================================================
             * PARCELAS
             * =====================================================
             */
            $parcelas =
                $conta
                    ->parcelas()
                    ->lockForUpdate()
                    ->get();

            if ($parcelas->isEmpty()) {
                throw ValidationException::withMessages([
                    'nota' =>
                        "A Conta a Receber #{$conta->id} não possui parcelas cadastradas.",
                ]);
            }

            /*
             * Valor recebido ativo por parcela.
             */
            $recebidoPorParcela = [];

            foreach ($parcelas as $parcela) {
                $recebidoPorParcela[$parcela->id] =
                    $this->paraCentavos(
                        $parcela
                            ->recebimentos()
                            ->whereNull('estornado_em')
                            ->sum('valor')
                    );
            }

            /*
             * Soma atual das parcelas.
             */
            $totalParcelasCentavos = 0;

            foreach ($parcelas as $parcela) {
                $totalParcelasCentavos +=
                    $this->paraCentavos(
                        $parcela->valor
                    );
            }

            $diferencaCentavos =
                $novoTotalCentavos
                - $totalParcelasCentavos;

            /*
             * =====================================================
             * AUMENTO DA NOTA
             * =====================================================
             *
             * A diferença é acrescentada à última parcela.
             *
             * Recebimentos existentes permanecem intactos.
             */
            if ($diferencaCentavos > 0) {
                $ultimaParcela =
                    $parcelas
                        ->sortByDesc('numero')
                        ->first();

                $valorAtualCentavos =
                    $this->paraCentavos(
                        $ultimaParcela->valor
                    );

                $ultimaParcela->update([
                    'valor' =>
                        (
                            $valorAtualCentavos
                            + $diferencaCentavos
                        ) / 100,
                ]);
            }

            /*
             * =====================================================
             * REDUÇÃO DA NOTA
             * =====================================================
             *
             * Reduzimos primeiro as últimas parcelas.
             *
             * Uma parcela nunca poderá ficar abaixo
             * do valor ativo já recebido nela.
             */
            if ($diferencaCentavos < 0) {
                $valorReduzirCentavos =
                    abs($diferencaCentavos);

                $parcelasOrdenadas =
                    $parcelas
                        ->sortByDesc('numero');

                foreach (
                    $parcelasOrdenadas as $parcela
                ) {
                    if (
                        $valorReduzirCentavos
                        <= 0
                    ) {
                        break;
                    }

                    $valorParcelaCentavos =
                        $this->paraCentavos(
                            $parcela->valor
                        );

                    $valorRecebidoParcelaCentavos =
                        $recebidoPorParcela[
                            $parcela->id
                        ] ?? 0;

                    /*
                     * Somente a parte ainda não recebida
                     * pode ser reduzida.
                     */
                    $valorDisponivelReducaoCentavos =
                        max(
                            0,
                            $valorParcelaCentavos
                            - $valorRecebidoParcelaCentavos
                        );

                    if (
                        $valorDisponivelReducaoCentavos
                        <= 0
                    ) {
                        continue;
                    }

                    $reducaoCentavos =
                        min(
                            $valorReduzirCentavos,
                            $valorDisponivelReducaoCentavos
                        );

                    $novoValorParcelaCentavos =
                        $valorParcelaCentavos
                        - $reducaoCentavos;

                    $parcela->update([
                        'valor' =>
                            $novoValorParcelaCentavos
                            / 100,
                    ]);

                    $valorReduzirCentavos -=
                        $reducaoCentavos;
                }

                /*
                 * Pela regra total >= recebido isso normalmente
                 * nunca deverá acontecer.
                 *
                 * Mantemos a proteção para qualquer inconsistência
                 * histórica existente no banco.
                 */
                if ($valorReduzirCentavos > 0) {
                    throw ValidationException::withMessages([
                        'nota' =>
                            'Não foi possível ajustar as parcelas da Conta a Receber ao novo valor da Nota sem afetar valores já recebidos.',
                    ]);
                }
            }

            /*
             * =====================================================
             * CONFERÊNCIA FINAL DAS PARCELAS
             * =====================================================
             */
            $totalParcelasAtualizadoCentavos =
                $this->paraCentavos(
                    $conta
                        ->parcelas()
                        ->sum('valor')
                );

            if (
                $totalParcelasAtualizadoCentavos
                !== $novoTotalCentavos
            ) {
                throw ValidationException::withMessages([
                    'nota' =>
                        'Não foi possível sincronizar corretamente as parcelas da Conta a Receber com o novo total da Nota.',
                ]);
            }

            /*
             * =====================================================
             * STATUS DA CONTA
             * =====================================================
             */
            if ($totalRecebidoCentavos <= 0) {
                $status = 'aberta';
                $dataQuitacao = null;
            } elseif (
                $totalRecebidoCentavos
                >= $novoTotalCentavos
            ) {
                $status = 'quitada';

                $dataQuitacao =
                    $conta
                        ->recebimentos()
                        ->whereNull('estornado_em')
                        ->orderByDesc('data_pagamento')
                        ->value('data_pagamento');
            } else {
                $status = 'parcial';
                $dataQuitacao = null;
            }

            $primeiroVencimento =
                $conta
                    ->parcelas()
                    ->orderBy('numero')
                    ->value('data_vencimento');

            /*
             * =====================================================
             * ATUALIZAR CONTA
             * =====================================================
             */
            $conta->update([
                'valor_original' =>
                    $novoTotalCentavos / 100,

                'data_vencimento' =>
                    $primeiroVencimento,

                'status' =>
                    $status,

                'data_quitacao' =>
                    $dataQuitacao,
            ]);

            return $conta->fresh([
                'parcelas',
                'recebimentos',
            ]);
        });
    }

    private function paraCentavos(
        mixed $valor
    ): int {
        return (int) round(
            (float) $valor * 100
        );
    }
}
