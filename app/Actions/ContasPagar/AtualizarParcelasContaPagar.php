<?php

namespace App\Actions\ContasPagar;

use App\Models\ContaPagar;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AtualizarParcelasContaPagar
{
    public function execute(ContaPagar $conta, array $parcelas): ContaPagar
    {
        return DB::transaction(function () use ($conta, $parcelas) {
            $conta = ContaPagar::query()
                ->lockForUpdate()
                ->with('parcelas.pagamentosAtivos')
                ->findOrFail($conta->id);

            if ($conta->status === 'cancelada') {
                throw new InvalidArgumentException(
                    'Não é possível alterar as parcelas de uma conta cancelada.'
                );
            }

            $valorPago = (float) $conta->pagamentosAtivos()->sum('valor');

            if (
                $conta->status === 'paga'
                || $valorPago >= (float) $conta->valor
            ) {
                throw new InvalidArgumentException(
                    'Não é possível alterar as parcelas de uma conta que já foi totalmente paga.'
                );
            }

            if (empty($parcelas)) {
                throw new InvalidArgumentException(
                    'A conta deve possuir pelo menos uma parcela.'
                );
            }

            $existentes = $conta->parcelas->keyBy('id');
            $idsRecebidos = [];
            $dadosNormalizados = [];

            foreach (array_values($parcelas) as $indice => $dados) {
                $id = isset($dados['id']) && $dados['id'] !== ''
                    ? (int) $dados['id']
                    : null;

                if ($id !== null) {
                    if (!$existentes->has($id)) {
                        throw new InvalidArgumentException(
                            'Uma das parcelas informadas não pertence a esta conta.'
                        );
                    }

                    if (isset($idsRecebidos[$id])) {
                        throw new InvalidArgumentException(
                            'Uma parcela foi informada mais de uma vez.'
                        );
                    }

                    $idsRecebidos[$id] = true;
                }

                $valor = round((float) ($dados['valor'] ?? 0), 2);

                if ($valor <= 0) {
                    throw new InvalidArgumentException(
                        'O valor de todas as parcelas deve ser maior que zero.'
                    );
                }

                $dataVencimento = $dados['data_vencimento'] ?? null;

                if (!$dataVencimento || !strtotime($dataVencimento)) {
                    throw new InvalidArgumentException(
                        'A data de vencimento de todas as parcelas deve ser válida.'
                    );
                }

                if (
                    $conta->data_emissao
                    && $dataVencimento < $conta->data_emissao->format('Y-m-d')
                ) {
                    throw new InvalidArgumentException(
                        'Nenhuma parcela pode ter vencimento anterior à data de emissão da conta.'
                    );
                }

                $parcelaExistente = $id !== null
                    ? $existentes->get($id)
                    : null;

                $valorPagoParcela = $parcelaExistente
                    ? (float) $parcelaExistente->pagamentosAtivos->sum('valor')
                    : 0;

                if ($valorPagoParcela > $valor) {
                    throw new InvalidArgumentException(
                        'O valor da parcela não pode ser menor que o total já pago de R$ ' .
                        number_format(
                            $valorPagoParcela,
                            2,
                            ',',
                            '.'
                        )
                    );
                }

                $dadosNormalizados[] = [
                    'id' => $id,
                    'valor' => $valor,
                    'data_vencimento' => $dataVencimento,
                    'numero' => $indice + 1,
                ];
            }

            foreach ($existentes as $parcela) {
                if (isset($idsRecebidos[$parcela->id])) {
                    continue;
                }

                $valorPagoParcela = (float) $parcela
                    ->pagamentosAtivos
                    ->sum('valor');

                if ($valorPagoParcela > 0) {
                    throw new InvalidArgumentException(
                        'Não é possível remover uma parcela que possui pagamentos ativos.'
                    );
                }
            }

            $valorTotal = round(
                array_sum(
                    array_column(
                        $dadosNormalizados,
                        'valor'
                    )
                ),
                2
            );

            if ($valorTotal <= 0) {
                throw new InvalidArgumentException(
                    'O valor total das parcelas deve ser maior que zero.'
                );
            }

            if ($valorTotal < $valorPago) {
                throw new InvalidArgumentException(
                    'A soma das parcelas não pode ser menor que o total já pago de R$ ' .
                    number_format(
                        $valorPago,
                        2,
                        ',',
                        '.'
                    )
                );
            }

            $temporario = 1000000;

            foreach ($existentes as $parcela) {
                $parcela->update([
                    'numero' => $temporario + $parcela->id,
                ]);
            }

            foreach ($dadosNormalizados as $dados) {
                if ($dados['id'] !== null) {
                    $parcela = $existentes->get($dados['id']);

                    $parcela->update([
                        'numero' => $dados['numero'],
                        'valor' => $dados['valor'],
                        'data_vencimento' => $dados['data_vencimento'],
                    ]);

                    continue;
                }

                $conta->parcelas()->create([
                    'numero' => $dados['numero'],
                    'valor' => $dados['valor'],
                    'data_vencimento' => $dados['data_vencimento'],
                ]);
            }

            foreach ($existentes as $parcela) {
                if (!isset($idsRecebidos[$parcela->id])) {
                    $parcela->delete();
                }
            }

            $conta->update([
                'valor' => $valorTotal,
                'data_vencimento' => $dadosNormalizados[0]['data_vencimento'],
                'status' => $valorPago <= 0
                    ? 'aberta'
                    : ($valorPago >= $valorTotal
                        ? 'paga'
                        : 'parcialmente_paga'),
            ]);

            return $conta->fresh('parcelas');
        });
    }
}
