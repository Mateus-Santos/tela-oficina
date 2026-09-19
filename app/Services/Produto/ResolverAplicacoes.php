<?php

namespace App\Services\Produto;

use App\Models\Montadora;
use App\Models\Veiculo;

class ResolverAplicacoes
{
    public function resolver(array $aplicacoes): array
    {
        $montadoras = [];
        $veiculosExistentes = [];
        $veiculosNovos = [];

        foreach ($aplicacoes as $aplicacao) {
            $montadoraNome = $aplicacao['montadora'];
            $veiculoNome = $aplicacao['veiculo'];

            $montadora = Montadora::query()
                ->where('nome', $montadoraNome)
                ->first();

            if (!$montadora) {
                $chaveMontadora = $this->normalizarChave($montadoraNome);

                $montadoras[$chaveMontadora] = [
                    'nome' => $montadoraNome,
                    'existente' => false,
                ];

                $chaveVeiculo = $this->normalizarChave(
                    $montadoraNome . '|' . $veiculoNome
                );

                $veiculosNovos[$chaveVeiculo] = [
                    'montadora' => $montadoraNome,
                    'veiculo' => $veiculoNome,
                    'montadora_existente' => false,
                ];

                continue;
            }

            $chaveMontadora = $this->normalizarChave($montadora->nome);

            $montadoras[$chaveMontadora] = [
                'id' => $montadora->id,
                'nome' => $montadora->nome,
                'existente' => true,
            ];

            $veiculo = Veiculo::query()
                ->where('montadora_id', $montadora->id)
                ->where('nome', $veiculoNome)
                ->first();

            if ($veiculo) {
                $chaveVeiculo = $this->normalizarChave(
                    $montadora->nome . '|' . $veiculo->nome
                );

                $veiculosExistentes[$chaveVeiculo] = [
                    'id' => $veiculo->id,
                    'montadora_id' => $montadora->id,
                    'montadora' => $montadora->nome,
                    'veiculo' => $veiculo->nome,
                ];

                continue;
            }

            $chaveVeiculo = $this->normalizarChave(
                $montadora->nome . '|' . $veiculoNome
            );

            $veiculosNovos[$chaveVeiculo] = [
                'montadora_id' => $montadora->id,
                'montadora' => $montadora->nome,
                'veiculo' => $veiculoNome,
                'montadora_existente' => true,
            ];
        }

        return [
            'montadoras' => array_values($montadoras),
            'veiculos_existentes' => array_values($veiculosExistentes),
            'veiculos_novos' => array_values($veiculosNovos),
            'total_aplicacoes' => count($aplicacoes),
        ];
    }

    private function normalizarChave(string $valor): string
    {
        return mb_strtolower(
            trim(
                preg_replace('/\s+/', ' ', $valor) ?? ''
            )
        );
    }
}
