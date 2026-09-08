<?php

namespace App\Actions\Notas;

use App\Actions\Estoque\RegistrarSaida;
use App\Actions\Financeiro\CriarContaReceber;
use App\Models\CategoriaFinanceira;
use App\Models\Nota;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class FinalizarNota
{
    private const CATEGORIA_RECEITA_NOME = 'VENDAS E SERVIÇOS';

    public function __construct(
        private RegistrarSaida $registrarSaida,
        private CriarContaReceber $criarContaReceber
    ) {
    }

    public function execute(Nota $nota): Nota
    {
        return DB::transaction(function () use ($nota) {
            $nota = Nota::query()
                ->lockForUpdate()
                ->with('itens.itemable')
                ->findOrFail($nota->id);

            if ($nota->status !== 'Aberto') {
                throw new InvalidArgumentException(
                    'Somente notas com status Aberto podem ser finalizadas.'
                );
            }

            if ($nota->itens->isEmpty()) {
                throw new InvalidArgumentException(
                    'Não é possível finalizar uma nota sem itens.'
                );
            }

            if (!$nota->cliente_id) {
                throw new InvalidArgumentException(
                    'Não é possível finalizar uma nota sem cliente.'
                );
            }

            if ((float) $nota->total <= 0) {
                throw new InvalidArgumentException(
                    'Não é possível finalizar uma nota com valor total menor ou igual a zero.'
                );
            }

            $contasReceberExistentes = $nota->contaReceber()->count();

            if ($contasReceberExistentes > 0) {
                throw new InvalidArgumentException(
                    "A Nota #{$nota->id} já possui uma Conta a Receber vinculada."
                );
            }

            $itensProdutos = $nota->itens
                ->filter(function ($item) {
                    if (!$item->itemable) {
                        throw new InvalidArgumentException(
                            "O item #{$item->id} possui um produto ou serviço inválido."
                        );
                    }

                    return $item->itemable instanceof Produto;
                })
                ->sortBy(function ($item) {
                    return $item->itemable->id;
                })
                ->values();

            foreach ($itensProdutos as $item) {
                $movimentacaoExistente = DB::table('movimentacao_estoques')
                    ->where(
                        'origem_type',
                        $item->getMorphClass()
                    )
                    ->where(
                        'origem_id',
                        $item->id
                    )
                    ->where(
                        'tipo',
                        'saida'
                    )
                    ->exists();

                if ($movimentacaoExistente) {
                    throw new InvalidArgumentException(
                        "O item #{$item->id} já possui uma baixa de estoque registrada."
                    );
                }

                $this->registrarSaida->execute(
                    produto: $item->itemable,
                    quantidade: (float) $item->quantidade,
                    valorUnitario: (float) $item->valor_unitario,
                    origem: $item,
                    observacoes: "Baixa de estoque da Nota #{$nota->id}, item #{$item->id}."
                );
            }

            $categoriaFinanceira = CategoriaFinanceira::query()
                ->where(
                    'nome',
                    self::CATEGORIA_RECEITA_NOME
                )
                ->where(
                    'tipo',
                    'entrada'
                )
                ->where(
                    'ativo',
                    true
                )
                ->first();

            if (!$categoriaFinanceira) {
                throw new InvalidArgumentException(
                    'A categoria financeira "VENDAS E SERVIÇOS" não está cadastrada ou está inativa.'
                );
            }

            $this->criarContaReceber->execute([
                'cliente_id' => $nota->cliente_id,
                'nota_id' => $nota->id,
                'categoria_financeira_id' => $categoriaFinanceira->id,
                'descricao' => "Conta a receber da Nota #{$nota->id}.",
                'valor_original' => (float) $nota->total,
                'desconto' => 0,
                'juros' => 0,
                'multa' => 0,
                'data_emissao' => now()->toDateString(),
                'data_vencimento' => now()->toDateString(),
                'observacoes' => "Gerada automaticamente pela finalização da Nota #{$nota->id}.",
            ]);

            $nota->update([
                'status' => 'Finalizado',
            ]);

            return $nota->fresh();
        });
    }
}
