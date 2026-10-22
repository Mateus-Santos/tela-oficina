<?php

namespace App\Actions\ContasPagar;

use App\Models\ContaPagar;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AtualizarContaPagar
{
    public function __construct(
        private VincularComprasContaPagar $vincularCompras
    ) {
    }

    public function execute(ContaPagar $conta, array $dados): ContaPagar
    {
        return DB::transaction(function () use ($conta, $dados) {
            $alterarCompras = array_key_exists(
                'compras',
                $dados
            );

            $compraIds = $dados['compras'] ?? [];

            unset($dados['compras']);
            $conta = ContaPagar::query()
                ->lockForUpdate()
                ->with([
                    'parcelas',
                    'compras',
                ])
                ->findOrFail($conta->id);

            if ($conta->status === 'cancelada') {
                throw new InvalidArgumentException(
                    'Não é possível alterar uma conta cancelada.'
                );
            }

            $valorPago = (float) $conta->pagamentosAtivos()->sum('valor');

            if (
                $conta->status === 'paga'
                || $valorPago >= (float) $conta->valor
            ) {
                throw new InvalidArgumentException(
                    'Não é possível alterar uma conta que já foi totalmente paga.'
                );
            }

            $quantidadeParcelas = $conta->parcelas->count();

            /*
             * Se a conta já possui compras vinculadas
             * e o agrupamento não está sendo alterado,
             * valor e fornecedor precisam continuar
             * coerentes com os vínculos existentes.
             */
            if (
                !$alterarCompras
                && $conta->compras->isNotEmpty()
            ) {
                $valorVinculado = round(
                    (float) $conta->compras->sum(
                        fn ($compra) =>
                            (float) $compra
                                ->pivot
                                ->valor_vinculado
                    ),
                    2
                );

                if (
                    array_key_exists('valor', $dados)
                    && abs(
                        round(
                            (float) $dados['valor'],
                            2
                        )
                        - $valorVinculado
                    ) > 0.009
                ) {
                    throw new InvalidArgumentException(
                        'O valor de uma conta com compras vinculadas deve permanecer igual ao total das compras.'
                    );
                }

                $fornecedorVinculado = (int)
                    $conta->compras
                        ->first()
                        ->fornecedor_id;

                if (
                    array_key_exists(
                        'fornecedor_id',
                        $dados
                    )
                    && $dados['fornecedor_id'] !== null
                    && (int) $dados['fornecedor_id']
                        !== $fornecedorVinculado
                ) {
                    throw new InvalidArgumentException(
                        'O fornecedor da conta deve permanecer igual ao fornecedor das compras vinculadas.'
                    );
                }
            }

            if ($quantidadeParcelas > 1) {
                if (
                    array_key_exists('valor', $dados)
                    && round((float) $dados['valor'], 2) !== round((float) $conta->valor, 2)
                ) {
                    throw new InvalidArgumentException(
                        'O valor de uma conta com múltiplas parcelas deve ser alterado pelo gerenciamento das parcelas.'
                    );
                }

                if (
                    array_key_exists('data_vencimento', $dados)
                    && $dados['data_vencimento'] !== $conta->data_vencimento?->format('Y-m-d')
                ) {
                    throw new InvalidArgumentException(
                        'O vencimento de uma conta com múltiplas parcelas deve ser alterado pelo gerenciamento das parcelas.'
                    );
                }
            }

            if (array_key_exists('valor', $dados)) {
                $novoValor = round((float) $dados['valor'], 2);

                if ($novoValor <= 0) {
                    throw new InvalidArgumentException(
                        'O valor da conta deve ser maior que zero.'
                    );
                }

                if ($valorPago > 0 && $novoValor < $valorPago) {
                    throw new InvalidArgumentException(
                        'O valor da conta não pode ser menor que o total já pago de R$ ' .
                        number_format($valorPago, 2, ',', '.')
                    );
                }

                $dados['valor'] = $novoValor;
            }

            unset($dados['status']);

            $conta->update($dados);

            if ($quantidadeParcelas === 1) {
                $parcela = $conta->parcelas->first();

                $parcela->update([
                    'valor' => $conta->valor,
                    'data_vencimento' => $conta->data_vencimento,
                ]);
            }

            $valorAtual = (float) $conta->valor;

            if ($valorPago <= 0) {
                $status = 'aberta';
            } elseif ($valorPago >= $valorAtual) {
                $status = 'paga';
            } else {
                $status = 'parcialmente_paga';
            }

            $conta->update([
                'status' => $status,
            ]);

            if ($alterarCompras) {
                $conta = $this
                    ->vincularCompras
                    ->execute(
                        $conta,
                        $compraIds
                    );
            }

            return $conta->fresh([
                'parcelas',
                'compras',
            ]);
        });
    }
}
