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
        return DB::transaction(function () use ($contaReceber, $dados) {
            $contaReceber = ContaReceber::query()
                ->lockForUpdate()
                ->findOrFail($contaReceber->id);

            if (
                in_array(
                    $contaReceber->status,
                    ['quitada', 'cancelada'],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'contaReceber' => 'Contas quitadas ou canceladas não podem ser alteradas.',
                ]);
            }

            if (
                $contaReceber
                    ->recebimentos()
                    ->whereNull('estornado_em')
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'contaReceber' => 'Contas que possuem recebimentos ativos não podem ser alteradas manualmente.',
                ]);
            }

            $nota = null;

            if (!empty($dados['nota_id'])) {
                $nota = Nota::query()
                    ->lockForUpdate()
                    ->findOrFail($dados['nota_id']);

                if ($nota->status === 'Cancelado') {
                    throw ValidationException::withMessages([
                        'nota_id' => "A Nota #{$nota->id} está cancelada e não pode ser vinculada a uma conta a receber.",
                    ]);
                }

                $outraConta = ContaReceber::query()
                    ->where('nota_id', $nota->id)
                    ->where('id', '!=', $contaReceber->id)
                    ->exists();

                if ($outraConta) {
                    throw ValidationException::withMessages([
                        'nota_id' => "A Nota #{$nota->id} já possui outra conta a receber.",
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

            if (!$nota && empty($dados['cliente_id'])) {
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

            if ($nota) {
                $valorNotaCentavos = $this->paraCentavos($nota->total);

                if ($valorOriginalCentavos !== $valorNotaCentavos) {
                    throw ValidationException::withMessages([
                        'valor_original' => 'O valor original da conta deve ser igual ao total da nota.',
                    ]);
                }
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

            $parcelas = $contaReceber
                ->parcelas()
                ->lockForUpdate()
                ->get();

            if ($parcelas->isEmpty()) {
                throw ValidationException::withMessages([
                    'parcelas' => 'A conta a receber não possui parcelas cadastradas.',
                ]);
            }

            $totalParcelasCentavos = 0;

            foreach ($parcelas as $parcela) {
                $totalParcelasCentavos += $this->paraCentavos($parcela->valor);
            }

            $diferencaCentavos =
                $valorDevidoCentavos
                - $totalParcelasCentavos;

            if ($diferencaCentavos > 0) {
                $ultimaParcela = $parcelas
                    ->sortByDesc('numero')
                    ->first();

                $valorAtualCentavos = $this->paraCentavos(
                    $ultimaParcela->valor
                );

                $ultimaParcela->update([
                    'valor' => ($valorAtualCentavos + $diferencaCentavos) / 100,
                ]);
            }

            if ($diferencaCentavos < 0) {
                $valorReduzirCentavos = abs($diferencaCentavos);

                foreach ($parcelas->sortByDesc('numero') as $parcela) {
                    if ($valorReduzirCentavos <= 0) {
                        break;
                    }

                    $valorParcelaCentavos = $this->paraCentavos($parcela->valor);

                    if ($valorReduzirCentavos < $valorParcelaCentavos) {
                        $parcela->update([
                            'valor' => ($valorParcelaCentavos - $valorReduzirCentavos) / 100,
                        ]);

                        $valorReduzirCentavos = 0;
                        break;
                    }

                    if ($parcela->recebimentos()->exists()) {
                        throw ValidationException::withMessages([
                            'parcelas' => 'Não é possível remover uma parcela que possui histórico de recebimentos.',
                        ]);
                    }

                    $valorReduzirCentavos -= $valorParcelaCentavos;
                    $parcela->delete();
                }

                if ($valorReduzirCentavos > 0) {
                    throw ValidationException::withMessages([
                        'parcelas' => 'Não foi possível ajustar as parcelas ao novo valor devido da conta.',
                    ]);
                }
            }

            $parcelasAtualizadas = $contaReceber
                ->parcelas()
                ->lockForUpdate()
                ->orderBy('numero')
                ->get();

            if ($parcelasAtualizadas->isEmpty()) {
                throw ValidationException::withMessages([
                    'parcelas' => 'A conta a receber precisa possuir ao menos uma parcela.',
                ]);
            }

            if (!empty($dados['data_vencimento'])) {
                $parcelasAtualizadas->first()->update([
                    'data_vencimento' => $dados['data_vencimento'],
                ]);
            }

            $totalParcelasAtualizadoCentavos = $this->paraCentavos(
                $contaReceber->parcelas()->sum('valor')
            );

            if ($totalParcelasAtualizadoCentavos !== $valorDevidoCentavos) {
                throw ValidationException::withMessages([
                    'parcelas' => 'As parcelas não correspondem ao novo valor devido da conta.',
                ]);
            }

            $primeiroVencimento = $contaReceber
                ->parcelas()
                ->orderBy('numero')
                ->value('data_vencimento');

            $dados['valor_original'] = $valorOriginalCentavos / 100;
            $dados['desconto'] = $descontoCentavos / 100;
            $dados['juros'] = $jurosCentavos / 100;
            $dados['multa'] = $multaCentavos / 100;
            $dados['data_vencimento'] = $primeiroVencimento;
            $dados['status'] = 'aberta';
            $dados['data_quitacao'] = null;

            $contaReceber->update($dados);

            return $contaReceber->fresh([
                'parcelas',
                'recebimentos',
            ]);
        });
    }

    private function paraCentavos(mixed $valor): int
    {
        return (int) round((float) $valor * 100);
    }
}
