<?php

namespace App\Actions\Compra;

use App\Models\Compra;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelarCompra
{
    public function execute(Compra $compra): Compra
    {
        return DB::transaction(function () use ($compra) {
            $compra = Compra::query()
                ->lockForUpdate()
                ->with('itens.movimentacoesEstoque')
                ->findOrFail($compra->id);

            if ($compra->estaCancelada()) {
                throw new InvalidArgumentException(
                    'A compra já está cancelada.'
                );
            }

            $estoqueLancado = $compra->itens->contains(
                function ($item) {
                    return $item->movimentacoesEstoque
                        ->contains('tipo', 'entrada');
                }
            );

            if ($estoqueLancado) {
                throw new InvalidArgumentException(
                    'Uma compra que já possui entrada no estoque não pode ser cancelada.'
                );
            }

            if (
                !$compra->estaPendente()
                && !$compra->estaEmConferencia()
                && !$compra->estaAprovada()
            ) {
                throw new InvalidArgumentException(
                    'O status atual da compra não permite cancelamento.'
                );
            }

            // Um estorno de estoque não deve deixar uma conta a pagar ativa
            // vinculada a uma compra cancelada. O financeiro é tratado à parte.
            $temFinanceiroAtivo = $compra->contaPagar()
                ->where('status', '!=', 'cancelada')
                ->exists()
                || $compra->contasPagar()
                    ->where('contas_pagar.status', '!=', 'cancelada')
                    ->exists();

            if ($temFinanceiroAtivo) {
                throw new InvalidArgumentException(
                    'A compra possui conta a pagar ativa vinculada. Regularize ou cancele o vínculo financeiro antes de cancelar/estornar a compra.'
                );
            }

            $compra->update([
                'status' => Compra::STATUS_CANCELADA,
            ]);

            return $compra->fresh();
        });
    }
}
