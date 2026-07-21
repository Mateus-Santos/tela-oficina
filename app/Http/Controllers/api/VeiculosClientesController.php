<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdemServico;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VeiculosCliente;
use App\Models\Cliente;

class VeiculosClientesController extends Controller
{
    public function buscarPorPlaca($placa)
    {
        $placa = strtoupper(str_replace(['-', ' '], '', $placa));

        $veiculo = VeiculosCliente::with([
            'cliente.user',
            'ordensServico'
        ])
        ->where('placa', $placa)
        ->first();

        if (!$veiculo) {
            return response()->json(['error' => 'Veículo não encontrado'], 404);
        }

        return response()->json([
            'veiculo_id' => $veiculo->id,
            'placa' => $veiculo->placa,
            'cliente_nome' => $veiculo->cliente->user->name,
            'cliente_id' => $veiculo->cliente->id,
            'ordens_servico' => $veiculo->ordensServico->map(function ($os) {
                return [
                    'id' => $os->id,
                    'descricao' => $os->descricao,
                ];
            }),
        ]);
    }

}
