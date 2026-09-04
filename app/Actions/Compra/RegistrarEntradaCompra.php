<?php

namespace App\Actions\Compra;

use App\Actions\Estoque\RegistrarEntrada;
use App\Models\Compra;
use App\Models\MovimentacaoEstoque;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarEntradaCompra
{
    public function __construct(
        private RegistrarEntrada $registrarEntrada
    ) {
    }

    public function execute(Compra $compra): void
    {
        DB::transaction(function () use ($compra) {
            $compra->loadMissing('itens.produto');

            if ($compra->status === 'cancelada') {
                throw new InvalidArgumentException(
                    'Uma compra cancelada não pode ser lançada no estoque.'
                );
            }

            if ($compra->itens->isEmpty()) {
                throw new InvalidArgumentException(
                    'A compra não possui itens para entrada no estoque.'
                );
            }

            foreach ($compra->itens as $item) {
                $jaLancado = MovimentacaoEstoque::query()
                    ->where('tipo', 'entrada')
                    ->where('origem_type', $item->getMorphClass())
                    ->where('origem_id', $item->id)
                    ->exists();

                if ($jaLancado) {
                    throw new InvalidArgumentException(
                        "O item '{$item->descricao}' da compra #{$compra->id} já foi lançado no estoque."
                    );
                }

                $quantidade = $item->quantidade_conferida !== null
                    ? (float) $item->quantidade_conferida
                    : (float) $item->quantidade;

                if ($quantidade <= 0) {
                    throw new InvalidArgumentException(
                        "A quantidade do produto {$item->descricao} deve ser maior que zero."
                    );
                }
            }

            foreach ($compra->itens as $item) {
                $quantidade = $item->quantidade_conferida !== null
                    ? (float) $item->quantidade_conferida
                    : (float) $item->quantidade;

                $this->registrarEntrada->execute(
                    $item->produto,
                    $quantidade,
                    (float) $item->valor_unitario,
                    $item,
                    "Entrada referente à compra #{$compra->id}."
                );
            }
        });
    }
}
