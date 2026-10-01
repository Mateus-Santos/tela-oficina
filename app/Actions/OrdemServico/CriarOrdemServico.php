<?php

namespace App\Actions\OrdemServico;

use App\Models\OrdemServico;
use App\Models\VeiculosCliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CriarOrdemServico
{
    public function execute(array $dados): OrdemServico
    {
        return DB::transaction(
            function () use ($dados) {
                $veiculoCliente =
                    VeiculosCliente::query()
                        ->where(
                            'id',
                            $dados['veiculo_cliente_id']
                        )
                        ->whereHas(
                            'clientes',
                            function ($query) use ($dados) {
                                $query->where(
                                    'clientes.id',
                                    $dados['cliente_id']
                                );
                            }
                        )
                        ->first();

                if (!$veiculoCliente) {
                    throw ValidationException::withMessages([
                        'veiculo_cliente_id' =>
                            'O veículo selecionado não está vinculado ao cliente informado.',
                    ]);
                }

                $valor =
                    $this->normalizarValor(
                        $dados['valor']
                    );

                $ordemServico =
                    OrdemServico::create([
                        'data_abertura' =>
                            now(),

                        'cliente_id' =>
                            $dados['cliente_id'],

                        'veiculo_cliente_id' =>
                            $dados['veiculo_cliente_id'],

                        'setor_servico_id' =>
                            $dados['setor_servico_id'],

                        'descricao' =>
                            $dados['descricao'] ?? null,

                        'valor' =>
                            $valor,

                        'status' =>
                            'aberta',
                    ]);

                return $ordemServico->fresh([
                    'setorServico',
                ]);
            }
        );
    }

    private function normalizarValor(
        mixed $valor
    ): float {
        $texto =
            trim(
                (string) $valor
            );

        if (str_contains($texto, ',')) {
            $texto =
                str_replace(
                    '.',
                    '',
                    $texto
                );

            $texto =
                str_replace(
                    ',',
                    '.',
                    $texto
                );
        }

        return round(
            (float) $texto,
            2
        );
    }
}
