<?php

namespace App\Actions\Financeiro;

use App\Models\ContaReceber;
use App\Models\Nota;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CriarContaReceber
{
    public function execute(array $dados): ContaReceber
    {
        return DB::transaction(function () use ($dados) {
            $nota = null;

            if (!empty($dados['nota_id'])) {
                $nota = Nota::query()
                    ->lockForUpdate()
                    ->findOrFail($dados['nota_id']);

                $contaExistente = ContaReceber::query()
                    ->where('nota_id', $nota->id)
                    ->exists();

                if ($contaExistente) {
                    throw ValidationException::withMessages([
                        'nota_id' => "A Nota #{$nota->id} já possui uma conta a receber.",
                    ]);
                }

                if (
                    !empty($dados['cliente_id'])
                    && (int) $dados['cliente_id'] !== (int) $nota->cliente_id
                ) {
                    throw ValidationException::withMessages([
                        'cliente_id' => 'O cliente informado não corresponde ao cliente da nota.',
                    ]);
                }

                $dados['cliente_id'] = $nota->cliente_id;
            }

            if (empty($dados['cliente_id'])) {
                throw ValidationException::withMessages([
                    'cliente_id' => 'É necessário informar um cliente ou uma nota vinculada.',
                ]);
            }

            $valorOriginalCentavos = $this->paraCentavos(
                $dados['valor_original'] ?? 0
            );

            $descontoCentavos = $this->paraCentavos(
                $dados['desconto'] ?? 0
            );

            $jurosCentavos = $this->paraCentavos(
                $dados['juros'] ?? 0
            );

            $multaCentavos = $this->paraCentavos(
                $dados['multa'] ?? 0
            );

            if ($valorOriginalCentavos <= 0) {
                throw ValidationException::withMessages([
                    'valor_original' => 'O valor original deve ser maior que zero.',
                ]);
            }

            if ($descontoCentavos < 0) {
                throw ValidationException::withMessages([
                    'desconto' => 'O desconto não pode ser negativo.',
                ]);
            }

            if ($jurosCentavos < 0) {
                throw ValidationException::withMessages([
                    'juros' => 'Os juros não podem ser negativos.',
                ]);
            }

            if ($multaCentavos < 0) {
                throw ValidationException::withMessages([
                    'multa' => 'A multa não pode ser negativa.',
                ]);
            }

            $valorDevidoCentavos =
                $valorOriginalCentavos
                - $descontoCentavos
                + $jurosCentavos
                + $multaCentavos;

            if ($valorDevidoCentavos <= 0) {
                throw ValidationException::withMessages([
                    'valor_original' => 'O valor final da conta deve ser maior que zero.',
                ]);
            }

            if ($nota) {
                $valorNotaCentavos = $this->paraCentavos(
                    $nota->total
                );

                if ($valorOriginalCentavos !== $valorNotaCentavos) {
                    throw ValidationException::withMessages([
                        'valor_original' => 'O valor original da conta deve ser igual ao total da nota.',
                    ]);
                }
            }

            $parcelas = $dados['parcelas'] ?? null;

            if (!is_array($parcelas) || empty($parcelas)) {
                throw ValidationException::withMessages([
                    'parcelas' => 'Informe ao menos uma parcela para a conta a receber.',
                ]);
            }

            $parcelasValidadas = [];
            $numeros = [];
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

                if (in_array($numero, $numeros, true)) {
                    throw ValidationException::withMessages([
                        "parcelas.{$indice}.numero" => "A parcela número {$numero} está duplicada.",
                    ]);
                }

                $valorCentavos = $this->paraCentavos(
                    $parcela['valor'] ?? 0
                );

                if ($valorCentavos <= 0) {
                    throw ValidationException::withMessages([
                        "parcelas.{$indice}.valor" => 'O valor da parcela deve ser maior que zero.',
                    ]);
                }

                $dataVencimento = $parcela['data_vencimento'] ?? null;

                if (
                    !$dataVencimento
                    || !$this->dataValida($dataVencimento)
                ) {
                    throw ValidationException::withMessages([
                        "parcelas.{$indice}.data_vencimento" => 'A data de vencimento da parcela é inválida.',
                    ]);
                }

                $numeros[] = $numero;
                $totalParcelasCentavos += $valorCentavos;

                $parcelasValidadas[] = [
                    'numero' => $numero,
                    'valor' => $valorCentavos / 100,
                    'data_vencimento' => $dataVencimento,
                ];
            }

            if ($totalParcelasCentavos !== $valorOriginalCentavos) {
                throw ValidationException::withMessages([
                    'parcelas' => sprintf(
                        'A soma das parcelas deve ser igual ao valor original da conta. Valor esperado: R$ %s.',
                        number_format(
                            $valorOriginalCentavos / 100,
                            2,
                            ',',
                            '.'
                        )
                    ),
                ]);
            }

            usort(
                $parcelasValidadas,
                fn (array $a, array $b) => $a['numero'] <=> $b['numero']
            );

            $dados['valor_original'] = $valorOriginalCentavos / 100;
            $dados['desconto'] = $descontoCentavos / 100;
            $dados['juros'] = $jurosCentavos / 100;
            $dados['multa'] = $multaCentavos / 100;
            $dados['status'] = 'aberta';
            $dados['data_vencimento'] = $parcelasValidadas[0]['data_vencimento'];

            unset($dados['parcelas']);

            $conta = ContaReceber::create($dados);

            $conta->parcelas()->createMany(
                $parcelasValidadas
            );

            return $conta->load('parcelas');
        });
    }

    private function paraCentavos(mixed $valor): int
    {
        return (int) round(
            (float) $valor * 100
        );
    }

    private function dataValida(mixed $data): bool
    {
        if (!is_string($data)) {
            return false;
        }

        $objeto = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $data
        );

        return $objeto !== false
            && $objeto->format('Y-m-d') === $data;
    }
}
