<?php

namespace App\Actions\Financeiro;

use App\Models\ContaReceber;
use App\Models\Nota;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AtualizarContaReceber
{
    public function execute(
        ContaReceber $contaReceber,
        array $dados
    ): ContaReceber {
        return DB::transaction(
            function () use (
                $contaReceber,
                $dados
            ) {
                $contaReceber = ContaReceber::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $contaReceber->id
                    );

                if (
                    in_array(
                        $contaReceber->status,
                        [
                            'quitada',
                            'cancelada',
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'contaReceber' =>
                            'Contas quitadas ou canceladas não podem ser alteradas.',
                    ]);
                }

                if (
                    $contaReceber
                        ->recebimentos()
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'contaReceber' =>
                            'Contas que possuem recebimentos não podem ser alteradas.',
                    ]);
                }

                $nota = null;

                /*
                 * =====================================================
                 * NOTA VINCULADA
                 * =====================================================
                 */
                if (!empty($dados['nota_id'])) {
                    $nota = Nota::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $dados['nota_id']
                        );

                    /*
                     * Nota aberta e finalizada são permitidas.
                     * Apenas Nota cancelada é bloqueada.
                     */
                    if (
                        $nota->status
                        === 'Cancelado'
                    ) {
                        throw ValidationException::withMessages([
                            'nota_id' =>
                                "A Nota #{$nota->id} está cancelada e não pode ser vinculada a uma conta a receber.",
                        ]);
                    }

                    /*
                     * Uma Nota só pode possuir
                     * uma Conta a Receber.
                     */
                    $outraConta = ContaReceber::query()
                        ->where(
                            'nota_id',
                            $nota->id
                        )
                        ->where(
                            'id',
                            '!=',
                            $contaReceber->id
                        )
                        ->exists();

                    if ($outraConta) {
                        throw ValidationException::withMessages([
                            'nota_id' =>
                                "A Nota #{$nota->id} já possui outra conta a receber.",
                        ]);
                    }

                    /*
                     * Se a Nota possui cliente, o cliente informado
                     * precisa ser o mesmo.
                     *
                     * Venda de balcão possui cliente_id NULL
                     * e continua sendo válida.
                     */
                    if (
                        !empty($dados['cliente_id'])
                        && (int) $dados['cliente_id']
                            !== (int) $nota->cliente_id
                    ) {
                        throw ValidationException::withMessages([
                            'cliente_id' =>
                                'O cliente informado não corresponde ao cliente da nota.',
                        ]);
                    }

                    /*
                     * A Nota é a fonte de verdade do cliente.
                     *
                     * Em venda de balcão isso resultará em NULL,
                     * o que é permitido.
                     */
                    $dados['cliente_id'] =
                        $nota->cliente_id;
                }

                /*
                 * Sem Nota, cliente é obrigatório.
                 *
                 * ATENÇÃO:
                 * não podemos simplesmente exigir cliente_id,
                 * porque uma Nota de balcão pode possuir cliente NULL.
                 */
                if (
                    !$nota
                    && empty($dados['cliente_id'])
                ) {
                    throw ValidationException::withMessages([
                        'cliente_id' =>
                            'É necessário informar um cliente ou uma nota vinculada.',
                    ]);
                }

                $valorOriginal =
                    (float) (
                        $dados['valor_original']
                        ?? 0
                    );

                $desconto =
                    (float) (
                        $dados['desconto']
                        ?? 0
                    );

                $juros =
                    (float) (
                        $dados['juros']
                        ?? 0
                    );

                $multa =
                    (float) (
                        $dados['multa']
                        ?? 0
                    );

                if ($valorOriginal <= 0) {
                    throw ValidationException::withMessages([
                        'valor_original' =>
                            'O valor original deve ser maior que zero.',
                    ]);
                }

                if ($desconto < 0) {
                    throw ValidationException::withMessages([
                        'desconto' =>
                            'O desconto não pode ser negativo.',
                    ]);
                }

                if ($juros < 0) {
                    throw ValidationException::withMessages([
                        'juros' =>
                            'Os juros não podem ser negativos.',
                    ]);
                }

                if ($multa < 0) {
                    throw ValidationException::withMessages([
                        'multa' =>
                            'A multa não pode ser negativa.',
                    ]);
                }

                /*
                 * Se existe Nota vinculada, o valor original
                 * deve continuar correspondendo ao total da Nota.
                 */
                if ($nota) {
                    $valorOriginalCentavos =
                        $this->paraCentavos(
                            $valorOriginal
                        );

                    $valorNotaCentavos =
                        $this->paraCentavos(
                            $nota->total
                        );

                    if (
                        $valorOriginalCentavos
                        !== $valorNotaCentavos
                    ) {
                        throw ValidationException::withMessages([
                            'valor_original' =>
                                'O valor original da conta deve ser igual ao total da nota.',
                        ]);
                    }
                }

                $valorDevido =
                    $valorOriginal
                    - $desconto
                    + $juros
                    + $multa;

                if ($valorDevido <= 0) {
                    throw ValidationException::withMessages([
                        'valor_original' =>
                            'O valor final da conta deve ser maior que zero.',
                    ]);
                }

                $dados['desconto'] =
                    $desconto;

                $dados['juros'] =
                    $juros;

                $dados['multa'] =
                    $multa;

                $dados['status'] =
                    'aberta';

                $dados['data_quitacao'] =
                    null;

                $contaReceber->update(
                    $dados
                );

                return $contaReceber->fresh();
            }
        );
    }

    private function paraCentavos(
        mixed $valor
    ): int {
        return (int) round(
            (float) $valor * 100
        );
    }
}
