<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VeiculosCliente;
use Illuminate\Http\JsonResponse;

class VeiculosClientesController extends Controller
{
    public function buscarPorPlaca(string $placa): JsonResponse
    {
        $placaLimpa = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $placa));

        if (empty($placaLimpa)) {
            return response()->json(['message' => 'Placa inválida.'], 422);
        }

        $veiculo = VeiculosCliente::with([
            'cliente.pessoa',
            'ordensServico' => function ($query) {
                // Traz apenas OSs abertas/em andamento se necessário, ou ordene por mais recente
                $query->orderBy('created_at', 'desc');
            }
        ])
        ->where('placa', $placaLimpa)
        ->first();

        if (!$veiculo) {
            return response()->json(['message' => 'Veículo não encontrado.'], 404);
        }

        return response()->json([
            'veiculo_id'     => $veiculo->id,
            'placa'          => $veiculo->placa,
            'cliente_id'     => $veiculo->cliente_id,
            'cliente_nome'   => $veiculo->cliente?->pessoa?->nome ?? 'Cliente não informado',
            'ordens_servico' => $veiculo->ordensServico->map(function ($os) {
                return [
                    'id' => $os->id,
                    // Garante um texto padrão caso o campo descricao esteja vazio
                    'descricao' => $os->descricao ?: 'OS #' . $os->id . ' (' . ($os->status ?? 'Aberta') . ')',
                ];
            }),
        ], 200);
    }
}
