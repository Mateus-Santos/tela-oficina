<?php

namespace App\Actions\Compra;

use App\Actions\Estoque\RegistrarEntrada;
use App\Models\Compra;
use App\Models\MovimentacaoEstoque;
use App\Support\Estoque\Quantidade;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrarEntradaCompra
{
    public function __construct(private RegistrarEntrada $registrarEntrada)
    {
    }

    public function execute(Compra $compra): void
    {
        DB::transaction(function () use ($compra): void {
            $compra = Compra::query()->lockForUpdate()->with('itens.produto')->findOrFail($compra->id);
            if (!$compra->estaAprovada()) {
                throw new InvalidArgumentException('Somente compras aprovadas podem ser lançadas no estoque.');
            }
            if ($compra->itens->isEmpty()) {
                throw new InvalidArgumentException('A compra não possui itens para entrada no estoque.');
            }
            $lancaveis = [];
            foreach ($compra->itens as $item) {
                if ($item->quantidade_conferida === null) {
                    throw new InvalidArgumentException("O item '{$item->descricao}' não foi conferido.");
                }
                $quantidade = Quantidade::milessimos((string) ($item->quantidade_aceita ?? $item->quantidade_conferida));
                if ($quantidade === 0) continue;
                if (!$item->produto) {
                    throw new InvalidArgumentException("Produto ausente no item '{$item->descricao}'.");
                }
                if (MovimentacaoEstoque::query()
                    ->where('tipo', 'entrada')
                    ->where('origem_type', $item->getMorphClass())
                    ->where('origem_id', $item->id)->exists()) {
                    throw new InvalidArgumentException("O item '{$item->descricao}' já foi lançado no estoque.");
                }
                $lancaveis[] = [$item, $quantidade];
            }
            if ($lancaveis === []) {
                throw new InvalidArgumentException('Nenhuma mercadoria aceita para lançar no estoque.');
            }
            foreach ($lancaveis as [$item, $quantidade]) {
                $this->registrarEntrada->execute(
                    $item->produto,
                    Quantidade::formatar($quantidade),
                    (float) $item->valor_unitario,
                    $item,
                    "Entrada aceita da compra #{$compra->id}; divergências segregadas."
                );
            }
        }, 3);
    }
}
