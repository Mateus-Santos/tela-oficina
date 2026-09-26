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
    public function __construct(
        private RegistrarSaida $registrarSaida,
        private CriarContaReceber $criarContaReceber
    ) {
    }

    public function execute(Nota $nota, array $dadosFinanceiros): Nota
    {
        return DB::transaction(function () use ($nota, $dadosFinanceiros) {
            $nota = Nota::query()
                ->lockForUpdate()
                ->with([
                    'itens.itemable',
                    'contaReceber',
                ])
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

            $valorNotaCentavos = $this->paraCentavos($nota->total);

            if ($valorNotaCentavos <= 0) {
                throw new InvalidArgumentException(
                    'Não é possível finalizar uma nota com valor total menor ou igual a zero.'
                );
            }

            if ($nota->contaReceber) {
                throw new InvalidArgumentException(
                    "A Nota #{$nota->id} já possui uma conta a receber."
                );
            }

            $categoriaFinanceiraId = $dadosFinanceiros['categoria_financeira_id'] ?? null;

            if (!$categoriaFinanceiraId) {
                throw ValidationException::withMessages([
                    'categoria_financeira_id' => 'Informe a categoria financeira.',
                ]);
            }

            $categoriaFinanceira = CategoriaFinanceira::query()
                ->whereKey($categoriaFinanceiraId)
                ->where('tipo', 'entrada')
                ->where('ativo', true)
                ->first();

            if (!$categoriaFinanceira) {
                throw ValidationException::withMessages([
                    'categoria_financeira_id' => 'A categoria financeira informada não existe, está inativa ou não é uma categoria de entrada.',
                ]);
            }

            $parcelas = $dadosFinanceiros['parcelas'] ?? null;

            if (!is_array($parcelas) || empty($parcelas)) {
                throw ValidationException::withMessages([
                    'parcelas' => 'Informe ao menos uma parcela para finalizar a nota.',
                ]);
            }

            $totalParcelasCentavos = 0;

            foreach ($parcelas as $indice => $parcela) {
                $numero = filter_var(
                    $parcela['numero'] ?? null,
                    FILTER_VALIDATE_INT
                );

                if ($numero === false || $numero <= 0) {
                    throw ValidationException::withMessages([
                        "parcelas.{$indice}.numero" => 'O número da parcela deve ser um inteiro maior que zero.',
                    ]);
                }

                $valorCentavos = $this->paraCentavos($parcela['valor'] ?? 0);

                if ($valorCentavos <= 0) {
                    throw ValidationException::withMessages([
                        "parcelas.{$indice}.valor" => 'O valor da parcela deve ser maior que zero.',
                    ]);
                }

                $dataVencimento = $parcela['data_vencimento'] ?? null;

                if (!$dataVencimento || !$this->dataValida($dataVencimento)) {
                    throw ValidationException::withMessages([
                        "parcelas.{$indice}.data_vencimento" => 'A data de vencimento da parcela é inválida.',
                    ]);
                }

                $totalParcelasCentavos += $valorCentavos;
            }

            if ($totalParcelasCentavos !== $valorNotaCentavos) {
                throw ValidationException::withMessages([
                    'parcelas' => sprintf(
                        'A soma das parcelas deve ser igual ao total da nota. Valor esperado: R$ %s.',
                        number_format($valorNotaCentavos / 100, 2, ',', '.')
                    ),
                ]);
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
                    ->where('origem_type', $item->getMorphClass())
                    ->where('origem_id', $item->id)
                    ->where('tipo', 'saida')
                    ->exists();

                if ($movimentacaoExistente) {
                    throw new InvalidArgumentException(
                        "O item #{$item->id} já possui uma baixa de estoque registrada."
                    );
                }
            }

            foreach ($itensProdutos as $item) {
                $this->registrarSaida->execute(
                    produto: $item->itemable,
                    quantidade: (float) $item->quantidade,
                    valorUnitario: (float) $item->valor_unitario,
                    origem: $item,
                    observacoes: "Baixa de estoque da Nota #{$nota->id}, item #{$item->id}."
                );
            }

            /*
             * A nota precisa estar finalizada antes da criação da conta a receber.
             *
             * Como toda a operação está dentro da mesma transação, qualquer erro
             * posterior desfaz também esta alteração de status.
             */
            $nota->update([
                'status' => 'Finalizado',
            ]);

            $this->criarContaReceber->execute([
                'cliente_id' => $nota->cliente_id,
                'nota_id' => $nota->id,
                'categoria_financeira_id' => $categoriaFinanceira->id,
                'descricao' => "Conta a receber da Nota #{$nota->id}.",
                'valor_original' => $valorNotaCentavos / 100,
                'desconto' => 0,
                'juros' => 0,
                'multa' => 0,
                'data_emissao' => now()->toDateString(),
                'observacoes' => "Gerada automaticamente pela finalização da Nota #{$nota->id}.",
                'parcelas' => $parcelas,
            ]);

            return $nota->fresh([
                'contaReceber.parcelas',
            ]);
        });
    }

    private function paraCentavos(mixed $valor): int
    {
        return (int) round((float) $valor * 100);
    }

    private function dataValida(mixed $data): bool
    {
        if (!is_string($data)) {
            return false;
        }

        $objeto = \DateTimeImmutable::createFromFormat('!Y-m-d', $data);

        return $objeto !== false
            && $objeto->format('Y-m-d') === $data;
    }
}
