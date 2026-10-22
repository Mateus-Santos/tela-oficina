<?php

namespace App\Actions\ContasPagar;

use App\Models\Compra;
use App\Models\ContaPagar;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VincularComprasContaPagar
{
    public function execute(
        ContaPagar $conta,
        array $compraIds
    ): ContaPagar {
        return DB::transaction(
            function () use ($conta, $compraIds) {
                $conta = ContaPagar::query()
                    ->lockForUpdate()
                    ->findOrFail($conta->id);

                if ($conta->status === 'cancelada') {
                    throw new InvalidArgumentException(
                        'Não é possível alterar as compras de uma conta cancelada.'
                    );
                }

                $valorPago = (float) $conta
                    ->pagamentosAtivos()
                    ->sum('valor');

                if ($valorPago > 0) {
                    throw new InvalidArgumentException(
                        'Não é possível alterar as compras de uma conta que possui pagamentos ativos.'
                    );
                }

                if ($compraIds === []) {
                    $conta->compras()->detach();

                    $conta->update([
                        'compra_id' => null,
                    ]);

                    return $conta->fresh([
                        'compras',
                        'fornecedor',
                    ]);
                }

                $ids = array_map(
                    static fn ($id) => (int) $id,
                    array_values($compraIds)
                );

                if (
                    count($ids)
                    !== count(array_unique($ids))
                ) {
                    throw new InvalidArgumentException(
                        'Uma compra foi informada mais de uma vez.'
                    );
                }

                foreach ($ids as $id) {
                    if ($id <= 0) {
                        throw new InvalidArgumentException(
                            'Uma das compras informadas é inválida.'
                        );
                    }
                }

                $compras = Compra::query()
                    ->whereIn('id', $ids)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($compras->count() !== count($ids)) {
                    throw new InvalidArgumentException(
                        'Uma das compras informadas não existe.'
                    );
                }

                $fornecedorId = null;
                $valorTotal = 0.0;

                foreach ($ids as $id) {
                    $compra = $compras->get($id);

                    if (!$compra->estaAprovada()) {
                        throw new InvalidArgumentException(
                            'Somente compras aprovadas podem ser vinculadas a uma conta a pagar.'
                        );
                    }

                    if ($fornecedorId === null) {
                        $fornecedorId =
                            (int) $compra->fornecedor_id;
                    } elseif (
                        $fornecedorId
                        !== (int) $compra->fornecedor_id
                    ) {
                        throw new InvalidArgumentException(
                            'Todas as compras agrupadas devem pertencer ao mesmo fornecedor.'
                        );
                    }

                    $valorCompra =
                        round(
                            (float) $compra->valor_total,
                            2
                        );

                    if ($valorCompra <= 0) {
                        throw new InvalidArgumentException(
                            'Todas as compras vinculadas devem possuir valor total maior que zero.'
                        );
                    }

                    $valorTotal += $valorCompra;
                }

                $conflitoPivo = DB::table(
                    'compra_conta_pagar'
                )
                    ->whereIn('compra_id', $ids)
                    ->where(
                        'conta_pagar_id',
                        '!=',
                        $conta->id
                    )
                    ->exists();

                if ($conflitoPivo) {
                    throw new InvalidArgumentException(
                        'Uma das compras já está vinculada a outra conta a pagar.'
                    );
                }

                $conflitoLegado =
                    ContaPagar::query()
                        ->whereIn(
                            'compra_id',
                            $ids
                        )
                        ->where(
                            'id',
                            '!=',
                            $conta->id
                        )
                        ->exists();

                if ($conflitoLegado) {
                    throw new InvalidArgumentException(
                        'Uma das compras já está vinculada a outra conta a pagar.'
                    );
                }

                if (
                    $conta->fornecedor_id !== null
                    && (int) $conta->fornecedor_id
                        !== $fornecedorId
                ) {
                    throw new InvalidArgumentException(
                        'O fornecedor da conta a pagar deve ser o mesmo das compras agrupadas.'
                    );
                }

                $valorTotal = round(
                    $valorTotal,
                    2
                );

                if (
                    abs(
                        $valorTotal
                        - (float) $conta->valor
                    ) > 0.009
                ) {
                    throw new InvalidArgumentException(
                        'O valor da conta a pagar deve ser igual à soma das compras vinculadas.'
                    );
                }

                $dadosSync = [];

                foreach ($ids as $id) {
                    $compra = $compras->get($id);

                    $dadosSync[$id] = [
                        'valor_vinculado' => round(
                            (float) $compra->valor_total,
                            2
                        ),
                    ];
                }

                $conta->compras()->sync(
                    $dadosSync
                );

                $conta->update([
                    'fornecedor_id' =>
                        $conta->fornecedor_id
                        ?? $fornecedorId,

                    'compra_id' =>
                        count($ids) === 1
                            ? $ids[0]
                            : null,
                ]);

                return $conta->fresh([
                    'compras',
                    'fornecedor',
                ]);
            }
        );
    }
}
