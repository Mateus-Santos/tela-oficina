<?php

namespace App\Services\Produto;

use App\Models\Montadora;
use App\Models\Veiculo;
use InvalidArgumentException;

class ResolverAplicacoes
{
    public function resolver(
        array $aplicacoes
    ): array {
        $montadoras = [];
        $veiculosExistentes = [];
        $veiculosNovos = [];

        foreach (
            $aplicacoes
            as $aplicacao
        ) {
            $montadoraNome =
                $this->normalizarTexto(
                    $aplicacao[
                        'montadora'
                    ] ?? ''
                );

            $veiculoNome =
                $this->normalizarTexto(
                    $aplicacao[
                        'veiculo'
                    ] ?? ''
                );

            if (
                $montadoraNome === ''
                || $veiculoNome === ''
            ) {
                continue;
            }

            $montadora =
                $this->localizarMontadora(
                    $montadoraNome
                );

            if (!$montadora) {
                $chaveMontadora =
                    $this->normalizarChave(
                        $montadoraNome
                    );

                $montadoras[
                    $chaveMontadora
                ] = [
                    'nome' =>
                        $montadoraNome,

                    'existente' =>
                        false,
                ];

                $chaveVeiculo =
                    $this->normalizarChave(
                        $montadoraNome
                        .'|'
                        .$veiculoNome
                    );

                $veiculosNovos[
                    $chaveVeiculo
                ] = [
                    'montadora' =>
                        $montadoraNome,

                    'veiculo' =>
                        $veiculoNome,

                    'montadora_existente' =>
                        false,
                ];

                continue;
            }

            $chaveMontadora =
                $this->normalizarChave(
                    $montadora->nome
                );

            $montadoras[
                $chaveMontadora
            ] = [
                'id' =>
                    $montadora->id,

                'nome' =>
                    $montadora->nome,

                'existente' =>
                    true,
            ];

            $veiculo =
                $this->localizarVeiculo(
                    $montadora,
                    $veiculoNome
                );

            if ($veiculo) {
                $chaveVeiculo =
                    $this->normalizarChave(
                        $montadora->nome
                        .'|'
                        .$veiculo->nome
                    );

                $veiculosExistentes[
                    $chaveVeiculo
                ] = [
                    'id' =>
                        $veiculo->id,

                    'montadora_id' =>
                        $montadora->id,

                    'montadora' =>
                        $montadora->nome,

                    'veiculo' =>
                        $veiculo->nome,
                ];

                continue;
            }

            $chaveVeiculo =
                $this->normalizarChave(
                    $montadora->nome
                    .'|'
                    .$veiculoNome
                );

            $veiculosNovos[
                $chaveVeiculo
            ] = [
                'montadora_id' =>
                    $montadora->id,

                'montadora' =>
                    $montadora->nome,

                'veiculo' =>
                    $veiculoNome,

                'montadora_existente' =>
                    true,
            ];
        }

        return [
            'montadoras' =>
                array_values(
                    $montadoras
                ),

            'veiculos_existentes' =>
                array_values(
                    $veiculosExistentes
                ),

            'veiculos_novos' =>
                array_values(
                    $veiculosNovos
                ),

            'total_aplicacoes' =>
                count(
                    $aplicacoes
                ),
        ];
    }

    public function resolverOuCriarVeiculo(
        array $aplicacao
    ): Veiculo {
        $montadoraNome =
            $this->normalizarTexto(
                $aplicacao[
                    'montadora'
                ] ?? ''
            );

        $veiculoNome =
            $this->normalizarTexto(
                $aplicacao[
                    'veiculo'
                ] ?? ''
            );

        if (
            $montadoraNome === ''
            || $veiculoNome === ''
        ) {
            throw new InvalidArgumentException(
                'Montadora e veículo são obrigatórios.'
            );
        }

        $montadora =
            $this->localizarMontadora(
                $montadoraNome
            );

        if (!$montadora) {
            $montadora =
                Montadora::query()
                    ->create([
                        'nome' =>
                            $montadoraNome,
                    ]);
        }

        $veiculo =
            $this->localizarVeiculo(
                $montadora,
                $veiculoNome
            );

        if ($veiculo) {
            return $veiculo;
        }

        return Veiculo::query()
            ->create([
                'nome' =>
                    $veiculoNome,

                'montadora_id' =>
                    $montadora->id,
            ]);
    }

    private function localizarMontadora(
        string $nome
    ): ?Montadora {
        $nome =
            $this->normalizarTexto(
                $nome
            );

        if ($nome === '') {
            return null;
        }

        return Montadora::query()
            ->whereRaw(
                'LOWER(TRIM(nome)) = ?',
                [
                    mb_strtolower(
                        $nome
                    ),
                ]
            )
            ->first();
    }

    private function localizarVeiculo(
        Montadora $montadora,
        string $nome
    ): ?Veiculo {
        $nome =
            $this->normalizarTexto(
                $nome
            );

        if ($nome === '') {
            return null;
        }

        return Veiculo::query()
            ->where(
                'montadora_id',
                $montadora->id
            )
            ->whereRaw(
                'LOWER(TRIM(nome)) = ?',
                [
                    mb_strtolower(
                        $nome
                    ),
                ]
            )
            ->first();
    }

    private function normalizarTexto(
        string $valor
    ): string {
        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                $valor
            ) ?? ''
        );
    }

    private function normalizarChave(
        string $valor
    ): string {
        return mb_strtolower(
            $this->normalizarTexto(
                $valor
            )
        );
    }
}
