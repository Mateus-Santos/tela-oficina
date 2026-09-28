<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VeiculosCliente;
use Illuminate\Http\JsonResponse;

class VeiculosClientesController extends Controller
{
    public function buscarPorPlaca(string $placa): JsonResponse
    {
        $placaLimpa = strtoupper(
            preg_replace(
                '/[^A-Z0-9]/i',
                '',
                $placa
            )
        );

        if (empty($placaLimpa)) {
            return response()->json(
                [
                    'message' => 'Placa inválida.',
                ],
                422
            );
        }

        $veiculo = VeiculosCliente::query()
            ->with([
                'veiculo.montadora',
                'clientes.pessoa',
                'ordensServico.cliente.pessoa',
            ])
            ->where(
                'placa',
                $placaLimpa
            )
            ->first();

        if (!$veiculo) {
            return response()->json(
                [
                    'message' => 'Veículo não encontrado.',
                ],
                404
            );
        }

        return response()->json(
            [
                'veiculo_cliente_id' => $veiculo->id,

                'placa' => $veiculo->placa,

                'ano' => $veiculo->ano,

                'cor' => $veiculo->cor,

                'veiculo' => [
                    'id' => $veiculo->veiculo?->id,

                    'nome' =>
                        $veiculo->veiculo?->nome,

                    'montadora' => [
                        'id' =>
                            $veiculo
                                ->veiculo
                                ?->montadora
                                ?->id,

                        'nome' =>
                            $veiculo
                                ->veiculo
                                ?->montadora
                                ?->nome,
                    ],
                ],

                'clientes' => $veiculo
                    ->clientes
                    ->map(
                        function ($cliente) {
                            return [
                                'id' => $cliente->id,

                                'nome' =>
                                    $cliente
                                        ->pessoa
                                        ?->nome
                                    ?? 'Cliente não informado',

                                'cpf' =>
                                    $cliente
                                        ->pessoa
                                        ?->cpf,

                                'telefone' =>
                                    $cliente
                                        ->pessoa
                                        ?->telefone_1
                                    ?: $cliente
                                        ->pessoa
                                        ?->telefone_2,
                            ];
                        }
                    )
                    ->values(),

                'ordens_servico' => $veiculo
                    ->ordensServico
                    ->map(
                        function ($os) {
                            return [
                                'id' => $os->id,

                                'cliente_id' =>
                                    $os->cliente_id,

                                'cliente_nome' =>
                                    $os
                                        ->cliente
                                        ?->pessoa
                                        ?->nome
                                    ?? 'Cliente não informado',

                                'status' =>
                                    $os->status,

                                'descricao' =>
                                    $os->descricao
                                    ?: 'OS #'
                                        . $os->id
                                        . ' ('
                                        . ($os->status ?? 'Aberta')
                                        . ')',
                            ];
                        }
                    )
                    ->values(),
            ],
            200
        );
    }
}
