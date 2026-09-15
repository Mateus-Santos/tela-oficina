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
            $compra = Compra::query()
                ->lockForUpdate()
                ->with('itens.produto')
                ->findOrFail($compra->id);

            if (!$compra->estaAprovada()) {
                throw new InvalidArgumentException(
                    'Somente compras aprovadas podem ser lançadas no estoque.'
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

                if ($item->quantidade_conferida === null) {
                    throw new InvalidArgumentException(
                        "O item '{$item->descricao}' ainda não foi conferido."
                    );
                }

                $quantidade = (float) $item->quantidade_conferida;

                if ($quantidade <= 0) {
                    throw new InvalidArgumentException(
                        "A quantidade conferida do item '{$item->descricao}' deve ser maior que zero."
                    );
                }

                if (!$item->produto) {
                    throw new InvalidArgumentException(
                        "O produto do item '{$item->descricao}' não foi encontrado."
                    );
                }
            }

            foreach ($compra->itens as $item) {
                $this->registrarEntrada->execute(
                    $item->produto,
                    (float) $item->quantidade_conferida,
                    (float) $item->valor_unitario,
                    $item,
                    "Entrada referente à compra #{$compra->id}."
                );
            }
        });
    }
}
