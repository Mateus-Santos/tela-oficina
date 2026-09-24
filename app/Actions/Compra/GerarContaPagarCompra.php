<?php

namespace App\Actions\Compra;

use App\Models\Compra;
use App\Models\ContaPagar;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GerarContaPagarCompra
{
    public function execute(
        Compra $compra,
        array $parcelas
    ): ContaPagar {
        return DB::transaction(function () use ($compra, $parcelas) {
            $compra = Compra::query()
                ->lockForUpdate()
                ->with('contaPagar')
                ->findOrFail($compra->id);

            if (!$compra->estaAprovada()) {
                throw new InvalidArgumentException(
                    'Somente compras aprovadas podem gerar uma conta a pagar.'
                );
            }

            if ($compra->contaPagar) {
                throw new InvalidArgumentException(
                    'Esta compra já possui uma conta a pagar vinculada.'
                );
            }

            if (empty($parcelas)) {
                throw new InvalidArgumentException(
                    'É necessário informar pelo menos uma parcela.'
                );
            }

            $valorTotalEmCentavos = (int) round(
                (float) $compra->valor_total * 100
            );

            $valorParcelasEmCentavos = 0;

            foreach ($parcelas as $indice => $parcela) {
                $numero = (int) ($parcela['numero'] ?? 0);
                $valor = (float) ($parcela['valor'] ?? 0);
                $valorEmCentavos = (int) round($valor * 100);
                $dataVencimento = $parcela['data_vencimento'] ?? null;

                if ($numero !== $indice + 1) {
                    throw new InvalidArgumentException(
                        'A numeração das parcelas é inválida.'
                    );
                }

                if ($valorEmCentavos <= 0) {
                    throw new InvalidArgumentException(
                        "O valor da parcela {$numero} deve ser maior que zero."
                    );
                }

                if (!$dataVencimento) {
                    throw new InvalidArgumentException(
                        "A data de vencimento da parcela {$numero} é obrigatória."
                    );
                }

                $valorParcelasEmCentavos += $valorEmCentavos;
            }

            if ($valorParcelasEmCentavos !== $valorTotalEmCentavos) {
                throw new InvalidArgumentException(
                    'A soma das parcelas deve ser exatamente igual ao valor total da compra.'
                );
            }

            $primeiraParcela = $parcelas[0];

            $conta = ContaPagar::create([
                'compra_id' => $compra->id,
                'fornecedor_id' => $compra->fornecedor_id,
                'nota_id' => null,
                'categoria_financeira_id' => null,
                'forma_pagamento_id' => null,
                'descricao' => $this->gerarDescricao($compra),
                'valor' => $compra->valor_total,
                'data_emissao' => $compra->data_emissao ?? $compra->data_entrada,
                'data_vencimento' => $primeiraParcela['data_vencimento'],
                'status' => 'aberta',
                'observacoes' => null,
            ]);

            foreach ($parcelas as $parcela) {
                $conta->parcelas()->create([
                    'numero' => (int) $parcela['numero'],
                    'valor' => $parcela['valor'],
                    'data_vencimento' => $parcela['data_vencimento'],
                ]);
            }

            return $conta->load('parcelas');
        });
    }

    private function gerarDescricao(Compra $compra): string
    {
        if ($compra->numero_nf) {
            return 'Compra - NF ' . $compra->numero_nf;
        }

        return 'Compra #' . $compra->id;
    }
}
