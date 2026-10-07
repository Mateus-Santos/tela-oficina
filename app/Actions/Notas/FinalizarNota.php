<?php

namespace App\Actions\Notas;

use App\Actions\Estoque\RegistrarSaida;
use App\Actions\Financeiro\CriarContaReceber;
use App\Models\CategoriaFinanceira;
use App\Models\ContaReceber;
use App\Models\Nota;
use App\Models\OrdemServico;
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

    public function execute(
        Nota $nota,
        array $dadosFinanceiros
    ): Nota {
        return DB::transaction(
            function () use (
                $nota,
                $dadosFinanceiros
            ) {
                $nota = Nota::query()
                    ->lockForUpdate()
                    ->with([
                        'itens.itemable',
                        'contaReceber',
                    ])
                    ->findOrFail($nota->id);

                /*
                 * =====================================================
                 * 1. VALIDAR ESTADO DA NOTA
                 * =====================================================
                 */
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

                $valorNotaCentavos =
                    $this->paraCentavos(
                        $nota->total
                    );

                if ($valorNotaCentavos <= 0) {
                    throw new InvalidArgumentException(
                        'Não é possível finalizar uma nota com valor total menor ou igual a zero.'
                    );
                }

                if (!$nota->cliente_id) {
                    throw new InvalidArgumentException(
                        'Não é possível finalizar uma nota sem cliente.'
                    );
                }

                /*
                 * =====================================================
                 * 2. VERIFICAR CONTA A RECEBER
                 * =====================================================
                 */
                $jaPossuiContaReceber =
                    $nota->contaReceber !== null;

                $categoriaFinanceira = null;
                $parcelas = [];

                /*
                 * =====================================================
                 * 3. VALIDAR FINANCEIRO
                 * =====================================================
                 */
                if ($jaPossuiContaReceber) {
                    $this->validarContaReceberExistente(
                        $nota,
                        $valorNotaCentavos
                    );
                } else {
                    $categoriaFinanceiraId =
                        $dadosFinanceiros[
                            'categoria_financeira_id'
                        ] ?? null;

                    if (!$categoriaFinanceiraId) {
                        throw ValidationException::withMessages([
                            'categoria_financeira_id' =>
                                'Informe a categoria financeira.',
                        ]);
                    }

                    $categoriaFinanceira =
                        CategoriaFinanceira::query()
                            ->whereKey(
                                $categoriaFinanceiraId
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
                        throw ValidationException::withMessages([
                            'categoria_financeira_id' =>
                                'A categoria financeira informada não existe, está inativa ou não é uma categoria de entrada.',
                        ]);
                    }

                    $parcelas =
                        $dadosFinanceiros['parcelas']
                        ?? null;

                    if (
                        !is_array($parcelas)
                        || empty($parcelas)
                    ) {
                        throw ValidationException::withMessages([
                            'parcelas' =>
                                'Informe ao menos uma parcela para finalizar a nota.',
                        ]);
                    }

                    $totalParcelasCentavos = 0;

                    foreach (
                        $parcelas as $indice => $parcela
                    ) {
                        $numero = filter_var(
                            $parcela['numero'] ?? null,
                            FILTER_VALIDATE_INT
                        );

                        if (
                            $numero === false
                            || $numero <= 0
                        ) {
                            throw ValidationException::withMessages([
                                "parcelas.{$indice}.numero" =>
                                    'O número da parcela deve ser um inteiro maior que zero.',
                            ]);
                        }

                        $valorCentavos =
                            $this->paraCentavos(
                                $parcela['valor']
                                ?? 0
                            );

                        if ($valorCentavos <= 0) {
                            throw ValidationException::withMessages([
                                "parcelas.{$indice}.valor" =>
                                    'O valor da parcela deve ser maior que zero.',
                            ]);
                        }

                        $dataVencimento =
                            $parcela[
                                'data_vencimento'
                            ] ?? null;

                        if (
                            !$dataVencimento
                            || !$this->dataValida(
                                $dataVencimento
                            )
                        ) {
                            throw ValidationException::withMessages([
                                "parcelas.{$indice}.data_vencimento" =>
                                    'A data de vencimento da parcela é inválida.',
                            ]);
                        }

                        $totalParcelasCentavos +=
                            $valorCentavos;
                    }

                    if (
                        $totalParcelasCentavos
                        !== $valorNotaCentavos
                    ) {
                        throw ValidationException::withMessages([
                            'parcelas' => sprintf(
                                'A soma das parcelas deve ser igual ao total da nota. Valor esperado: R$ %s.',
                                number_format(
                                    $valorNotaCentavos / 100,
                                    2,
                                    ',',
                                    '.'
                                )
                            ),
                        ]);
                    }
                }

                /*
                 * =====================================================
                 * 4. IDENTIFICAR PRODUTOS E ORDENS DE SERVIÇO
                 * =====================================================
                 */
                $itensProdutos = $nota
                    ->itens
                    ->filter(
                        function ($item) {
                            if (!$item->itemable) {
                                throw new InvalidArgumentException(
                                    "O item #{$item->id} possui um produto ou serviço inválido."
                                );
                            }

                            return $item->itemable
                                instanceof Produto;
                        }
                    )
                    ->sortBy(
                        function ($item) {
                            return $item
                                ->itemable
                                ->id;
                        }
                    )
                    ->values();

                $itensOrdensServico = $nota
                    ->itens
                    ->filter(
                        fn ($item) =>
                            $item->itemable
                            instanceof OrdemServico
                    )
                    ->sortBy(
                        fn ($item) =>
                            $item->itemable->id
                    )
                    ->values();

                /*
                 * =====================================================
                 * 5. VALIDAR ORDENS DE SERVIÇO
                 * =====================================================
                 *
                 * Uma O.S. cancelada não pode compor uma Nota que está
                 * sendo finalizada.
                 */
                foreach ($itensOrdensServico as $item) {
                    $ordemServico =
                        OrdemServico::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $item->itemable->id
                            );

                    if (
                        $ordemServico->status
                        === 'cancelada'
                    ) {
                        throw new InvalidArgumentException(
                            "A O.S. #{$ordemServico->id} está cancelada e não pode ser finalizada junto com a Nota."
                        );
                    }
                }

                /*
                 * =====================================================
                 * 6. IMPEDIR DUPLICIDADE DE BAIXA DE ESTOQUE
                 * =====================================================
                 */
                foreach ($itensProdutos as $item) {
                    $movimentacaoExistente =
                        DB::table(
                            'movimentacao_estoques'
                        )
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
                }

                /*
                 * =====================================================
                 * 7. REGISTRAR SAÍDA DO ESTOQUE
                 * =====================================================
                 */
                foreach ($itensProdutos as $item) {
                    $this->registrarSaida->execute(
                        produto: $item->itemable,
                        quantidade: (float) $item->quantidade,
                        valorUnitario: (float) $item->valor_unitario,
                        origem: $item,
                        observacoes:
                            "Baixa de estoque da Nota #{$nota->id}, item #{$item->id}."
                    );
                }

                /*
                 * =====================================================
                 * 8. FINALIZAR ORDENS DE SERVIÇO
                 * =====================================================
                 */
                foreach ($itensOrdensServico as $item) {
                    $ordemServico =
                        OrdemServico::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $item->itemable->id
                            );

                    if (
                        $ordemServico->status
                        === 'finalizada'
                    ) {
                        continue;
                    }

                    $ordemServico->update([
                        'status' =>
                            'finalizada',

                        'data_fechamento' =>
                            $ordemServico
                                ->data_fechamento
                            ?? now(),
                    ]);
                }

                /*
                 * =====================================================
                 * 9. FINALIZAR NOTA
                 * =====================================================
                 */
                $nota->update([
                    'status' => 'Finalizado',
                ]);

                /*
                 * =====================================================
                 * 10. CRIAR CONTA A RECEBER SE NÃO EXISTIR
                 * =====================================================
                 */
                if (!$jaPossuiContaReceber) {
                    $this->criarContaReceber->execute([
                        'cliente_id' =>
                            $nota->cliente_id,

                        'nota_id' =>
                            $nota->id,

                        'categoria_financeira_id' =>
                            $categoriaFinanceira->id,

                        'descricao' =>
                            "Conta a receber da Nota #{$nota->id}.",

                        'valor_original' =>
                            $valorNotaCentavos / 100,

                        'desconto' =>
                            0,

                        'juros' =>
                            0,

                        'multa' =>
                            0,

                        'data_emissao' =>
                            now()->toDateString(),

                        'observacoes' =>
                            "Gerada automaticamente pela finalização da Nota #{$nota->id}.",

                        'parcelas' =>
                            $parcelas,
                    ]);
                }

                /*
                 * =====================================================
                 * 11. RETORNAR NOTA ATUALIZADA
                 * =====================================================
                 */
                return $nota->fresh([
                    'itens.itemable',
                    'contaReceber.parcelas',
                ]);
            }
        );
    }

    private function validarContaReceberExistente(
        Nota $nota,
        int $valorNotaCentavos
    ): void {
        if (!$nota->contaReceber) {
            throw ValidationException::withMessages([
                'finalizacao' =>
                    'A Conta a Receber vinculada à Nota não foi encontrada.',
            ]);
        }

        $contaReceber =
            ContaReceber::query()
                ->lockForUpdate()
                ->findOrFail(
                    $nota->contaReceber->id
                );

        if (
            (int) $contaReceber->nota_id
            !== (int) $nota->id
        ) {
            throw ValidationException::withMessages([
                'finalizacao' =>
                    'A Conta a Receber vinculada não pertence à Nota que está sendo finalizada.',
            ]);
        }

        if (
            $contaReceber->status
            === 'cancelada'
        ) {
            throw ValidationException::withMessages([
                'finalizacao' =>
                    'A Conta a Receber vinculada à Nota está cancelada. Regularize o financeiro antes de finalizar.',
            ]);
        }

        $valorContaCentavos =
            $this->paraCentavos(
                $contaReceber->valor_original
            );

        if (
            $valorContaCentavos
            !== $valorNotaCentavos
        ) {
            throw ValidationException::withMessages([
                'finalizacao' => sprintf(
                    'A Conta a Receber vinculada está com valor diferente do total atual da Nota. Nota: R$ %s. Conta: R$ %s.',
                    number_format(
                        $valorNotaCentavos / 100,
                        2,
                        ',',
                        '.'
                    ),
                    number_format(
                        $valorContaCentavos / 100,
                        2,
                        ',',
                        '.'
                    )
                ),
            ]);
        }

        $parcelas =
            $contaReceber
                ->parcelas()
                ->lockForUpdate()
                ->get();

        if ($parcelas->isEmpty()) {
            throw ValidationException::withMessages([
                'finalizacao' =>
                    'A Conta a Receber vinculada não possui parcelas. Regularize o financeiro antes de finalizar.',
            ]);
        }

        $totalParcelasCentavos = 0;

        foreach ($parcelas as $parcela) {
            $valorParcelaCentavos =
                $this->paraCentavos(
                    $parcela->valor
                );

            if ($valorParcelaCentavos <= 0) {
                throw ValidationException::withMessages([
                    'finalizacao' =>
                        "A parcela #{$parcela->numero} da Conta a Receber possui valor inválido.",
                ]);
            }

            $totalParcelasCentavos +=
                $valorParcelaCentavos;
        }

        if (
            $totalParcelasCentavos
            !== $valorContaCentavos
        ) {
            throw ValidationException::withMessages([
                'finalizacao' => sprintf(
                    'As parcelas da Conta a Receber não correspondem ao valor da Nota. Total das parcelas: R$ %s. Valor esperado: R$ %s.',
                    number_format(
                        $totalParcelasCentavos / 100,
                        2,
                        ',',
                        '.'
                    ),
                    number_format(
                        $valorContaCentavos / 100,
                        2,
                        ',',
                        '.'
                    )
                ),
            ]);
        }
    }

    private function paraCentavos(
        mixed $valor
    ): int {
        return (int) round(
            (float) $valor * 100
        );
    }

    private function dataValida(
        mixed $data
    ): bool {
        if (!is_string($data)) {
            return false;
        }

        $objeto =
            \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $data
            );

        return $objeto !== false
            && $objeto->format('Y-m-d')
                === $data;
    }
}
