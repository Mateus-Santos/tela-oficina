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

            if (!$conta) {
                return null;
            }

            if ($conta->status === 'cancelada') {
                throw ValidationException::withMessages([
                    'nota' => "A Conta a Receber #{$conta->id} vinculada à Nota está cancelada.",
                ]);
            }

            $novoValorOriginalCentavos = $this->paraCentavos($nota->total);

            if ($novoValorOriginalCentavos <= 0) {
                throw ValidationException::withMessages([
                    'nota' => 'O total da Nota deve ser maior que zero para sincronizar o financeiro.',
                ]);
            }

            $descontoCentavos = $this->paraCentavos($conta->desconto);
            $jurosCentavos = $this->paraCentavos($conta->juros);
            $multaCentavos = $this->paraCentavos($conta->multa);

            $novoValorDevidoCentavos =
                $novoValorOriginalCentavos
                - $descontoCentavos
                + $jurosCentavos
                + $multaCentavos;

            if ($novoValorDevidoCentavos <= 0) {
                throw ValidationException::withMessages([
                    'nota' => 'O novo valor devido da Conta a Receber deve ser maior que zero.',
                ]);
            }

            $recebimentosAtivos = $conta
                ->recebimentos()
                ->whereNull('estornado_em')
                ->lockForUpdate()
                ->get();

            $totalRecebidoCentavos = 0;

            foreach ($recebimentosAtivos as $recebimento) {
                $totalRecebidoCentavos += $this->paraCentavos(
                    $recebimento->valor
                );
            }

            if ($novoValorDevidoCentavos < $totalRecebidoCentavos) {
                $valorEstornarCentavos =
                    $totalRecebidoCentavos
                    - $novoValorDevidoCentavos;

                throw ValidationException::withMessages([
                    'nota' => sprintf(
                        'Não é possível reduzir o valor devido para R$ %s porque existem R$ %s em recebimentos ativos. Estorne ao menos R$ %s antes de salvar esta alteração.',
                        number_format($novoValorDevidoCentavos / 100, 2, ',', '.'),
                        number_format($totalRecebidoCentavos / 100, 2, ',', '.'),
                        number_format($valorEstornarCentavos / 100, 2, ',', '.')
                    ),
                ]);
            }

            $parcelas = $conta
                ->parcelas()
                ->lockForUpdate()
                ->get();

            if ($parcelas->isEmpty()) {
                throw ValidationException::withMessages([
                    'nota' => "A Conta a Receber #{$conta->id} não possui parcelas cadastradas.",
                ]);
            }

            $recebidoPorParcela = [];

            foreach ($parcelas as $parcela) {
                $recebidoPorParcela[$parcela->id] = $this->paraCentavos(
                    $parcela
                        ->recebimentos()
                        ->whereNull('estornado_em')
                        ->sum('valor')
                );
            }

            $totalParcelasCentavos = 0;

            foreach ($parcelas as $parcela) {
                $totalParcelasCentavos += $this->paraCentavos(
                    $parcela->valor
                );
            }

            $diferencaCentavos =
                $novoValorDevidoCentavos
                - $totalParcelasCentavos;

            if ($diferencaCentavos > 0) {
                $ultimaParcela = $parcelas
                    ->sortByDesc('numero')
                    ->first();

                $valorAtualCentavos = $this->paraCentavos(
                    $ultimaParcela->valor
                );

                $ultimaParcela->update([
                    'valor' => ($valorAtualCentavos + $diferencaCentavos) / 100,
                ]);
            }

            if ($diferencaCentavos < 0) {
                $valorReduzirCentavos = abs($diferencaCentavos);

                foreach ($parcelas->sortByDesc('numero') as $parcela) {
                    if ($valorReduzirCentavos <= 0) {
                        break;
                    }

                    $valorParcelaCentavos = $this->paraCentavos(
                        $parcela->valor
                    );

                    $valorRecebidoParcelaCentavos =
                        $recebidoPorParcela[$parcela->id] ?? 0;

                    $valorDisponivelReducaoCentavos = max(
                        0,
                        $valorParcelaCentavos
                        - $valorRecebidoParcelaCentavos
                    );

                    if ($valorDisponivelReducaoCentavos <= 0) {
                        continue;
                    }

                    $reducaoCentavos = min(
                        $valorReduzirCentavos,
                        $valorDisponivelReducaoCentavos
                    );

                    $novoValorParcelaCentavos =
                        $valorParcelaCentavos
                        - $reducaoCentavos;

                    if ($novoValorParcelaCentavos <= 0) {
                        if ($valorRecebidoParcelaCentavos > 0) {
                            throw ValidationException::withMessages([
                                'nota' => 'Não foi possível reduzir as parcelas sem afetar valores já recebidos.',
                            ]);
                        }

                        $parcela->delete();
                    } else {
                        $parcela->update([
                            'valor' => $novoValorParcelaCentavos / 100,
                        ]);
                    }

                    $valorReduzirCentavos -= $reducaoCentavos;
                }

                if ($valorReduzirCentavos > 0) {
                    throw ValidationException::withMessages([
                        'nota' => 'Não foi possível ajustar as parcelas da Conta a Receber ao novo valor devido sem afetar valores já recebidos.',
                    ]);
                }
            }

            $parcelasRestantes = $conta
                ->parcelas()
                ->lockForUpdate()
                ->orderBy('numero')
                ->get();

            if ($parcelasRestantes->isEmpty()) {
                throw ValidationException::withMessages([
                    'nota' => 'A Conta a Receber precisa possuir ao menos uma parcela.',
                ]);
            }

            $totalParcelasAtualizadoCentavos = $this->paraCentavos(
                $conta->parcelas()->sum('valor')
            );

            if ($totalParcelasAtualizadoCentavos !== $novoValorDevidoCentavos) {
                throw ValidationException::withMessages([
                    'nota' => 'Não foi possível sincronizar corretamente as parcelas da Conta a Receber com o novo valor devido.',
                ]);
            }

            if ($totalRecebidoCentavos <= 0) {
                $status = 'aberta';
                $dataQuitacao = null;
            } elseif ($totalRecebidoCentavos >= $novoValorDevidoCentavos) {
                $status = 'quitada';
                $dataQuitacao = $conta
                    ->recebimentos()
                    ->whereNull('estornado_em')
                    ->orderByDesc('data_pagamento')
                    ->value('data_pagamento');
            } else {
                $status = 'parcial';
                $dataQuitacao = null;
            }

            $primeiroVencimento = $conta
                ->parcelas()
                ->orderBy('numero')
                ->value('data_vencimento');

            $conta->update([
                'valor_original' => $novoValorOriginalCentavos / 100,
                'data_vencimento' => $primeiroVencimento,
                'status' => $status,
                'data_quitacao' => $dataQuitacao,
            ]);

            return $conta->fresh([
                'parcelas',
                'recebimentos',
            ]);
        });
    }

    private function paraCentavos(mixed $valor): int
    {
        return (int) round((float) $valor * 100);
    }
}
