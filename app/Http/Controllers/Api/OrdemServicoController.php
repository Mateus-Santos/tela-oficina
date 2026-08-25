<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrdemServico;

class OrdemServicoController extends Controller
{
    public function ordemservico(string $id)
    {
        $ordemservico = OrdemServico::with('veiculoCliente.user')->find($id);

        if (!$ordemservico) {
            return response()->json(['error' => 'O.S não encontrada'], 404);
        }

        return response()->json($ordemservico->veiculocliente->user->name);
    }
}
