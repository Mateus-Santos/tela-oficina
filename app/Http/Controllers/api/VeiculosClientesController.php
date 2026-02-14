<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdemServico;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VeiculosClientes;
use App\Models\Cliente;

class VeiculosClientesController extends Controller
{
    public function buscarPorPlaca($placa)
    {
        $veiculo = VeiculosClientes::with('cliente.user')
            ->where('placa', $placa)
            ->first();

        if (!$veiculo) {
            return response()->json(['error' => 'Veículo não encontrado'], 404);
        }

        return response()->json([
            'veiculo_id' => $veiculo->id,
            'placa' => $veiculo->placa,
            'cliente_nome' => $veiculo->cliente->user->name, // ← aqui muda
        ]);

    }
}
