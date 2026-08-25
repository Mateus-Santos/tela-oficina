<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Montadora;

class MontadoraController extends Controller
{
    public function veiculos(string $id)
    {
        $montadora = Montadora::with('veiculos')->find($id);

        if (!$montadora) {
            return response()->json(['error' => 'Montadora não encontrada'], 404);
        }

        return response()->json($montadora->veiculos);
    }
}
